<?php
$_tpImage = $authUser['profile_image'] ?? null;
$_tpAvatar = $_tpImage
    ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($_tpImage, ENT_QUOTES, 'UTF-8')
    : BASE_URL . '/assets/images/user/avatar-1.jpg';
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
      <ul class="list-unstyled">
        <li class="dropdown pc-h-item">
          <a class="pc-head-link dropdown-toggle arrow-none me-0 d-flex align-items-center gap-2"
             data-bs-toggle="dropdown" href="#" role="button">
            <img src="<?= $_tpAvatar ?>"
                 alt="avatar"
                 class="rounded-circle"
                 style="width:36px;height:36px;object-fit:cover;"
                 onerror="this.src='<?= BASE_URL ?>/assets/images/user/avatar-1.jpg'" />
            <span class="d-none d-md-inline">
              <?= htmlspecialchars($authUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </span>
          </a>
          <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
            <?php if (\Core\Auth::isAdmin()): ?>
            <span class="dropdown-item-text text-muted small">
              <i class="ph-duotone ph-shield-check me-1 text-primary"></i>Administrador
            </span>
            <div class="dropdown-divider"></div>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/profile" class="dropdown-item">
              <i class="ph-duotone ph-user-circle me-2"></i> Mi Perfil
            </a>
            <a href="<?= BASE_URL ?>/account" class="dropdown-item">
              <i class="ph-duotone ph-gear me-2"></i> Mi Cuenta
            </a>
            <?php if (\Core\Auth::isAdmin()): ?>
            <a href="<?= BASE_URL ?>/users" class="dropdown-item">
              <i class="ph-duotone ph-users-three me-2"></i> Usuarios
            </a>
            <?php endif; ?>
            <hr class="dropdown-divider" />
            <form action="<?= BASE_URL ?>/logout" method="POST" class="d-inline">
              <?= \Core\CSRF::field() ?>
              <button type="submit" class="dropdown-item text-danger">
                <i class="ph-duotone ph-sign-out me-2"></i> Cerrar Sesión
              </button>
            </form>
          </div>
        </li>
      </ul>
    </div>
  </div>
</header>
