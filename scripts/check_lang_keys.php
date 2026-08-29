<?php

declare(strict_types=1);

/**
 * check_lang_keys.php — Validacion de solo lectura de las claves de
 * idioma de Skeleton (lang/es.php vs lang/en.php).
 *
 * Uso:
 *   php scripts/check_lang_keys.php
 *
 * Que hace:
 *   - Carga lang/es.php y lang/en.php (require, no evalua nada mas).
 *   - Aplana claves con notacion de punto si hubiera arrays anidados
 *     (los archivos actuales ya usan claves planas tipo 'auth.login',
 *     pero el aplanado se deja listo para arrays anidados futuros).
 *   - Compara ambos conjuntos de claves y reporta faltantes en cada
 *     direccion, duplicados detectados en el archivo fuente, y totales.
 *   - NO modifica ningun archivo. NO escribe en base de datos.
 *
 * Codigos de salida:
 *   0 = OK, no hay diferencias.
 *   1 = WARNING, hay claves faltantes entre es/en.
 *   2 = ERROR, archivo no encontrado / no devuelve array / fallo de carga.
 */

// ─── 1. Solo CLI ────────────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Acceso denegado. Este script solo puede ejecutarse desde CLI.\n";
    exit(2);
}

// ─── 2. Rutas robustas basadas en __DIR__ (sin rutas absolutas del equipo) ──
$projectRoot = dirname(__DIR__);
$esPath = $projectRoot . '/lang/es.php';
$enPath = $projectRoot . '/lang/en.php';

/**
 * Aplana un array asociativo con notacion de punto.
 * Ejemplo: ['auth' => ['login' => 'x']] -> ['auth.login' => 'x']
 * Si las claves ya vienen planas (como 'auth.login'), el resultado es
 * identico al array original.
 */
function flattenLangKeys(array $data, string $prefix = ''): array
{
    $flat = [];
    foreach ($data as $key => $value) {
        $fullKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (is_array($value)) {
            $flat += flattenLangKeys($value, $fullKey);
        } else {
            $flat[$fullKey] = $value;
        }
    }
    return $flat;
}

/**
 * Carga un archivo de idioma de forma segura.
 * Devuelve ['ok' => bool, 'data' => array|null, 'error' => string|null].
 */
function loadLangFile(string $path): array
{
    if (!is_file($path)) {
        return ['ok' => false, 'data' => null, 'error' => "Archivo no encontrado: {$path}"];
    }

    try {
        $result = require $path;
    } catch (\Throwable $e) {
        return ['ok' => false, 'data' => null, 'error' => "Error al cargar {$path}: " . $e->getMessage()];
    }

    if (!is_array($result)) {
        return ['ok' => false, 'data' => null, 'error' => "El archivo {$path} no devuelve un array."];
    }

    return ['ok' => true, 'data' => $result, 'error' => null];
}

/**
 * Detecta claves duplicadas leyendo el TEXTO FUENTE del archivo (no el
 * array ya cargado), porque PHP silencia duplicados en un array literal
 * (la ultima definicion sobrescribe a la anterior sin avisar).
 * Devuelve un array [clave => cantidad_de_apariciones] solo para las
 * claves que aparecen 2 o mas veces.
 */
function findDuplicateKeysInSource(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $source = file_get_contents($path);
    if ($source === false) {
        return [];
    }

    // Captura claves 'string' o "string" seguidas de => al inicio de linea
    // (con indentacion opcional), que es el patron usado en lang/*.php.
    preg_match_all('/^\s*[\'"]((?:[^\'"\\\\]|\\\\.)*)[\'"]\s*=>/m', $source, $matches);

    $counts = array_count_values($matches[1] ?? []);
    return array_filter($counts, static fn (int $count): bool => $count > 1);
}

// ─── 3. Cargar ambos archivos ───────────────────────────────────────────────
$es = loadLangFile($esPath);
$en = loadLangFile($enPath);

echo "============================================\n";
echo "Validación de claves de idioma - Skeleton\n";
echo "============================================\n\n";
echo "Archivo base ES: lang/es.php\n";
echo "Archivo base EN: lang/en.php\n\n";

if (!$es['ok'] || !$en['ok']) {
    if (!$es['ok']) {
        echo "ERROR (ES): {$es['error']}\n";
    }
    if (!$en['ok']) {
        echo "ERROR (EN): {$en['error']}\n";
    }
    echo "\nResultado: ERROR - No fue posible validar los archivos de idioma.\n";
    exit(2);
}

// ─── 4. Aplanar y comparar ──────────────────────────────────────────────────
$esFlat = flattenLangKeys($es['data']);
$enFlat = flattenLangKeys($en['data']);

$esKeys = array_keys($esFlat);
$enKeys = array_keys($enFlat);

$missingInEn = array_values(array_diff($esKeys, $enKeys));
$missingInEs = array_values(array_diff($enKeys, $esKeys));
$common      = array_values(array_intersect($esKeys, $enKeys));

sort($missingInEn);
sort($missingInEs);

$totalEs     = count($esKeys);
$totalEn     = count($enKeys);
$totalCommon = count($common);
$totalDiff   = count($missingInEn) + count($missingInEs);

echo "Total ES: {$totalEs}\n";
echo "Total EN: {$totalEn}\n";
echo "Comunes: {$totalCommon}\n";
echo "Faltantes en EN: " . count($missingInEn) . "\n";
echo "Faltantes en ES: " . count($missingInEs) . "\n";

// ─── 5. Duplicados detectados en el archivo fuente ─────────────────────────
$dupEs = findDuplicateKeysInSource($esPath);
$dupEn = findDuplicateKeysInSource($enPath);

// ─── 6. Valores vacios (problema de estructura menor, informativo) ────────
$emptyEs = array_keys(array_filter($esFlat, static fn ($v): bool => is_string($v) && trim($v) === ''));
$emptyEn = array_keys(array_filter($enFlat, static fn ($v): bool => is_string($v) && trim($v) === ''));
sort($emptyEs);
sort($emptyEn);

if (!empty($missingInEn)) {
    echo "\nClaves faltantes en EN:\n";
    foreach ($missingInEn as $key) {
        echo "- {$key}\n";
    }
}

if (!empty($missingInEs)) {
    echo "\nClaves faltantes en ES:\n";
    foreach ($missingInEs as $key) {
        echo "- {$key}\n";
    }
}

if (!empty($dupEs)) {
    echo "\nClaves duplicadas detectadas en lang/es.php (la ultima definicion gana, las anteriores se pierden silenciosamente):\n";
    foreach ($dupEs as $key => $count) {
        echo "- {$key} (x{$count})\n";
    }
}

if (!empty($dupEn)) {
    echo "\nClaves duplicadas detectadas en lang/en.php (la ultima definicion gana, las anteriores se pierden silenciosamente):\n";
    foreach ($dupEn as $key => $count) {
        echo "- {$key} (x{$count})\n";
    }
}

if (!empty($emptyEs)) {
    echo "\nClaves con valor vacio en lang/es.php:\n";
    foreach ($emptyEs as $key) {
        echo "- {$key}\n";
    }
}

if (!empty($emptyEn)) {
    echo "\nClaves con valor vacio en lang/en.php:\n";
    foreach ($emptyEn as $key) {
        echo "- {$key}\n";
    }
}

// ─── 7. Resultado final y codigo de salida ─────────────────────────────────
echo "\n";
if ($totalDiff === 0) {
    echo "Resultado: OK - No hay diferencias entre archivos de idioma.\n";
    exit(0);
}

echo "Resultado: WARNING - Hay diferencias entre archivos de idioma.\n";
exit(1);
