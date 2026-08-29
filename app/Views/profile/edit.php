<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = __('profile.edit_profile');
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
          <h5 class="mb-0"><?= __('profile.edit_profile') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/profile"><?= __('profile.title') ?></a></li>
            <li class="breadcrumb-item active"><?= __('buttons.edit') ?></li>
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
        <h5 class="mb-0"><i class="ph-duotone ph-pencil me-2 text-primary"></i><?= __('profile.edit_info') ?></h5>
      </div>
      <div class="card-body">
        <?php
        $_editImg = $user['profile_image'] ?? null;
        $_editAvatar = $_editImg
            ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($_editImg, ENT_QUOTES, 'UTF-8')
            : null;
        ?>
        <form action="<?= BASE_URL ?>/profile/update" method="POST" enctype="multipart/form-data" novalidate>
          <?= CSRF::field() ?>

          <!-- Imagen de perfil -->
          <div class="mb-4 text-center">
            <div class="mb-2">
              <?php if ($_editAvatar): ?>
                <img src="<?= $_editAvatar ?>"
                     alt="avatar actual"
                     id="avatarPreview"
                     class="rounded-circle"
                     style="width:90px;height:90px;object-fit:cover;"
                     onerror="this.src=''">
              <?php else: ?>
                <div class="avtar bg-light-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-1"
                     id="avatarFallback"
                     style="width:90px;height:90px;">
                  <i class="ph-duotone ph-user-circle text-primary" style="font-size:3.5rem;"></i>
                </div>
                <img id="avatarPreview" src="" alt="" class="rounded-circle d-none"
                     style="width:90px;height:90px;object-fit:cover;">
              <?php endif; ?>
            </div>
            <label for="profile_image" class="form-label fw-semibold d-block">
              <?= __('profile.profile_image') ?> <span class="text-muted small fw-normal">(<?= __('profile.image_hint') ?>)</span>
            </label>
            <input type="file"
                   name="profile_image"
                   id="profile_image"
                   class="form-control <?= isset($errors['profile_image']) ? 'is-invalid' : '' ?>"
                   accept=".jpg,.jpeg,.png,.webp"
                   onchange="previewAvatar(this)">
            <?php if (isset($errors['profile_image'])): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['profile_image'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="nombres" class="form-label fw-semibold">
                <?= __('profile.first_names') ?> <span class="text-danger">*</span>
              </label>
              <input
                type="text"
                name="nombres"
                id="nombres"
                class="form-control <?= isset($errors['nombres']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['nombres'] ?? $user['nombres'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                maxlength="100"
                required
                placeholder="<?= __('profile.names_placeholder') ?>"
              />
              <?php if (isset($errors['nombres'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['nombres'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-6 mb-3">
              <label for="apellidos" class="form-label fw-semibold">
                <?= __('profile.last_names') ?> <span class="text-danger">*</span>
              </label>
              <input
                type="text"
                name="apellidos"
                id="apellidos"
                class="form-control <?= isset($errors['apellidos']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['apellidos'] ?? $user['apellidos'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                maxlength="100"
                required
                placeholder="<?= __('profile.lastnames_placeholder') ?>"
              />
              <?php if (isset($errors['apellidos'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="telefono" class="form-label fw-semibold"><?= __('profile.phone') ?></label>
            <div class="input-group">
              <span class="input-group-text"><i data-feather="phone"></i></span>
              <input
                type="text"
                name="telefono"
                id="telefono"
                class="form-control <?= isset($errors['telefono']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['telefono'] ?? $user['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                maxlength="25"
                placeholder="<?= __('profile.phone_placeholder') ?>"
              />
              <?php if (isset($errors['telefono'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['telefono'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-4">
            <label for="direccion" class="form-label fw-semibold"><?= __('profile.address') ?></label>
            <textarea
              name="direccion"
              id="direccion"
              class="form-control <?= isset($errors['direccion']) ? 'is-invalid' : '' ?>"
              rows="3"
              maxlength="255"
              placeholder="<?= __('profile.address_placeholder') ?>"
            ><?= htmlspecialchars($old['direccion'] ?? $user['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (isset($errors['direccion'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['direccion'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-floppy-disk me-1"></i> <?= __('buttons.save') ?>
            </button>
            <a href="<?= BASE_URL ?>/profile" class="btn btn-outline-secondary">
              <i class="ph-duotone ph-x me-1"></i> <?= __('buttons.cancel') ?>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
function previewAvatar(input) {
  if (!input.files || !input.files[0]) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var preview = document.getElementById('avatarPreview');
    var fallback = document.getElementById('avatarFallback');
    preview.src = e.target.result;
    preview.classList.remove('d-none');
    if (fallback) fallback.classList.add('d-none');
  };
  reader.readAsDataURL(input.files[0]);
}
</script>
JS;
?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
