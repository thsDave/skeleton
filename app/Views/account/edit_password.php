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
                placeholder="Mínimo 10 caracteres"
              />
              <?php if (isset($errors['new_password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['new_password'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
            <div id="pwd-requirements" class="mt-2 small lh-lg">
              <div id="req-length"  class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Mínimo 10 caracteres</div>
              <div id="req-upper"   class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos una mayúscula</div>
              <div id="req-lower"   class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos una minúscula</div>
              <div id="req-number"  class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos un número</div>
              <div id="req-special" class="text-muted"><i class="ph-duotone ph-circle me-1"></i>Al menos un carácter especial</div>
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
            <div id="pwd-match" class="mt-1"></div>
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

<?php
$extraScript = <<<'JS'
<script>
(function () {
  var pwdEl = document.getElementById('new_password');
  var cfmEl = document.getElementById('confirm_password');
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
