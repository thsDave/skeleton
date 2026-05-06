<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = 'Cambiar Contraseña';
$activeMenu = 'account';
$errors     = Session::getFlash('errors', []);

require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">Cambiar Contraseña</h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/account">Mi Cuenta</a></li>
            <li class="breadcrumb-item active">Cambiar Contraseña</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ph-duotone ph-lock-key me-2 text-warning"></i>Cambiar Contraseña</h5>
      </div>
      <div class="card-body">
        <form action="<?= BASE_URL ?>/account/update-password" method="POST" novalidate>
          <?= CSRF::field() ?>

          <div class="mb-3">
            <label for="current_password" class="form-label fw-semibold">
              Contraseña actual <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text"><i data-feather="lock"></i></span>
              <input
                type="password"
                name="current_password"
                id="current_password"
                class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                required
                autocomplete="current-password"
                placeholder="Tu contraseña actual"
              />
              <?php if (isset($errors['current_password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['current_password'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="new_password" class="form-label fw-semibold">
              Nueva contraseña <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text"><i data-feather="key"></i></span>
              <input
                type="password"
                name="new_password"
                id="new_password"
                class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                required
                autocomplete="new-password"
                placeholder="Nueva contraseña"
              />
              <?php if (isset($errors['new_password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['new_password'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
            <div class="form-text">
              <i class="ph-duotone ph-info me-1"></i>
              Mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial (Ej: <code>!</code> <code>@</code> <code>#</code> <code>*</code>).
            </div>
          </div>

          <div class="mb-4">
            <label for="confirm_password" class="form-label fw-semibold">
              Confirmar nueva contraseña <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text"><i data-feather="check-circle"></i></span>
              <input
                type="password"
                name="confirm_password"
                id="confirm_password"
                class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                required
                autocomplete="new-password"
                placeholder="Repite la nueva contraseña"
              />
              <?php if (isset($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning">
              <i class="ph-duotone ph-lock-key me-1"></i> Actualizar Contraseña
            </button>
            <a href="<?= BASE_URL ?>/account" class="btn btn-outline-secondary">
              <i class="ph-duotone ph-x me-1"></i> Cancelar
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
