<?php
$pageTitle  = __('password_policy.title');
$activeMenu = 'security_password_policy';
$policy     = $policy ?? [];

require dirname(dirname(dirname(__DIR__))) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('password_policy.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('password_policy.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- ── Columna izquierda: formulario ─────────────────────────────────── -->
  <div class="col-lg-8">
    <form action="<?= BASE_URL ?>/security/password-policy/update" method="POST" id="formPolicy">
      <?= \Core\CSRF::field() ?>

      <!-- Card principal: activar política -->
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">
            <i class="ph-duotone ph-password me-2 text-primary"></i>
            <?= __('password_policy.title') ?>
          </h5>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
              id="isEnabled" name="is_enabled" value="1"
              <?= !empty($policy['is_enabled']) ? 'checked' : '' ?>>
          </div>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3"><?= __('password_policy.description') ?></p>

          <div class="alert alert-info small py-2">
            <i class="ph-duotone ph-info me-1"></i>
            <?= __('password_policy.scope_note') ?>
          </div>

          <!-- Longitud mínima -->
          <div class="mb-4">
            <label for="minLength" class="form-label fw-semibold">
              <?= __('password_policy.min_length') ?>
            </label>
            <div class="input-group" style="max-width:180px;">
              <input type="number" id="minLength" name="min_length"
                class="form-control text-center"
                min="6" max="128"
                value="<?= (int)($policy['min_length'] ?? 10) ?>">
              <span class="input-group-text">chars</span>
            </div>
            <div class="form-text text-muted"><?= __('password_policy.min_length_help') ?></div>
          </div>

          <!-- Switches de reglas de caracteres -->
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="d-flex align-items-center justify-content-between border rounded p-3">
                <div>
                  <div class="fw-semibold small"><?= __('password_policy.require_uppercase') ?></div>
                  <div class="text-muted" style="font-size:.78rem;"><?= __('password_policy.require_uppercase_ex') ?></div>
                </div>
                <div class="form-check form-switch ms-3">
                  <input class="form-check-input" type="checkbox" role="switch"
                    name="require_uppercase" value="1"
                    <?= !empty($policy['require_uppercase']) ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="d-flex align-items-center justify-content-between border rounded p-3">
                <div>
                  <div class="fw-semibold small"><?= __('password_policy.require_lowercase') ?></div>
                  <div class="text-muted" style="font-size:.78rem;"><?= __('password_policy.require_lowercase_ex') ?></div>
                </div>
                <div class="form-check form-switch ms-3">
                  <input class="form-check-input" type="checkbox" role="switch"
                    name="require_lowercase" value="1"
                    <?= !empty($policy['require_lowercase']) ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="d-flex align-items-center justify-content-between border rounded p-3">
                <div>
                  <div class="fw-semibold small"><?= __('password_policy.require_number') ?></div>
                  <div class="text-muted" style="font-size:.78rem;"><?= __('password_policy.require_number_ex') ?></div>
                </div>
                <div class="form-check form-switch ms-3">
                  <input class="form-check-input" type="checkbox" role="switch"
                    name="require_number" value="1"
                    <?= !empty($policy['require_number']) ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="d-flex align-items-center justify-content-between border rounded p-3">
                <div>
                  <div class="fw-semibold small"><?= __('password_policy.require_special') ?></div>
                  <div class="text-muted" style="font-size:.78rem;"><?= __('password_policy.require_special_ex') ?></div>
                </div>
                <div class="form-check form-switch ms-3">
                  <input class="form-check-input" type="checkbox" role="switch"
                    name="require_special" value="1"
                    <?= !empty($policy['require_special']) ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: reglas contextuales -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ph-duotone ph-user-check me-2 text-warning"></i>
            <?= __('password_policy.contextual_rules') ?>
          </h5>
        </div>
        <div class="card-body">
          <!-- Evitar correo -->
          <div class="d-flex align-items-start justify-content-between py-2 border-bottom">
            <div>
              <div class="fw-semibold small"><?= __('password_policy.prevent_email') ?></div>
              <p class="text-muted small mb-0"><?= __('password_policy.prevent_email_desc') ?></p>
            </div>
            <div class="form-check form-switch ms-3 flex-shrink-0">
              <input class="form-check-input" type="checkbox" role="switch"
                name="prevent_email_in_password" value="1"
                <?= !empty($policy['prevent_email_in_password']) ? 'checked' : '' ?>>
            </div>
          </div>
          <!-- Evitar nombre/apellido -->
          <div class="d-flex align-items-start justify-content-between py-2 border-bottom">
            <div>
              <div class="fw-semibold small"><?= __('password_policy.prevent_name') ?></div>
              <p class="text-muted small mb-0"><?= __('password_policy.prevent_name_desc') ?></p>
            </div>
            <div class="form-check form-switch ms-3 flex-shrink-0">
              <input class="form-check-input" type="checkbox" role="switch"
                name="prevent_name_in_password" value="1"
                <?= !empty($policy['prevent_name_in_password']) ? 'checked' : '' ?>>
            </div>
          </div>
          <!-- Evitar contraseñas comunes -->
          <div class="d-flex align-items-start justify-content-between py-2">
            <div>
              <div class="fw-semibold small"><?= __('password_policy.prevent_common') ?></div>
              <p class="text-muted small mb-0"><?= __('password_policy.prevent_common_desc') ?></p>
            </div>
            <div class="form-check form-switch ms-3 flex-shrink-0">
              <input class="form-check-input" type="checkbox" role="switch"
                name="prevent_common_passwords" value="1"
                <?= !empty($policy['prevent_common_passwords']) ? 'checked' : '' ?>>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: historial y expiración -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ph-duotone ph-clock-countdown me-2 text-info"></i>
            <?= __('password_policy.advanced_rules') ?>
          </h5>
        </div>
        <div class="card-body">
          <!-- Historial -->
          <div class="mb-4">
            <label for="historyCount" class="form-label fw-semibold">
              <?= __('password_policy.history_count') ?>
            </label>
            <div class="input-group" style="max-width:180px;">
              <input type="number" id="historyCount" name="password_history_count"
                class="form-control text-center"
                min="0" max="10"
                value="<?= (int)($policy['password_history_count'] ?? 3) ?>">
              <span class="input-group-text"><?= __('password_policy.history_unit') ?></span>
            </div>
            <div class="form-text text-muted"><?= __('password_policy.history_help') ?></div>
          </div>

          <!-- Expiración -->
          <div>
            <label for="expirationDays" class="form-label fw-semibold">
              <?= __('password_policy.expiration_days') ?>
            </label>
            <div class="input-group" style="max-width:180px;">
              <input type="number" id="expirationDays" name="password_expiration_days"
                class="form-control text-center"
                min="0" max="365"
                value="<?= (int)($policy['password_expiration_days'] ?? 0) ?>">
              <span class="input-group-text"><?= __('password_policy.expiration_unit') ?></span>
            </div>
            <div class="form-text text-muted"><?= __('password_policy.expiration_help') ?></div>
            <?php if (($policy['password_expiration_days'] ?? 0) > 0): ?>
            <div class="alert alert-warning small py-2 mt-2">
              <i class="ph-duotone ph-warning me-1"></i>
              <?= __('password_policy.expiration_pending') ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (can('security_password_policy.edit')): ?>
      <button type="submit" class="btn btn-primary" id="btnSave">
        <i class="ph-duotone ph-floppy-disk me-1"></i>
        <?= __('password_policy.save') ?>
      </button>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Columna derecha: resumen de política activa ───────────────────── -->
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-shield-check me-2 text-success"></i>
          <?= __('password_policy.current_summary') ?>
        </h6>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush small">
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.enabled') ?></span>
            <span class="badge <?= !empty($policy['is_enabled']) ? 'bg-success' : 'bg-secondary' ?>">
              <?= !empty($policy['is_enabled']) ? __('common.yes') : __('common.no') ?>
            </span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.min_length') ?></span>
            <strong><?= (int)($policy['min_length'] ?? 10) ?></strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.require_uppercase') ?></span>
            <?= !empty($policy['require_uppercase'])
              ? '<i class="ph-fill ph-check-circle text-success"></i>'
              : '<i class="ph-fill ph-x-circle text-secondary"></i>' ?>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.require_lowercase') ?></span>
            <?= !empty($policy['require_lowercase'])
              ? '<i class="ph-fill ph-check-circle text-success"></i>'
              : '<i class="ph-fill ph-x-circle text-secondary"></i>' ?>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.require_number') ?></span>
            <?= !empty($policy['require_number'])
              ? '<i class="ph-fill ph-check-circle text-success"></i>'
              : '<i class="ph-fill ph-x-circle text-secondary"></i>' ?>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.require_special') ?></span>
            <?= !empty($policy['require_special'])
              ? '<i class="ph-fill ph-check-circle text-success"></i>'
              : '<i class="ph-fill ph-x-circle text-secondary"></i>' ?>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.prevent_email') ?></span>
            <?= !empty($policy['prevent_email_in_password'])
              ? '<i class="ph-fill ph-check-circle text-success"></i>'
              : '<i class="ph-fill ph-x-circle text-secondary"></i>' ?>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.prevent_name') ?></span>
            <?= !empty($policy['prevent_name_in_password'])
              ? '<i class="ph-fill ph-check-circle text-success"></i>'
              : '<i class="ph-fill ph-x-circle text-secondary"></i>' ?>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.prevent_common') ?></span>
            <?= !empty($policy['prevent_common_passwords'])
              ? '<i class="ph-fill ph-check-circle text-success"></i>'
              : '<i class="ph-fill ph-x-circle text-secondary"></i>' ?>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.history_count') ?></span>
            <strong><?= (int)($policy['password_history_count'] ?? 3) ?></strong>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= __('password_policy.expiration_days') ?></span>
            <strong>
              <?php $exp = (int)($policy['password_expiration_days'] ?? 0); ?>
              <?= $exp > 0 ? $exp . ' ' . __('password_policy.expiration_unit') : __('common.disabled') ?>
            </strong>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
  var btnSave  = document.getElementById('btnSave');
  var minInput = document.getElementById('minLength');
  var isEnabled = document.getElementById('isEnabled');

  if (btnSave) {
    btnSave.addEventListener('click', function (e) {
      var min = parseInt(minInput ? minInput.value : '10', 10);
      var enabled = isEnabled && isEnabled.checked;
      if (enabled && min < 8) {
        e.preventDefault();
        Swal.fire({
          icon: 'error',
          title: 'Política débil',
          text: 'La longitud mínima debe ser al menos 8 caracteres cuando la política está activa.',
          confirmButtonColor: '#4680ff'
        });
        return;
      }
      if (min < 6 || min > 128) {
        e.preventDefault();
        Swal.fire({
          icon: 'error',
          title: 'Valor inválido',
          text: 'La longitud mínima debe estar entre 6 y 128 caracteres.',
          confirmButtonColor: '#4680ff'
        });
      }
    });
  }
});
</script>
JS;

require dirname(dirname(dirname(__DIR__))) . '/layouts/footer.php';
?>
