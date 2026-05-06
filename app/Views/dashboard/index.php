<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
require dirname(__DIR__) . '/layouts/main.php';
?>

<!-- [ breadcrumb ] start -->
<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">Dashboard</h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active">Inicio</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>
<!-- [ breadcrumb ] end -->

<!-- [ Main Content ] start -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="flex-shrink-0">
            <div class="avtar avtar-xl bg-light-primary">
              <i class="ph-duotone ph-user-circle text-primary" style="font-size:3rem;"></i>
            </div>
          </div>
          <div class="flex-grow-1 ms-3">
            <h4 class="mb-1">
              ¡Bienvenido, <?= htmlspecialchars($authUser['nombres'] ?? $authUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>!
            </h4>
            <p class="text-muted mb-0">Has iniciado sesión correctamente. Sesión activa como <strong><?= htmlspecialchars($authUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-4 mb-3">
    <div class="card prod-p-card">
      <div class="card-body">
        <div class="row align-items-center m-b-0">
          <div class="col">
            <p class="mb-1 text-muted">Mi Perfil</p>
            <h5 class="mb-0">Información Personal</h5>
            <p class="text-muted small mt-1 mb-2">Nombres, apellidos, teléfono y dirección.</p>
            <a href="<?= BASE_URL ?>/profile" class="btn btn-sm btn-outline-primary">
              <i class="ph-duotone ph-user-circle me-1"></i> Ver Perfil
            </a>
          </div>
          <div class="col-auto">
            <i class="ph-duotone ph-user-circle text-primary f-30" style="font-size:2.5rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4 mb-3">
    <div class="card prod-p-card">
      <div class="card-body">
        <div class="row align-items-center m-b-0">
          <div class="col">
            <p class="mb-1 text-muted">Mi Cuenta</p>
            <h5 class="mb-0">Credenciales de Acceso</h5>
            <p class="text-muted small mt-1 mb-2">Correo electrónico y contraseña.</p>
            <a href="<?= BASE_URL ?>/account" class="btn btn-sm btn-outline-warning">
              <i class="ph-duotone ph-gear me-1"></i> Mi Cuenta
            </a>
          </div>
          <div class="col-auto">
            <i class="ph-duotone ph-gear text-warning f-30" style="font-size:2.5rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4 mb-3">
    <div class="card prod-p-card">
      <div class="card-body">
        <div class="row align-items-center m-b-0">
          <div class="col">
            <p class="mb-1 text-muted">Seguridad</p>
            <h5 class="mb-0">Sesión Activa</h5>
            <p class="text-muted small mt-1 mb-2">Sesión protegida con timeout de 30 min.</p>
            <form action="<?= BASE_URL ?>/logout" method="POST" class="d-inline">
              <?= \Core\CSRF::field() ?>
              <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="ph-duotone ph-sign-out me-1"></i> Cerrar Sesión
              </button>
            </form>
          </div>
          <div class="col-auto">
            <i class="ph-duotone ph-shield-check text-success f-30" style="font-size:2.5rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- [ Main Content ] end -->

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
