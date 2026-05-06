<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = 'Nuevo Usuario';
$activeMenu = 'users';
$errors     = Session::getFlash('errors', []);
$old        = Session::getFlash('old', []);

require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">Nuevo Usuario</h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/users">Usuarios</a></li>
            <li class="breadcrumb-item active">Nuevo</li>
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

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ph-duotone ph-user-plus me-2 text-primary"></i>Crear Usuario</h5>
      </div>
      <div class="card-body">
        <form action="<?= BASE_URL ?>/users/store" method="POST" enctype="multipart/form-data" novalidate>
          <?= CSRF::field() ?>

          <!-- Imagen de perfil -->
          <div class="mb-4 text-center">
            <div class="mb-2">
              <div class="avtar bg-light-primary d-inline-flex align-items-center justify-content-center rounded-circle"
                   id="avatarFallback"
                   style="width:90px;height:90px;">
                <i class="ph-duotone ph-user-circle text-primary" style="font-size:3.5rem;"></i>
              </div>
              <img id="avatarPreview" src="" alt="" class="rounded-circle d-none"
                   style="width:90px;height:90px;object-fit:cover;">
            </div>
            <label for="profile_image" class="form-label fw-semibold d-block">
              Foto de Perfil <span class="text-muted small fw-normal">(JPG, PNG o WEBP — máx. 2 MB, opcional)</span>
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
                     value="<?= htmlspecialchars($old['nombres'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     maxlength="100" required placeholder="Nombres">
              <?php if (isset($errors['nombres'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['nombres'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
              <label for="apellidos" class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
              <input type="text" name="apellidos" id="apellidos"
                     class="form-control <?= isset($errors['apellidos']) ? 'is-invalid' : '' ?>"
                     value="<?= htmlspecialchars($old['apellidos'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
                     value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     required placeholder="correo@ejemplo.com" autocomplete="off">
              <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="password" class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
              <input type="password" name="password" id="password"
                     class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                     required placeholder="Mínimo 10 caracteres" autocomplete="new-password">
              <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
              <div id="pwd-requirements" class="mt-2 small lh-lg">
                <div id="req-length"  class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Mínimo 10 caracteres</div>
                <div id="req-upper"   class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos una mayúscula</div>
                <div id="req-lower"   class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos una minúscula</div>
                <div id="req-number"  class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos un número</div>
                <div id="req-special" class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos un carácter especial</div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label for="password_confirmation" class="form-label fw-semibold">Confirmar Contraseña <span class="text-danger">*</span></label>
              <input type="password" name="password_confirmation" id="password_confirmation"
                     class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                     required placeholder="Repetir contraseña" autocomplete="new-password">
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
                     value="<?= htmlspecialchars($old['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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
                      placeholder="Dirección completa"><?= htmlspecialchars($old['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
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
                  <option value="<?= (int)$role['id'] ?>"
                    <?= ($old['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>>
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
                  <option value="<?= (int)$status['id'] ?>"
                    <?= ($old['status_id'] ?? 1) == $status['id'] ? 'selected' : '' ?>>
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
              <i class="ph-duotone ph-user-plus me-1"></i> Crear Usuario
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
  if (!pwdEl || !cfmEl) return;
  var rules = [
    { id: 'req-length',  fn: function(v){ return v.length >= 10; } },
    { id: 'req-upper',   fn: function(v){ return /[A-Z]/.test(v); } },
    { id: 'req-lower',   fn: function(v){ return /[a-z]/.test(v); } },
    { id: 'req-number',  fn: function(v){ return /[0-9]/.test(v); } },
    { id: 'req-special', fn: function(v){ return /[\W_]/.test(v); } }
  ];
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
</script>
JS;
?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
