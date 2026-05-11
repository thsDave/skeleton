<?php
$_tpUser   = $authUser ?? \Core\Auth::user();
$_tpAvatar = current_user_avatar_url($_tpUser);
$_tpTheme  = \Core\Auth::check() ? \Core\Auth::theme() : 'light'; // 'light'|'dark'|'default'
$_tpThemeIcon = match($_tpTheme) {
    'dark'    => 'ph-moon',
    'default' => 'ph-circle-half',
    default   => 'ph-sun',
};
?>
<header class="pc-header">
  <div class="header-wrapper">
    <div class="me-auto pc-mob-drp">
      <ul class="list-unstyled">
        <li class="pc-h-item pc-sidebar-collapse">
          <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
            <i class="ti ti-menu-2"></i>
          </a>
        </li>
        <li class="pc-h-item pc-sidebar-popup">
          <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
            <i class="ti ti-menu-2"></i>
          </a>
        </li>
      </ul>
    </div>

    <div class="ms-auto">
      <ul class="list-unstyled d-flex align-items-center mb-0">

        <!-- ── Selector de tema ─────────────────────────────── -->
        <li class="dropdown pc-h-item me-1">
          <a class="pc-head-link dropdown-toggle arrow-none me-0"
             data-bs-toggle="dropdown" href="#" role="button"
             title="<?= __('theme.title') ?>">
            <i class="ph-duotone <?= $_tpThemeIcon ?>" style="font-size:1.2rem;"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-end" style="min-width:160px;">
            <h6 class="dropdown-header"><?= __('theme.title') ?></h6>
            <a href="#" class="dropdown-item <?= $_tpTheme === 'light'   ? 'active' : '' ?>"
               onclick="setTheme('light');   return false;">
              <i class="ph-duotone ph-sun me-2"></i><?= __('theme.light') ?>
            </a>
            <a href="#" class="dropdown-item <?= $_tpTheme === 'dark'    ? 'active' : '' ?>"
               onclick="setTheme('dark');    return false;">
              <i class="ph-duotone ph-moon me-2"></i><?= __('theme.dark') ?>
            </a>
            <a href="#" class="dropdown-item <?= $_tpTheme === 'default' ? 'active' : '' ?>"
               onclick="setTheme('default'); return false;">
              <i class="ph-duotone ph-circle-half me-2"></i><?= __('theme.default') ?>
            </a>
          </div>
        </li>
        <!-- ── / Selector de tema ──────────────────────────── -->

        <!-- ── Menú de usuario ───────────────────────────────── -->
        <li class="dropdown pc-h-item">
          <a class="pc-head-link dropdown-toggle arrow-none me-0 d-flex align-items-center gap-2"
             data-bs-toggle="dropdown" href="#" role="button">
            <img src="<?= $_tpAvatar ?>"
                 alt="avatar"
                 class="rounded-circle"
                 style="width:36px;height:36px;object-fit:cover;"
                 onerror="this.src='<?= BASE_URL ?>/assets/images/user/avatar-1.jpg'" />
          </a>
          <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
            <?php if (\Core\Auth::isAdmin()): ?>
            <span class="dropdown-item-text text-muted small">
              <i class="ph-duotone ph-shield-check me-1 text-primary"></i><?= __('common.administrator') ?>
            </span>
            <div class="dropdown-divider"></div>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/profile" class="dropdown-item">
              <i class="ph-duotone ph-user-circle me-2"></i> <?= __('menu.my_profile') ?>
            </a>
            <a href="<?= BASE_URL ?>/account" class="dropdown-item">
              <i class="ph-duotone ph-gear me-2"></i> <?= __('menu.my_account_link') ?>
            </a>
            <?php if (\Core\Auth::isAdmin()): ?>
            <a href="<?= BASE_URL ?>/users" class="dropdown-item">
              <i class="ph-duotone ph-users-three me-2"></i> <?= __('menu.users') ?>
            </a>
            <?php endif; ?>
            <hr class="dropdown-divider" />
            <form action="<?= BASE_URL ?>/logout" method="POST" class="d-inline">
              <?= \Core\CSRF::field() ?>
              <button type="submit" class="dropdown-item text-danger">
                <i class="ph-duotone ph-sign-out me-2"></i> <?= __('auth.logout') ?>
              </button>
            </form>
          </div>
        </li>
        <!-- ── / Menú de usuario ─────────────────────────────── -->

      </ul>
    </div>
  </div>
</header>

<script>
/**
 * setTheme — cambia el tema inmediatamente en el DOM y lo guarda en
 * la sesión/BD vía AJAX (POST /profile/theme con token CSRF).
 */
function setTheme(theme) {
  // 1. Aplicar visualmente de inmediato
  var resolved = theme;
  if (theme === 'default') {
    resolved = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
               ? 'dark' : 'light';
  }
  layout_change(resolved);
  if (typeof syncBsTheme === 'function') syncBsTheme(resolved);

  // 2. Guardar en servidor (sin recargar la página)
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  fetch('<?= BASE_URL ?>/profile/theme', {
    method : 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body   : '_csrf_token=' + encodeURIComponent(csrf)
           + '&theme='      + encodeURIComponent(theme)
  })
  .then(function (r) { return r.json(); })
  .then(function (data) {
    if (data.success) {
      // Actualizar icono del botón del topbar
      var icons = { light: 'ph-sun', dark: 'ph-moon', default: 'ph-circle-half' };
      var btn = document.querySelector('.pc-header .dropdown .ph-sun, .pc-header .dropdown .ph-moon, .pc-header .dropdown .ph-circle-half');
      if (btn) {
        btn.className = btn.className.replace(/ph-(sun|moon|circle-half)/, icons[theme] || 'ph-sun');
      }
      // Actualizar ítem activo en el dropdown
      document.querySelectorAll('.pc-header .dropdown-item[onclick]').forEach(function (el) {
        el.classList.remove('active');
      });
      var activeEl = document.querySelector('.pc-header .dropdown-item[onclick*="\'' + theme + '\'"]');
      if (activeEl) activeEl.classList.add('active');
    }
  })
  .catch(function () { /* silencioso — el tema ya se aplicó visualmente */ });
}
</script>
