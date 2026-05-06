<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header">
      <a href="<?= BASE_URL ?>/dashboard" class="b-brand text-primary">
        <div class="d-flex align-items-center gap-2">
          <i class="ph-duotone ph-shield-check text-white" style="font-size:1.8rem;"></i>
          <span class="logo-lg text-white fw-bold fs-5">Skeleton</span>
          <span class="logo-sm text-white fw-bold fs-5">SK</span>
        </div>
      </a>
    </div>
    <div class="navbar-content">
      <ul class="pc-navbar">

        <li class="pc-item pc-caption">
          <label><?= __('menu.navigation') ?></label>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/dashboard" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-gauge"></i></span>
            <span class="pc-mtext"><?= __('menu.dashboard') ?></span>
          </a>
        </li>

        <li class="pc-item pc-caption">
          <label><?= __('menu.my_account') ?></label>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'profile' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/profile" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-user-circle"></i></span>
            <span class="pc-mtext"><?= __('menu.my_profile') ?></span>
          </a>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'account' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/account" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-gear"></i></span>
            <span class="pc-mtext"><?= __('menu.my_account_link') ?></span>
          </a>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'system_information' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/system-information" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-info"></i></span>
            <span class="pc-mtext"><?= __('menu.system_information') ?></span>
          </a>
        </li>

        <?php if (\Core\Auth::isAdmin()): ?>
        <li class="pc-item pc-caption">
          <label><?= __('menu.administration') ?></label>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/users" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-users-three"></i></span>
            <span class="pc-mtext"><?= __('menu.users') ?></span>
          </a>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'languages' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/languages" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-translate"></i></span>
            <span class="pc-mtext"><?= __('menu.languages') ?></span>
          </a>
        </li>

        <li class="pc-item pc-caption">
          <label><?= __('menu.security') ?></label>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'security_sessions' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/security/sessions" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-lock-key"></i></span>
            <span class="pc-mtext"><?= __('menu.security_sessions') ?></span>
          </a>
        </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
