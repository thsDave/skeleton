<?php
$_sidebarAppearance = (new \App\Models\AppearanceSetting())->get();
$_appName  = htmlspecialchars($_sidebarAppearance['app_display_name'] ?? 'Skeleton', ENT_QUOTES, 'UTF-8');
$_logoPath = $_sidebarAppearance['logo_path'] ?? null;
$_logoUrl  = $_logoPath ? BASE_URL . '/uploads/appearance/logo/' . htmlspecialchars($_logoPath, ENT_QUOTES, 'UTF-8') : null;
// Iniciales para logo-sm (primeras 2 letras del nombre)
$_initials = mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $_appName), 0, 2));
?>
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header">
      <a href="<?= BASE_URL ?>/dashboard" class="b-brand text-primary">
        <div class="d-flex align-items-center gap-2">
          <?php if ($_logoUrl): ?>
            <img src="<?= $_logoUrl ?>" alt="<?= $_appName ?>"
                 class="logo-lg" style="max-height:36px;max-width:140px;object-fit:contain;">
            <img src="<?= $_logoUrl ?>" alt="<?= $_appName ?>"
                 class="logo-sm" style="max-height:36px;max-width:36px;object-fit:contain;">
          <?php else: ?>
            <i class="ph-duotone ph-shield-check text-white" style="font-size:1.8rem;"></i>
            <span class="logo-lg text-white fw-bold fs-5"><?= $_appName ?></span>
            <span class="logo-sm text-white fw-bold fs-5"><?= $_initials ?></span>
          <?php endif; ?>
        </div>
      </a>
    </div>
    <div class="navbar-content">
      <ul class="pc-navbar">

        <li class="pc-item pc-caption">
          <label><?= __('menu.navigation') ?></label>
        </li>

        <?php if (can('dashboard.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/dashboard" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-gauge"></i></span>
            <span class="pc-mtext"><?= __('menu.dashboard') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <li class="pc-item pc-caption">
          <label><?= __('menu.my_account') ?></label>
        </li>

        <?php if (can('profile.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'profile' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/profile" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-user-circle"></i></span>
            <span class="pc-mtext"><?= __('menu.my_profile') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('account.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'account' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/account" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-gear"></i></span>
            <span class="pc-mtext"><?= __('menu.my_account_link') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('system_information.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'system_information' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/system-information" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-info"></i></span>
            <span class="pc-mtext"><?= __('menu.system_information') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php
        $showAdmin = can('users.view') || can('languages.view') || can('appearance.view');
        if ($showAdmin):
        ?>
        <li class="pc-item pc-caption">
          <label><?= __('menu.administration') ?></label>
        </li>
        <?php endif; ?>

        <?php if (can('users.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/users" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-users-three"></i></span>
            <span class="pc-mtext"><?= __('menu.users') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('languages.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'languages' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/languages" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-translate"></i></span>
            <span class="pc-mtext"><?= __('menu.languages') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('appearance.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'appearance' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/appearance" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-palette"></i></span>
            <span class="pc-mtext"><?= __('menu.appearance') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php
        $showSecurity = can('security_sessions.view') || can('roles_permissions.view') || can('audit_logs.view') || can('security_smtp.view') || can('security_mfa.view') || can('security_attempts.view') || can('security_authentication.view');
        if ($showSecurity):
        ?>
        <li class="pc-item pc-caption">
          <label><?= __('menu.security') ?></label>
        </li>
        <?php endif; ?>

        <?php if (can('security_sessions.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'security_sessions' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/security/sessions" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-lock-key"></i></span>
            <span class="pc-mtext"><?= __('menu.security_sessions') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('roles_permissions.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'roles_permissions' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/roles-permissions" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-shield-check"></i></span>
            <span class="pc-mtext"><?= __('menu.roles_permissions') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('security_smtp.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'security_smtp' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/security/smtp" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-envelope"></i></span>
            <span class="pc-mtext"><?= __('menu.security_smtp') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('security_authentication.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'security_authentication' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/security/authentication" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-sign-in"></i></span>
            <span class="pc-mtext"><?= __('menu.security_authentication') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('security_mfa.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'security_mfa' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/security/mfa" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-shield-plus"></i></span>
            <span class="pc-mtext"><?= __('menu.security_mfa') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('security_attempts.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'security_attempts' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/security/attempts" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-shield-warning"></i></span>
            <span class="pc-mtext"><?= __('menu.security_attempts') ?></span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (can('audit_logs.view')): ?>
        <li class="pc-item <?= ($activeMenu ?? '') === 'audit_logs' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/audit-logs" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-clipboard-text"></i></span>
            <span class="pc-mtext"><?= __('menu.audit_logs') ?></span>
          </a>
        </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
