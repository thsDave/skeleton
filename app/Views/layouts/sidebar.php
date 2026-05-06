<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header">
      <a href="<?= BASE_URL ?>/dashboard" class="b-brand text-primary">
        <img src="<?= BASE_URL ?>/assets/images/logo-white.svg" alt="logo" class="logo-lg" />
        <span class="logo-sm text-white fw-bold fs-4">SK</span>
      </a>
    </div>
    <div class="navbar-content">
      <ul class="pc-navbar">

        <li class="pc-item pc-caption">
          <label>Navegación</label>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/dashboard" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-gauge"></i></span>
            <span class="pc-mtext">Dashboard</span>
          </a>
        </li>

        <li class="pc-item pc-caption">
          <label>Mi Cuenta</label>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'profile' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/profile" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-user-circle"></i></span>
            <span class="pc-mtext">Mi Perfil</span>
          </a>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'account' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/account" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-gear"></i></span>
            <span class="pc-mtext">Mi Cuenta</span>
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>
