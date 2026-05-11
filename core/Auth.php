<?php

namespace Core;

class Auth
{
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id',                    $user['id']);
        Session::set('user_email',                 $user['email']);
        Session::set('user_name',                  $user['nombres'] . ' ' . $user['apellidos']);
        Session::set('user_nombres',               $user['nombres']);
        Session::set('user_apellidos',             $user['apellidos']);
        Session::set('user_role_slug',             $user['role_slug']   ?? 'user');
        Session::set('user_role_name',             $user['role_name']   ?? 'Usuario');
        Session::set('user_profile_image',         $user['profile_image'] ?? null);
        Session::set('user_theme',                 $user['theme_preference'] ?? 'light');
        Session::set('user_lang',                  $user['lang_code'] ?? 'es');
        Session::set('user_lang_id',               $user['language_id'] ?? null);
        Session::set('user_force_password_change', (int)($user['force_password_change'] ?? 0));
        Session::set('user_password_changed_at',   $user['password_changed_at'] ?? null);
        Session::set('_last_activity',             time());
        self::loadPermissions();
    }

    public static function check(): bool
    {
        if (!Session::has('user_id')) {
            return false;
        }
        return Session::checkTimeout();
    }

    public static function requireAuth(): void
    {
        // Pending 2FA state: partially authenticated — redirect to challenge, not login
        if (Session::has('pending_2fa_user_id') && !Session::has('user_id')) {
            Redirect::to('/two-factor/challenge');
            exit;
        }

        // Capture last activity BEFORE check() updates it
        $lastActivity = Session::get('_last_activity', time());

        if (!self::check()) {
            Redirect::to('/login');
            exit;
        }

        self::checkSessionLock($lastActivity);
        self::checkPasswordChangeRequired();
    }

    private static function checkPasswordChangeRequired(): void
    {
        $uri = self::currentUri();
        foreach (['/account/password/required-change', '/logout', '/lock', '/unlock'] as $exempt) {
            if ($uri === $exempt || str_starts_with($uri, $exempt . '/')) {
                return;
            }
        }

        if ((int)Session::get('user_force_password_change', 0) === 1) {
            Session::set('pwd_change_reason', 'forced');
            Redirect::to('/account/password/required-change');
            exit;
        }

        $checkedAt = (int)Session::get('_pwd_exp_at', 0);
        if ((time() - $checkedAt) >= 60) {
            $expired = self::isPasswordExpiredInternal();
            Session::set('_pwd_exp_result', $expired ? 1 : 0);
            Session::set('_pwd_exp_at', time());
        } else {
            $expired = (int)Session::get('_pwd_exp_result', 0) === 1;
        }

        if ($expired) {
            Session::set('pwd_change_reason', 'expired');
            Redirect::to('/account/password/required-change');
            exit;
        }
    }

    private static function isPasswordExpiredInternal(): bool
    {
        try {
            $policy         = (new \App\Services\PasswordPolicyService())->getPolicy();
            if (empty($policy['is_enabled'])) {
                return false;
            }
            $expirationDays = (int)($policy['password_expiration_days'] ?? 0);
            if ($expirationDays <= 0) {
                return false;
            }
            $changedAt = Session::get('user_password_changed_at');
            if ($changedAt === null) {
                return true;
            }
            $changedAtTs = strtotime($changedAt);
            if ($changedAtTs === false) {
                return true;
            }
            return (int)floor((time() - $changedAtTs) / 86400) >= $expirationDays;
        } catch (\Throwable $e) {
            \Core\Logger::error('Auth::isPasswordExpiredInternal — ' . $e->getMessage());
            return false;
        }
    }

    private static function currentUri(): string
    {
        $uri       = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }
        $uri = '/' . ltrim($uri, '/');
        return rtrim($uri, '/') ?: '/';
    }

    public static function requiresPasswordChange(): bool
    {
        if ((int)Session::get('user_force_password_change', 0) === 1) {
            return true;
        }
        $checkedAt = (int)Session::get('_pwd_exp_at', 0);
        if ((time() - $checkedAt) < 60 && Session::has('_pwd_exp_result')) {
            return (int)Session::get('_pwd_exp_result', 0) === 1;
        }
        return self::isPasswordExpiredInternal();
    }

    public static function clearPasswordChangeRequired(): void
    {
        Session::set('user_force_password_change', 0);
        Session::set('user_password_changed_at',   date('Y-m-d H:i:s'));
        Session::delete('pwd_change_reason');
        Session::delete('_pwd_exp_result');
        Session::delete('_pwd_exp_at');
    }

    private static function checkSessionLock(int $lastActivity): void
    {
        if (Session::get('is_locked', false)) {
            if (!Session::has('intended_url')) {
                Session::set('intended_url', self::sanitizeIntendedUrl());
            }
            Redirect::to('/lock');
            exit;
        }

        $settings = self::loadSecuritySettings();
        if (!$settings['session_lock_enabled']) {
            return;
        }

        $elapsed = time() - $lastActivity;
        if ($elapsed > (int)$settings['session_inactivity_seconds']) {
            Session::set('is_locked', true);
            Session::set('locked_at', time());
            Session::set('intended_url', self::sanitizeIntendedUrl());
            Redirect::to('/lock');
            exit;
        }
    }

    public static function loadSecuritySettings(): array
    {
        $cached   = Session::get('_sec_settings');
        $cachedAt = Session::get('_sec_settings_at', 0);

        if ($cached !== null && (time() - $cachedAt) < 60) {
            return $cached;
        }

        try {
            $model    = new \App\Models\SecuritySetting();
            $settings = $model->getSettings();
        } catch (\Throwable $e) {
            $settings = ['session_lock_enabled' => 1, 'session_inactivity_seconds' => 900];
        }

        Session::set('_sec_settings', $settings);
        Session::set('_sec_settings_at', time());
        return $settings;
    }

    private static function sanitizeIntendedUrl(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (!$uri) {
            return '/dashboard';
        }

        $uri = parse_url($uri, PHP_URL_PATH) ?: '';

        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = '/' . ltrim($uri, '/');
        $uri = rtrim($uri, '/') ?: '/';

        if (preg_match('#^//#', $uri) || str_contains($uri, '://')) {
            return '/dashboard';
        }

        if (in_array(strtolower($uri), ['/lock', '/unlock'], true)) {
            return '/dashboard';
        }

        return $uri;
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            Redirect::to('/dashboard');
            exit;
        }
        // Prevent navigating back to login while 2FA challenge is pending
        if (Session::has('pending_2fa_user_id')) {
            Redirect::to('/two-factor/challenge');
            exit;
        }
    }

    public static function isAdmin(): bool
    {
        return Session::get('user_role_slug') === 'administrator';
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            Session::flash('error', 'Acceso denegado. No tienes permisos para acceder a esa sección.');
            Redirect::to('/dashboard');
            exit;
        }
    }

    // ── Sistema de permisos ───────────────────────────────────────────────────

    /**
     * Checks whether the authenticated user has the given permission slug.
     * If the permissions cache is absent (existing session pre-migration),
     * they are loaded automatically on first call.
     */
    public static function can(string $permission): bool
    {
        $permissions = Session::get('user_permissions');
        if ($permissions === null) {
            self::loadPermissions();
            $permissions = Session::get('user_permissions', []);
        }
        return in_array($permission, (array)$permissions, true);
    }

    /**
     * Loads permission slugs for the current user's role into the session.
     * Silently sets an empty array on any DB failure (tables may not exist yet).
     */
    public static function loadPermissions(): void
    {
        $roleSlug = Session::get('user_role_slug');
        if (!$roleSlug) {
            Session::set('user_permissions', []);
            return;
        }
        try {
            $model = new \App\Models\RolePermission();
            $slugs = $model->getPermissionSlugsByRoleSlug($roleSlug);
            Session::set('user_permissions', $slugs);
        } catch (\Throwable) {
            Session::set('user_permissions', []);
        }
    }

    /**
     * Forces a fresh permissions load from the DB.
     * Call this after updating role permissions to reflect changes immediately.
     */
    public static function refreshPermissions(): void
    {
        Session::delete('user_permissions');
        self::loadPermissions();
    }

    /**
     * Requires the user to be authenticated AND to have the given permission.
     * On failure: sets HTTP 403, renders the 403 view, and exits.
     */
    public static function requirePermission(string $permission): void
    {
        self::requireAuth();

        if (!self::can($permission)) {
            \Core\Logger::security(
                "Acceso denegado: permiso '{$permission}' requerido — usuario ID "
                . (self::id() ?? 'anon')
                . ' IP ' . ($_SERVER['REMOTE_ADDR'] ?? '')
            );

            // Audit: log denied access (catch-all to avoid loops)
            try {
                \Core\Audit::log([
                    'module'      => explode('.', $permission)[0] ?? null,
                    'action'      => 'access_denied',
                    'description' => "Acceso denegado: permiso '{$permission}' requerido",
                    'status'      => 'denied',
                ]);
            } catch (\Throwable) { /* never break the flow */ }

            http_response_code(403);
            $authUser   = self::user();
            $pageTitle  = '403 — Acceso Denegado';
            $activeMenu = '';
            require dirname(__DIR__) . '/app/Views/errors/403.php';
            exit;
        }
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function user(): array
    {
        return [
            'id'            => Session::get('user_id'),
            'email'         => Session::get('user_email'),
            'name'          => Session::get('user_name'),
            'nombres'       => Session::get('user_nombres'),
            'apellidos'     => Session::get('user_apellidos'),
            'role_slug'     => Session::get('user_role_slug'),
            'role_name'     => Session::get('user_role_name'),
            'profile_image' => Session::get('user_profile_image'),
            'theme'         => Session::get('user_theme', 'light'),
            'lang'          => Session::get('user_lang', 'es'),
            'lang_id'       => Session::get('user_lang_id'),
        ];
    }

    public static function theme(): string
    {
        return Session::get('user_theme', 'light');
    }

    public static function lang(): string
    {
        return Session::get('user_lang', 'es');
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function updateSession(array $data): void
    {
        if (isset($data['email'])) {
            Session::set('user_email', $data['email']);
        }
        if (isset($data['nombres'])) {
            Session::set('user_nombres', $data['nombres']);
            Session::set('user_name', $data['nombres'] . ' ' . Session::get('user_apellidos'));
        }
        if (isset($data['apellidos'])) {
            Session::set('user_apellidos', $data['apellidos']);
            Session::set('user_name', Session::get('user_nombres') . ' ' . $data['apellidos']);
        }
        if (array_key_exists('profile_image', $data)) {
            Session::set('user_profile_image', $data['profile_image']);
        }
        if (isset($data['theme'])) {
            Session::set('user_theme', $data['theme']);
        }
        if (isset($data['lang'])) {
            Session::set('user_lang', $data['lang']);
        }
        if (array_key_exists('lang_id', $data)) {
            Session::set('user_lang_id', $data['lang_id']);
        }
        if (isset($data['force_password_change'])) {
            Session::set('user_force_password_change', (int)$data['force_password_change']);
        }
        if (isset($data['password_changed_at'])) {
            Session::set('user_password_changed_at', $data['password_changed_at']);
            Session::delete('_pwd_exp_result');
            Session::delete('_pwd_exp_at');
        }
    }
}
