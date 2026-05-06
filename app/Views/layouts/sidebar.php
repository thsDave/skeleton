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

        <?php if (\Core\Auth::isAdmin()): ?>
        <li class="pc-item pc-caption">
          <label>Administración</label>
        </li>

        <li class="pc-item <?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">
          <a href="<?= BASE_URL ?>/users" class="pc-link">
            <span class="pc-micon"><i class="ph-duotone ph-users-three"></i></span>
            <span class="pc-mtext">Usuarios</span>
          </a>
        </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
