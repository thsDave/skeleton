<?php

namespace App\Controllers;

use Core\Audit;
use Core\Auth;
use Core\Session;
use Core\CSRF;
use Core\Redirect;
use App\Models\SecuritySetting;
use App\Services\UserSessionService;

class SecurityController
{
    public function sessions(): void
    {
        Auth::requirePermission('security_sessions.view');

        $authUser = Auth::user();
        $model    = new SecuritySetting();
        $settings = $model->getSettings();
        $activeSessions = can('security_sessions.view_active')
            ? (new UserSessionService())->getActiveSessionsForAdmin()
            : [];

        require dirname(__DIR__) . '/Views/security/sessions/index.php';
    }

    public function updateSessions(): void
    {
        Auth::requirePermission('security_sessions.edit');
        CSRF::validateOrFail();

        $enabled = isset($_POST['session_lock_enabled']) ? 1 : 0;
        $seconds = (int) ($_POST['session_inactivity_seconds'] ?? 900);

        if ($seconds < 60)    $seconds = 60;
        if ($seconds > 86400) $seconds = 86400;

        $model = new SecuritySetting();
        if ($model->updateSettings((bool) $enabled, $seconds)) {
            Session::delete('_sec_settings');
            Session::delete('_sec_settings_at');
            Audit::log(['module' => 'security_sessions', 'action' => 'security_sessions.settings_updated',
                'description' => 'Configuración de sesiones actualizada',
                'new_values' => ['session_lock_enabled' => $enabled, 'session_inactivity_seconds' => $seconds],
                'status' => 'success']);
            Session::flash('success', __('security.sessions.updated'));
        } else {
            Session::flash('error', __('alerts.internal'));
        }

        Redirect::to('/security/sessions');
        exit;
    }

    public function revokeSession(string $id): void
    {
        Auth::requirePermission('security_sessions.revoke');
        CSRF::validateOrFail();

        $service = new UserSessionService();
        $session = $service->findActiveSession((int)$id);
        if (!$session) {
            Redirect::withError('/security/sessions', __('sessions.closed_error'));
        }

        if (hash_equals((string)$session['session_hash'], $service->getCurrentSessionHash())) {
            Redirect::withError('/security/sessions', __('sessions.closed_error'));
        }

        $ok = $service->revokeSession((int)$id, Auth::id(), 'admin_revoke');
        Session::flash($ok ? 'success' : 'error', $ok ? __('sessions.closed_success') : __('sessions.closed_error'));
        Redirect::to('/security/sessions');
    }

    public function revokeUserSessions(string $userId): void
    {
        Auth::requirePermission('security_sessions.revoke_user_all');
        CSRF::validateOrFail();

        $targetUserId = (int)$userId;
        $keepCurrent = $targetUserId === (int)Auth::id();
        $count = (new UserSessionService())->revokeAllUserSessions($targetUserId, Auth::id(), 'admin_revoke_user_all', $keepCurrent);
        Session::flash('success', __('sessions.closed_all_success', ['count' => (string)$count]));
        Redirect::to('/security/sessions');
    }
}
