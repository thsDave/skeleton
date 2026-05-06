<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = 'Editar Perfil';
$activeMenu = 'profile';
$errors     = Session::getFlash('errors', []);
$old        = Session::getFlash('old', []);

require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">Editar Perfil</h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/profile">Mi Perfil</a></li>
            <li class="breadcrumb-item active">Editar</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<?php
// Mostrar errores generales (no asociados a campos)
$generalErrors = array_filter($errors, fn($k) => $k === 'general', ARRAY_FILTER_USE_KEY);
foreach ($generalErrors as $msg): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="ph-duotone ph-warning-circle me-2"></i>
    <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ph-duotone ph-pencil me-2 text-primary"></i>Editar Información Personal</h5>
      </div>
      <div class="card-body">
        <form action="<?= BASE_URL ?>/profile/update" method="POST" novalidate>
          <?= CSRF::field() ?>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="nombres" class="form-label fw-semibold">
                Nombres <span class="text-danger">*</span>
              </label>
              <input
                type="text"
                name="nombres"
                id="nombres"
                class="form-control <?= isset($errors['nombres']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['nombres'] ?? $user['nombres'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                maxlength="100"
                required
                placeholder="Tus nombres"
              />
              <?php if (isset($errors['nombres'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['nombres'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-6 mb-3">
              <label for="apellidos" class="form-label fw-semibold">
                Apellidos <span class="text-danger">*</span>
              </label>
              <input
                type="text"
                name="apellidos"
                id="apellidos"
                class="form-control <?= isset($errors['apellidos']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['apellidos'] ?? $user['apellidos'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                maxlength="100"
                required
                placeholder="Tus apellidos"
              />
              <?php if (isset($errors['apellidos'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="telefono" class="form-label fw-semibold">Teléfono</label>
            <div class="input-group">
              <span class="input-group-text"><i data-feather="phone"></i></span>
              <input
                type="text"
                name="telefono"
                id="telefono"
                class="form-control <?= isset($errors['telefono']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['telefono'] ?? $user['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                maxlength="25"
                placeholder="Ej: +503 7000-0000"
              />
              <?php if (isset($errors['telefono'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['telefono'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-4">
            <label for="direccion" class="form-label fw-semibold">Dirección</label>
            <textarea
              name="direccion"
              id="direccion"
              class="form-control <?= isset($errors['direccion']) ? 'is-invalid' : '' ?>"
              rows="3"
              maxlength="255"
              placeholder="Tu dirección completa"
            ><?= htmlspecialchars($old['direccion'] ?? $user['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (isset($errors['direccion'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['direccion'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-floppy-disk me-1"></i> Guardar Cambios
            </button>
            <a href="<?= BASE_URL ?>/profile" class="btn btn-outline-secondary">
              <i class="ph-duotone ph-x me-1"></i> Cancelar
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
