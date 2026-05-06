<?php

namespace App\Controllers;

use Core\Auth;
use Core\Session;
use Core\CSRF;
use Core\Redirect;
use App\Models\SecuritySetting;

class SecurityController
{
    public function sessions(): void
    {
        Auth::requireAdmin();

        $model    = new SecuritySetting();
        $settings = $model->getSettings();

        require dirname(__DIR__) . '/Views/security/sessions/index.php';
    }

    public function updateSessions(): void
    {
        Auth::requireAdmin();
        CSRF::verify();

        $enabled = isset($_POST['session_lock_enabled']) ? 1 : 0;
        $seconds = (int) ($_POST['session_inactivity_seconds'] ?? 900);

        if ($seconds < 60)    $seconds = 60;
        if ($seconds > 86400) $seconds = 86400;

        $model = new SecuritySetting();
        if ($model->updateSettings((bool) $enabled, $seconds)) {
            // Invalidate cached settings so all subsequent requests pick up the new values
            Session::delete('_sec_settings');
            Session::delete('_sec_settings_at');
            Session::flash('success', __('security.sessions.updated'));
        } else {
            Session::flash('error', __('alerts.internal'));
        }

        Redirect::to('/security/sessions');
        exit;
    }
}
