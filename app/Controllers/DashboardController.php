<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Language;
use App\Models\Module;
use App\Models\Permission;
use App\Models\SmtpSettings;
use App\Models\MfaSettings;
use App\Models\SecuritySetting;
use App\Models\SystemSetting;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requirePermission('dashboard.view');
        $authUser = Auth::user();

        $isAdmin       = ($authUser['role_slug'] ?? '') === 'administrator';
        $canViewUsers  = can('users.view');
        $canViewAudit  = can('audit_logs.view');
        $canViewMfa    = can('security_mfa.view');
        $canViewSmtp   = can('security_smtp.view');
        $showAdminDash = $isAdmin || $canViewUsers;

        $userModel       = new User();
        $freshUser       = $userModel->findById((int) $authUser['id']);
        $sessionSettings = (new SecuritySetting())->getSettings();

        // Admin-only data (only queried when needed)
        $userStats        = [];
        $roleStats        = [];
        $mfaMethodStats   = [];
        $lastUser         = null;
        $smtpData         = [];
        $mfaSettings      = [];
        $systemSettings   = [];
        $activeLanguages  = 0;
        $defaultLanguage  = null;
        $activeModules    = 0;
        $permissionsCount = 0;

        if ($showAdminDash) {
            $userStats      = $userModel->getDashboardStats();
            $roleStats      = $userModel->getStatsByRole();
            $mfaMethodStats = $userModel->getMfaMethodStats();
            $lastUser       = $userModel->getLastRegistered();

            $smtpData    = (new SmtpSettings())->get();
            $mfaSettings = (new MfaSettings())->get();

            $systemSettings  = (new SystemSetting())->get() ?: [];
            $langModel       = new Language();
            $activeLanguages = $langModel->getActiveCount();
            $defaultLanguage = $langModel->getDefaultLanguage();

            $activeModules    = (new Module())->getActiveCount();
            $permissionsCount = (new Permission())->getCount();
        }

        $recentActivity = [];
        if ($canViewAudit) {
            $recentActivity = (new AuditLog())->getRecent(10);
        }

        $pageTitle  = __('dashboard.title');
        $activeMenu = 'dashboard';
        require dirname(__DIR__) . '/Views/dashboard/index.php';
    }
}
