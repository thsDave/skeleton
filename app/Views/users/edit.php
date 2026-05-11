<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = 'Editar Usuario';
$activeMenu = 'users';
$errors     = Session::getFlash('errors', []);
$old        = Session::getFlash('old', []);

require dirname(__DIR__) . '/layouts/main.php';
?>

<?php
$_editImg = $user['profile_image'] ?? null;
$_editAvatar = $_editImg
    ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($_editImg, ENT_QUOTES, 'UTF-8')
    : null;
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">Editar Usuario</h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/users">Usuarios</a></li>
            <li class="breadcrumb-item active">Editar</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
  <i class="ph-duotone ph-warning-circle me-2"></i>
  <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php
$isLockedEdit = !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
if ($isLockedEdit): ?>
<div class="alert alert-danger d-flex align-items-start gap-3 mb-3">
  <i class="ph-duotone ph-lock fs-4 flex-shrink-0 mt-1"></i>
  <div class="flex-grow-1">
    <strong><?= __('users.locked') ?></strong> —
    <?= __('users.locked_until') ?>:
    <strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime($user['locked_until'])), ENT_QUOTES, 'UTF-8') ?></strong>
    <?php if (can('users.unlock')): ?>
    <form action="<?= BASE_URL ?>/users/unlock/<?= (int)$user['id'] ?>"
          method="POST"
          class="d-inline ms-2 form-unlock-edit">
      <?= \Core\CSRF::field() ?>
      <button type="button"
              class="btn btn-sm btn-warning btn-unlock-edit"
              data-name="<?= htmlspecialchars(trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
        <i class="ph-duotone ph-lock-open me-1"></i><?= __('users.unlock') ?>
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="ph-duotone ph-pencil me-2 text-primary"></i>
          <?= htmlspecialchars(trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
        </h5>
      </div>
      <div class="card-body">
        <form action="<?= BASE_URL ?>/users/update/<?= (int)$user['id'] ?>"
              method="POST" enctype="multipart/form-data" novalidate>
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
                     onerror="this.src='<?= BASE_URL ?>/assets/images/user/avatar-1.jpg'">
              <?php else: ?>
                <div class="avtar bg-light-primary d-inline-flex align-items-center justify-content-center rounded-circle"
                     id="avatarFallback"
                     style="width:90px;height:90px;">
                  <i class="ph-duotone ph-user-circle text-primary" style="font-size:3.5rem;"></i>
                </div>
                <img id="avatarPreview" src="" alt="" class="rounded-circle d-none"
                     style="width:90px;height:90px;object-fit:cover;">
              <?php endif; ?>
            </div>
            <label for="profile_image" class="form-label fw-semibold d-block">
              Foto de Perfil <span class="text-muted small fw-normal">(Dejar vacío para no cambiar — JPG, PNG o WEBP — máx. 2 MB)</span>
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
              <label for="nombres" class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
              <input type="text" name="nombres" id="nombres"
                     class="form-control <?= isset($errors['nombres']) ? 'is-invalid' : '' ?>"
                     value="<?= htmlspecialchars($old['nombres'] ?? $user['nombres'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     maxlength="100" required placeholder="Nombres">
              <?php if (isset($errors['nombres'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['nombres'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
              <label for="apellidos" class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
              <input type="text" name="apellidos" id="apellidos"
                     class="form-control <?= isset($errors['apellidos']) ? 'is-invalid' : '' ?>"
                     value="<?= htmlspecialchars($old['apellidos'] ?? $user['apellidos'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     maxlength="100" required placeholder="Apellidos">
              <?php if (isset($errors['apellidos'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['apellidos'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Correo Electrónico <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i data-feather="mail"></i></span>
              <input type="email" name="email" id="email"
                     class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                     value="<?= htmlspecialchars($old['email'] ?? $user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     required placeholder="correo@ejemplo.com" autocomplete="off">
              <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="password" class="form-label fw-semibold">Nueva Contraseña</label>
              <input type="password" name="password" id="password"
                     class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                     placeholder="Dejar vacío para no cambiar" autocomplete="new-password">
              <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
<?php
$_eMinLen  = (int)(($policyReqs['is_enabled'] ?? 0) ? ($policyReqs['min_length'] ?? 10) : 6);
$_eUpper   = !empty($policyReqs['is_enabled']) && !empty($policyReqs['require_uppercase']);
$_eLower   = !empty($policyReqs['is_enabled']) && !empty($policyReqs['require_lowercase']);
$_eNumber  = !empty($policyReqs['is_enabled']) && !empty($policyReqs['require_number']);
$_eSpecial = !empty($policyReqs['is_enabled']) && !empty($policyReqs['require_special']);
?>
              <div id="pwd-requirements" class="mt-2 small lh-lg"
                   data-min="<?= $_eMinLen ?>"
                   data-upper="<?= $_eUpper ? '1' : '0' ?>"
                   data-lower="<?= $_eLower ? '1' : '0' ?>"
                   data-number="<?= $_eNumber ? '1' : '0' ?>"
                   data-special="<?= $_eSpecial ? '1' : '0' ?>">
                <div id="req-length" class="text-muted">
                  <i class="ph-duotone ph-circle me-1"></i>Mínimo <?= $_eMinLen ?> caracteres
                </div>
                <?php if ($_eUpper): ?><div id="req-upper" class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos una mayúscula</div><?php endif; ?>
                <?php if ($_eLower): ?><div id="req-lower" class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos una minúscula</div><?php endif; ?>
                <?php if ($_eNumber): ?><div id="req-number" class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos un número</div><?php endif; ?>
                <?php if ($_eSpecial): ?><div id="req-special" class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos un carácter especial</div><?php endif; ?>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label for="password_confirmation" class="form-label fw-semibold">Confirmar Contraseña</label>
              <input type="password" name="password_confirmation" id="password_confirmation"
                     class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                     placeholder="Repetir nueva contraseña" autocomplete="new-password">
              <?php if (isset($errors['password_confirmation'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirmation'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
              <div id="pwd-match" class="mt-1"></div>
            </div>
          </div>

          <div class="mb-3">
            <label for="telefono" class="form-label fw-semibold">Teléfono</label>
            <div class="input-group">
              <span class="input-group-text"><i data-feather="phone"></i></span>
              <input type="text" name="telefono" id="telefono"
                     class="form-control <?= isset($errors['telefono']) ? 'is-invalid' : '' ?>"
                     value="<?= htmlspecialchars($old['telefono'] ?? $user['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     maxlength="25" placeholder="Ej: +503 7000-0000">
              <?php if (isset($errors['telefono'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['telefono'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="direccion" class="form-label fw-semibold">Dirección</label>
            <textarea name="direccion" id="direccion"
                      class="form-control <?= isset($errors['direccion']) ? 'is-invalid' : '' ?>"
                      rows="2" maxlength="255"
                      placeholder="Dirección completa"><?= htmlspecialchars($old['direccion'] ?? $user['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (isset($errors['direccion'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['direccion'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="role_id" class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
              <select name="role_id" id="role_id"
                      class="form-select select2 <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>"
                      required>
                <option value="">— Seleccionar rol —</option>
                <?php foreach ($roles as $role): ?>
                  <?php $sel = ($old['role_id'] ?? $user['role_id'] ?? '') == $role['id']; ?>
                  <option value="<?= (int)$role['id'] ?>" <?= $sel ? 'selected' : '' ?>>
                    <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errors['role_id'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['role_id'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-4">
              <label for="status_id" class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
              <select name="status_id" id="status_id"
                      class="form-select select2 <?= isset($errors['status_id']) ? 'is-invalid' : '' ?>"
                      required>
                <option value="">— Seleccionar estado —</option>
                <?php foreach ($statuses as $status): ?>
                  <?php $sel = ($old['status_id'] ?? $user['status_id'] ?? '') == $status['id']; ?>
                  <option value="<?= (int)$status['id'] ?>" <?= $sel ? 'selected' : '' ?>>
                    <?= htmlspecialchars($status['name'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errors['status_id'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['status_id'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-floppy-disk me-1"></i> Guardar Cambios
            </button>
            <a href="<?= BASE_URL ?>/users" class="btn btn-outline-secondary">
              <i class="ph-duotone ph-x me-1"></i> Cancelar
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
(function () {
  var pwdEl = document.getElementById('password');
  var cfmEl = document.getElementById('password_confirmation');
  var reqs  = document.getElementById('pwd-requirements');
  if (!pwdEl || !cfmEl || !reqs) return;
  var minLen = parseInt(reqs.getAttribute('data-min') || '6', 10);
  var rules = [{ id: 'req-length', fn: function(v){ return v.length >= minLen; } }];
  if (reqs.getAttribute('data-upper')   === '1') rules.push({ id: 'req-upper',   fn: function(v){ return /[A-Z]/.test(v); } });
  if (reqs.getAttribute('data-lower')   === '1') rules.push({ id: 'req-lower',   fn: function(v){ return /[a-z]/.test(v); } });
  if (reqs.getAttribute('data-number')  === '1') rules.push({ id: 'req-number',  fn: function(v){ return /[0-9]/.test(v); } });
  if (reqs.getAttribute('data-special') === '1') rules.push({ id: 'req-special', fn: function(v){ return /[\W_]/.test(v); } });
  pwdEl.addEventListener('input', function () {
    var val = this.value;
    rules.forEach(function (r) {
      var el = document.getElementById(r.id);
      if (!el) return;
      var ok = r.fn(val);
      el.className = val ? (ok ? 'text-success' : 'text-danger') : 'text-muted';
      var icon = el.querySelector('i');
      if (icon) icon.className = val ? (ok ? 'ph-fill ph-check-circle me-1' : 'ph-fill ph-x-circle me-1') : 'ph-duotone ph-circle me-1';
    });
    checkMatch();
  });
  cfmEl.addEventListener('input', checkMatch);
  function checkMatch() {
    var matchDiv = document.getElementById('pwd-match');
    if (!matchDiv) return;
    var p = pwdEl.value, c = cfmEl.value;
    if (!c) { matchDiv.innerHTML = ''; return; }
    matchDiv.innerHTML = p === c
      ? '<span class="text-success small"><i class="ph-fill ph-check-circle me-1"></i>Las contraseñas coinciden.</span>'
      : '<span class="text-danger small"><i class="ph-fill ph-x-circle me-1"></i>Las contraseñas no coinciden.</span>';
  }
}());

$(document).on('click', '.btn-unlock-edit', function () {
  var form = $(this).closest('form');
  var name = $(this).data('name');
  Swal.fire({
    title: '¿Desbloquear usuario?',
    html: 'Se quitará el bloqueo de inicio de sesión a <strong>' + name + '</strong>.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f39c12',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, desbloquear',
    cancelButtonText: 'Cancelar'
  }).then(function (result) {
    if (result.isConfirmed) form.submit();
  });
});
</script>
JS;
?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
