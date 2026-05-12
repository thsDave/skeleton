<?php

namespace App\Services;

use App\Models\UserSession;
use Core\Audit;
use Core\Auth;
use Core\Logger;
use Core\Session;

class UserSessionService
{
    private UserSession $sessions;

    public function __construct()
    {
        $this->sessions = new UserSession();
    }

    public function registerCurrentSession(int $userId): bool
    {
        try {
            $hash = $this->getCurrentSessionHash();
            $existing = $this->sessions->findByHash($hash);
            if ($existing && empty($existing['revoked_at'])) {
                $this->sessions->touch($hash);
                Session::set('_user_session_registered', 1);
                Session::set('_user_session_touch_at', time());
                return true;
            }

            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $parsed = $this->parseUserAgent($ua);
            $created = $this->sessions->create([
                'user_id' => $userId,
                'session_hash' => $hash,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $ua !== '' ? substr($ua, 0, 1000) : null,
                'browser' => $parsed['browser'],
                'platform' => $parsed['platform'],
                'device_type' => $parsed['device_type'],
            ]);

            if ($created) {
                Session::set('_user_session_registered', 1);
                Session::set('_user_session_touch_at', time());
                Audit::log([
                    'module' => 'sessions',
                    'action' => 'sessions.created',
                    'entity' => 'user_session',
                    'entity_id' => $created,
                    'description' => 'Sesion activa registrada',
                    'new_values' => [
                        'user_id' => $userId,
                        'browser' => $parsed['browser'],
                        'platform' => $parsed['platform'],
                        'device_type' => $parsed['device_type'],
                    ],
                    'status' => 'success',
                    'user_id' => $userId,
                ]);
                return true;
            }
        } catch (\Throwable $e) {
            Logger::error('UserSessionService::registerCurrentSession - ' . $e->getMessage());
        }

        return false;
    }

    public function getCurrentSessionHash(): string
    {
        $key = (string)env('APP_KEY', 'local-session-key');
        return hash_hmac('sha256', session_id(), $key);
    }

    public function enforceCurrentSession(): bool
    {
        try {
            if (!Auth::id()) {
                return true;
            }

            $hash = $this->getCurrentSessionHash();
            $session = $this->sessions->findByHash($hash);

            if (!$session) {
                $this->registerCurrentSession((int)Auth::id());
                return true;
            }

            if (!empty($session['revoked_at'])) {
                Audit::log([
                    'module' => 'sessions',
                    'action' => 'sessions.revoked_detected_on_request',
                    'entity' => 'user_session',
                    'entity_id' => (int)$session['id'],
                    'description' => 'Sesion revocada detectada durante request',
                    'status' => 'denied',
                ]);
                return false;
            }

            $lastTouch = (int)Session::get('_user_session_touch_at', 0);
            if ((time() - $lastTouch) >= 60) {
                $this->sessions->touch($hash);
                Session::set('_user_session_touch_at', time());
            }

            return true;
        } catch (\Throwable $e) {
            Logger::error('UserSessionService::enforceCurrentSession - ' . $e->getMessage());
            return true;
        }
    }

    public function revokeSession(int $sessionId, ?int $revokedBy, string $reason): bool
    {
        try {
            $session = $this->sessions->findActiveById($sessionId);
            if (!$session) {
                return false;
            }

            $ok = $this->sessions->revokeById($sessionId, $revokedBy, $reason);
            if ($ok) {
                Audit::log([
                    'module' => 'sessions',
                    'action' => $revokedBy && (int)$session['user_id'] === $revokedBy ? 'sessions.revoked_by_user' : 'sessions.revoked_by_admin',
                    'entity' => 'user_session',
                    'entity_id' => $sessionId,
                    'description' => 'Sesion activa revocada',
                    'new_values' => ['user_id' => (int)$session['user_id'], 'reason' => $reason],
                    'status' => 'success',
                    'user_id' => $revokedBy,
                ]);
            }
            return $ok;
        } catch (\Throwable $e) {
            Logger::error('UserSessionService::revokeSession - ' . $e->getMessage());
            return false;
        }
    }

    public function revokeCurrentSession(?int $revokedBy, string $reason): void
    {
        try {
            $this->sessions->revokeByHash($this->getCurrentSessionHash(), $revokedBy, $reason);
        } catch (\Throwable $e) {
            Logger::error('UserSessionService::revokeCurrentSession - ' . $e->getMessage());
        }
    }

    public function revokeOtherSessions(int $userId, ?int $revokedBy, string $reason): int
    {
        try {
            $count = $this->sessions->revokeOtherSessions($userId, $this->getCurrentSessionHash(), $revokedBy, $reason);
            Audit::log([
                'module' => 'sessions',
                'action' => $reason === 'password_change' ? 'sessions.revoked_due_password_change' : 'sessions.revoked_others',
                'entity' => 'user',
                'entity_id' => $userId,
                'description' => 'Otras sesiones activas revocadas',
                'new_values' => ['revoked_count' => $count, 'reason' => $reason],
                'status' => 'success',
                'user_id' => $revokedBy,
            ]);
            return $count;
        } catch (\Throwable $e) {
            Logger::error('UserSessionService::revokeOtherSessions - ' . $e->getMessage());
            return 0;
        }
    }

    public function revokeAllUserSessions(int $userId, ?int $revokedBy, string $reason, bool $keepCurrent = false): int
    {
        try {
            $exceptHash = $keepCurrent ? $this->getCurrentSessionHash() : null;
            $count = $this->sessions->revokeAllForUser($userId, $revokedBy, $reason, $exceptHash);
            $action = match ($reason) {
                'password_reset' => 'sessions.revoked_due_password_reset',
                'required_password_change', 'admin_password_change' => 'sessions.revoked_due_password_change',
                'mfa_change' => 'sessions.revoked_due_mfa_change',
                'email_change' => 'sessions.revoked_due_email_change',
                default => 'sessions.revoked_all_user',
            };
            Audit::log([
                'module' => 'sessions',
                'action' => $action,
                'entity' => 'user',
                'entity_id' => $userId,
                'description' => 'Sesiones activas de usuario revocadas',
                'new_values' => ['revoked_count' => $count, 'reason' => $reason],
                'status' => 'success',
                'user_id' => $revokedBy,
            ]);
            return $count;
        } catch (\Throwable $e) {
            Logger::error('UserSessionService::revokeAllUserSessions - ' . $e->getMessage());
            return 0;
        }
    }

    public function getCurrentUserSessions(int $userId): array
    {
        return $this->markCurrent($this->sessions->getActiveByUser($userId));
    }

    public function getCurrentUserSessionHistory(int $userId, array $filters = []): array
    {
        return $this->decorateHistory($this->sessions->getHistoryByUser($userId, $filters));
    }

    public function getActiveSessionsForAdmin(array $filters = []): array
    {
        return $this->markCurrent($this->sessions->getActiveForAdmin($filters));
    }

    public function findActiveSession(int $id): array|false
    {
        return $this->sessions->findActiveById($id);
    }

    public function findSession(int $id): array|false
    {
        return $this->sessions->findById($id);
    }

    public function getUserSessionHistoryForAdmin(int $userId, array $filters = []): array
    {
        return $this->decorateHistory($this->sessions->getHistoryByUser($userId, $filters));
    }

    public function parseUserAgent(string $ua): array
    {
        $browser = __('sessions.unknown_browser');
        if (stripos($ua, 'Edg/') !== false) {
            $browser = 'Microsoft Edge';
        } elseif (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium') === false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'Firefox/') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome/') === false) {
            $browser = 'Safari';
        }

        $platform = __('sessions.unknown_platform');
        if (stripos($ua, 'Windows') !== false) {
            $platform = 'Windows';
        } elseif (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) {
            $platform = 'macOS';
        } elseif (stripos($ua, 'Android') !== false) {
            $platform = 'Android';
        } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
            $platform = 'iOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $platform = 'Linux';
        }

        $device = __('sessions.unknown_device');
        if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) {
            $device = 'Mobile';
        } elseif ($ua !== '') {
            $device = 'Desktop';
        }

        return ['browser' => $browser, 'platform' => $platform, 'device_type' => $device];
    }

    private function markCurrent(array $sessions): array
    {
        $hash = $this->getCurrentSessionHash();
        foreach ($sessions as &$session) {
            $session['is_current'] = hash_equals((string)$session['session_hash'], $hash);
        }
        unset($session);
        return $sessions;
    }

    private function decorateHistory(array $sessions): array
    {
        $hash = $this->getCurrentSessionHash();
        foreach ($sessions as &$session) {
            $session['is_current'] = empty($session['revoked_at'])
                && hash_equals((string)$session['session_hash'], $hash);
            $session['display_status'] = $this->resolveDisplayStatus($session);
            $session['display_reason'] = empty($session['revoked_at'])
                ? '-'
                : $this->resolveDisplayReason((string)($session['revoke_reason'] ?? ''));
        }
        unset($session);
        return $sessions;
    }

    private function resolveDisplayStatus(array $session): string
    {
        if (!empty($session['is_current'])) {
            return __('sessions.current_session');
        }
        if (empty($session['revoked_at'])) {
            return __('sessions.active');
        }
        return __('sessions.closed');
    }

    private function resolveDisplayReason(string $reason): string
    {
        return match ($reason) {
            'logout' => __('sessions.reason_logout'),
            'user_revoke', 'user_revoke_others' => __('sessions.reason_user'),
            'admin_revoke', 'admin_revoke_user_all' => __('sessions.reason_admin'),
            'password_change', 'required_password_change', 'admin_password_change' => __('sessions.reason_password_change'),
            'password_reset' => __('sessions.reason_password_reset'),
            'mfa_change' => __('sessions.reason_mfa_change'),
            'email_change' => __('sessions.reason_email_change'),
            default => $reason !== '' ? $reason : __('sessions.reason_unknown'),
        };
    }
}
