<?php

namespace App\Services;

use Core\Database;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/**
 * Servicio generico de rate limit (Etapa 3.1 — infraestructura base).
 *
 * Responsabilidad unica: registrar y consultar intentos por combinacion
 * accion + identificador, usando una sola fila por combinacion
 * (contador con ventana fija), y decidir si esa combinacion esta
 * actualmente bloqueada.
 *
 * Este servicio NO decide el mensaje que ve el usuario final, NO
 * registra auditoria por si mismo, y NO resuelve la IP real detras de
 * un proxy — quien lo invoque decide el identificador exacto a usar.
 *
 * IMPORTANTE: en esta etapa el servicio existe de forma AISLADA. No
 * esta conectado a ningun controlador ni flujo funcional todavia.
 *
 * Criterio de ventana (documentado por decision de diseno): "ventana
 * fija" anclada a `first_attempt_at`. Los parametros `$maxAttempts`/
 * `$windowSeconds` recibidos en cada llamada son SIEMPRE la fuente de
 * verdad para decidir si la ventana vencio y si el limite se alcanzo;
 * los valores guardados en las columnas `max_attempts`/`window_seconds`
 * son solo informativos (reflejan los parametros usados en el ultimo
 * `hit()`), no una configuracion alterna. Una vez que una combinacion
 * queda bloqueada dentro de una ventana, `available_at` no se extiende
 * por intentos adicionales dentro de esa misma ventana: el bloqueo dura
 * hasta que la ventana original vence, momento en el que se reinicia.
 */
class RateLimitService
{
    private PDO $db;

    private const TABLE = 'tbl_rate_limits';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Registra un intento para $action + $identifier y devuelve el
     * estado resultante.
     *
     * @return array{blocked:bool, attempts:int, remaining:int, available_in:int, available_at:?string}
     */
    public function hit(
        string $action,
        string $identifier,
        string $identifierType,
        int $maxAttempts,
        int $windowSeconds
    ): array {
        $this->assertValid($action, $identifier, $identifierType, $maxAttempts, $windowSeconds);

        $hash = $this->getIdentifierHash($identifier, $identifierType);
        $now  = new DateTimeImmutable();

        $row = $this->findRow($action, $hash);

        if ($row === null) {
            $attempts     = 1;
            $firstAttempt = $now;
            $availableAt  = $attempts >= $maxAttempts ? $now->modify("+{$windowSeconds} seconds") : null;

            $this->insertRow($action, $hash, $identifierType, $attempts, $maxAttempts, $windowSeconds, $firstAttempt, $availableAt, $now);

            return $this->buildResult($attempts, $maxAttempts, $availableAt, $now);
        }

        $firstAttempt = new DateTimeImmutable((string) $row['first_attempt_at']);
        $windowEndsAt = $firstAttempt->modify("+{$windowSeconds} seconds");

        if ($now >= $windowEndsAt) {
            // Ventana vencida: reiniciar por completo.
            $attempts     = 1;
            $firstAttempt = $now;
            $availableAt  = $attempts >= $maxAttempts ? $now->modify("+{$windowSeconds} seconds") : null;

            $this->updateRow((int) $row['id'], $identifierType, $attempts, $maxAttempts, $windowSeconds, $firstAttempt, $availableAt, $now);

            return $this->buildResult($attempts, $maxAttempts, $availableAt, $now);
        }

        // Ventana activa: incrementar.
        $attempts = (int) $row['attempts'] + 1;

        if ($attempts >= $maxAttempts) {
            // No extender un bloqueo ya establecido dentro de la misma ventana.
            $existingAvailableAt = $row['available_at'] !== null ? new DateTimeImmutable((string) $row['available_at']) : null;
            $availableAt = $existingAvailableAt ?? $firstAttempt->modify("+{$windowSeconds} seconds");
        } else {
            $availableAt = null;
        }

        $this->updateRow((int) $row['id'], $identifierType, $attempts, $maxAttempts, $windowSeconds, $firstAttempt, $availableAt, $now);

        return $this->buildResult($attempts, $maxAttempts, $availableAt, $now);
    }

    /**
     * Consulta si $action + $identifier esta actualmente bloqueado,
     * SIN incrementar ningun contador. Si `available_at` ya vencio,
     * se considera no bloqueado (aunque el registro en BD siga sin
     * limpiar hasta el proximo `hit()` o `cleanupExpired()`).
     */
    public function tooManyAttempts(
        string $action,
        string $identifier,
        int $maxAttempts,
        int $windowSeconds,
        string $identifierType = 'custom'
    ): bool {
        $this->assertValid($action, $identifier, $identifierType, $maxAttempts, $windowSeconds);

        $hash = $this->getIdentifierHash($identifier, $identifierType);
        $row  = $this->findRow($action, $hash);

        if ($row === null || $row['available_at'] === null) {
            return false;
        }

        $availableAt = new DateTimeImmutable((string) $row['available_at']);

        return new DateTimeImmutable() < $availableAt;
    }

    /**
     * Segundos restantes hasta que $action + $identifier vuelva a
     * estar disponible. Devuelve 0 si no esta bloqueado o si no existe
     * registro.
     */
    public function availableIn(string $action, string $identifier, string $identifierType = 'custom'): int
    {
        if ($action === '' || $identifier === '' || $identifierType === '') {
            throw new InvalidArgumentException('RateLimitService::availableIn — action, identifier e identifierType no pueden estar vacios.');
        }

        $hash = $this->getIdentifierHash($identifier, $identifierType);
        $row  = $this->findRow($action, $hash);

        if ($row === null || $row['available_at'] === null) {
            return 0;
        }

        $now         = new DateTimeImmutable();
        $availableAt = new DateTimeImmutable((string) $row['available_at']);

        if ($now >= $availableAt) {
            return 0;
        }

        return $availableAt->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Elimina el registro de $action + $identifier (reinicio completo
     * tras un exito, por ejemplo un login correcto). Devuelve true si
     * existia y se elimino, false si no habia nada que limpiar.
     */
    public function clear(string $action, string $identifier, string $identifierType = 'custom'): bool
    {
        if ($action === '' || $identifier === '' || $identifierType === '') {
            throw new InvalidArgumentException('RateLimitService::clear — action, identifier e identifierType no pueden estar vacios.');
        }

        $hash = $this->getIdentifierHash($identifier, $identifierType);

        $stmt = $this->db->prepare(
            'DELETE FROM ' . self::TABLE . ' WHERE action = ? AND identifier_hash = ?'
        );
        $stmt->execute([$action, $hash]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Calcula el hash HMAC-SHA256 (hex) del identificador, usando
     * APP_KEY como clave secreta. NUNCA se guarda el identificador
     * crudo en la base de datos — solo este hash.
     *
     * Normalizacion aplicada antes de calcular el hash (decision de
     * diseno, ver docstring de la clase):
     *   - Siempre: trim().
     *   - identifier_type === 'email': ademas mb_strtolower(), porque
     *     los correos se tratan como no sensibles a mayusculas en todo
     *     el proyecto (ver App\Models\User::findByEmail()).
     *   - identifier_type === 'ip' | 'user' | 'session' | 'custom' |
     *     cualquier otro valor: solo trim(), sin lowercase, para no
     *     alterar IPs (IPv6 puede ser sensible a mayusculas en su
     *     forma canonica), IDs de usuario numericos, ni identificadores
     *     de sesion/personalizados.
     */
    public function getIdentifierHash(string $identifier, string $identifierType = 'custom'): string
    {
        $normalized = trim($identifier);

        if ($identifierType === 'email') {
            $normalized = mb_strtolower($normalized);
        }

        if ($normalized === '') {
            throw new InvalidArgumentException('RateLimitService::getIdentifierHash — el identificador no puede quedar vacio tras normalizar.');
        }

        $hash = hash_hmac('sha256', $normalized, $this->appKey());

        if ($hash === '' || $hash === false) {
            // hash_hmac() con sha256 nunca deberia devolver vacio/false en PHP soportado,
            // pero se valida explicitamente para no devolver jamas un hash vacio.
            throw new \RuntimeException('RateLimitService::getIdentifierHash — no se pudo calcular el hash HMAC.');
        }

        return $hash;
    }

    /**
     * Elimina registros vencidos y no bloqueados activamente. NO borra
     * un registro cuyo `available_at` siga en el futuro (bloqueo
     * activo), sin importar que tan antiguo sea `last_attempt_at`.
     *
     * @return int Cantidad de filas eliminadas.
     */
    public function cleanupExpired(int $olderThanSeconds = 86400): int
    {
        if ($olderThanSeconds <= 0) {
            throw new InvalidArgumentException('RateLimitService::cleanupExpired — olderThanSeconds debe ser mayor que 0.');
        }

        $stmt = $this->db->prepare(
            'DELETE FROM ' . self::TABLE . '
             WHERE last_attempt_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
               AND (available_at IS NULL OR available_at < NOW())'
        );
        $stmt->execute([$olderThanSeconds]);

        return $stmt->rowCount();
    }

    // ─── Internos ───────────────────────────────────────────────────────────

    private function assertValid(
        string $action,
        string $identifier,
        string $identifierType,
        int $maxAttempts,
        int $windowSeconds
    ): void {
        if ($action === '' || $identifier === '' || $identifierType === '') {
            throw new InvalidArgumentException('RateLimitService — action, identifier e identifierType no pueden estar vacios.');
        }
        if ($maxAttempts <= 0) {
            throw new InvalidArgumentException('RateLimitService — maxAttempts debe ser mayor que 0.');
        }
        if ($windowSeconds <= 0) {
            throw new InvalidArgumentException('RateLimitService — windowSeconds debe ser mayor que 0.');
        }
    }

    private function appKey(): string
    {
        $key = (string) env('APP_KEY', '');
        if ($key === '') {
            // Misma convencion que Core\Crypt::key(): fallar de forma clara
            // en vez de calcular un hash inseguro o silenciosamente vacio.
            throw new \RuntimeException('RateLimitService: APP_KEY no esta configurada en .env');
        }

        return $key;
    }

    private function findRow(string $action, string $identifierHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . self::TABLE . ' WHERE action = ? AND identifier_hash = ? LIMIT 1'
        );
        $stmt->execute([$action, $identifierHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    private function insertRow(
        string $action,
        string $identifierHash,
        string $identifierType,
        int $attempts,
        int $maxAttempts,
        int $windowSeconds,
        DateTimeImmutable $firstAttemptAt,
        ?DateTimeImmutable $availableAt,
        DateTimeImmutable $now
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . self::TABLE . '
             (action, identifier_hash, identifier_type, attempts, max_attempts, window_seconds,
              first_attempt_at, available_at, last_attempt_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $action,
            $identifierHash,
            $identifierType,
            $attempts,
            $maxAttempts,
            $windowSeconds,
            $firstAttemptAt->format('Y-m-d H:i:s'),
            $availableAt?->format('Y-m-d H:i:s'),
            $now->format('Y-m-d H:i:s'),
        ]);
    }

    private function updateRow(
        int $id,
        string $identifierType,
        int $attempts,
        int $maxAttempts,
        int $windowSeconds,
        DateTimeImmutable $firstAttemptAt,
        ?DateTimeImmutable $availableAt,
        DateTimeImmutable $now
    ): void {
        $stmt = $this->db->prepare(
            'UPDATE ' . self::TABLE . '
             SET identifier_type = ?, attempts = ?, max_attempts = ?, window_seconds = ?,
                 first_attempt_at = ?, available_at = ?, last_attempt_at = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $identifierType,
            $attempts,
            $maxAttempts,
            $windowSeconds,
            $firstAttemptAt->format('Y-m-d H:i:s'),
            $availableAt?->format('Y-m-d H:i:s'),
            $now->format('Y-m-d H:i:s'),
            $id,
        ]);
    }

    /**
     * @return array{blocked:bool, attempts:int, remaining:int, available_in:int, available_at:?string}
     */
    private function buildResult(int $attempts, int $maxAttempts, ?DateTimeImmutable $availableAt, DateTimeImmutable $now): array
    {
        $blocked = $availableAt !== null && $now < $availableAt;

        return [
            'blocked'      => $blocked,
            'attempts'     => $attempts,
            'remaining'    => max(0, $maxAttempts - $attempts),
            'available_in' => $blocked ? ($availableAt->getTimestamp() - $now->getTimestamp()) : 0,
            'available_at' => $availableAt?->format('Y-m-d H:i:s'),
        ];
    }
}
