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
          <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button">
            <i class="ph-duotone ph-user-circle" style="font-size:1.5rem;"></i>
            <span class="ms-2 d-none d-md-inline"><?= htmlspecialchars($authUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
            <a href="<?= BASE_URL ?>/profile" class="dropdown-item">
              <i class="ph-duotone ph-user-circle me-2"></i> Mi Perfil
            </a>
            <a href="<?= BASE_URL ?>/account" class="dropdown-item">
              <i class="ph-duotone ph-gear me-2"></i> Mi Cuenta
            </a>
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
