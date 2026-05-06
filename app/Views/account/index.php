<?php
$pageTitle  = 'Mi Cuenta';
$activeMenu = 'account';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">Mi Cuenta</h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Inicio</a></li>
            <li class="breadcrumb-item active">Mi Cuenta</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<?php $_accStatus = $user['status_slug'] ?? $user['status'] ?? ''; ?>

<div class="row">
  <!-- Correo electrónico -->
  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="ph-duotone ph-envelope me-2 text-primary"></i>Correo Electrónico
        </h5>
      </div>
      <div class="card-body d-flex flex-column">
        <p class="text-muted mb-1 small">Correo actual</p>
        <h6 class="mb-3"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></h6>
        <p class="text-muted small flex-grow-1">
          Tu correo electrónico se usa para iniciar sesión. Al cambiarlo, deberás usar el nuevo correo en tu próximo acceso.
        </p>
        <div>
          <a href="<?= BASE_URL ?>/account/edit-email" class="btn btn-primary btn-sm">
            <i class="ph-duotone ph-pencil me-1"></i> Cambiar Correo
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Contraseña -->
  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="ph-duotone ph-lock-key me-2 text-warning"></i>Contraseña
        </h5>
      </div>
      <div class="card-body d-flex flex-column">
        <p class="text-muted mb-1 small">Última actualización de contraseña</p>
        <h6 class="mb-3">
          <?php if (!empty($user['password_changed_at'])): ?>
            <?= htmlspecialchars(date('d/m/Y \a \l\a\s H:i', strtotime($user['password_changed_at'])), ENT_QUOTES, 'UTF-8') ?>
          <?php else: ?>
            <span class="text-muted fst-italic">Sin cambios registrados</span>
          <?php endif; ?>
        </h6>
        <p class="text-muted small flex-grow-1">
          Mantén tu contraseña segura. Usa al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.
        </p>
        <div>
          <a href="<?= BASE_URL ?>/account/edit-password" class="btn btn-warning btn-sm">
            <i class="ph-duotone ph-lock-key me-1"></i> Cambiar Contraseña
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Información de seguridad -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ph-duotone ph-shield-check me-2 text-success"></i>Información de Sesión</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-sm-6 col-md-3 mb-2">
            <p class="mb-0 text-muted small">Último acceso</p>
            <p class="mb-0 fw-semibold">
              <?= !empty($user['last_login_at'])
                ? htmlspecialchars(date('d/m/Y H:i', strtotime($user['last_login_at'])), ENT_QUOTES, 'UTF-8')
                : '<span class="text-muted">—</span>' ?>
            </p>
          </div>
          <div class="col-sm-6 col-md-3 mb-2">
            <p class="mb-0 text-muted small">IP del último acceso</p>
            <p class="mb-0 fw-semibold">
              <?= htmlspecialchars($user['last_login_ip'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </p>
          </div>
          <div class="col-sm-6 col-md-3 mb-2">
            <p class="mb-0 text-muted small">Estado de cuenta</p>
            <p class="mb-0">
              <span class="badge bg-<?= $_accStatus === 'active' ? 'success' : 'danger' ?>">
                <?= $_accStatus === 'active' ? 'Activa' : ucfirst($user['status_name'] ?? $_accStatus) ?>
              </span>
            </p>
          </div>
          <div class="col-sm-6 col-md-3 mb-2">
            <p class="mb-0 text-muted small">Timeout de sesión</p>
            <p class="mb-0 fw-semibold">30 minutos de inactividad</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
