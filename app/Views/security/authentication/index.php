<?php
$pageTitle  = __('security_authentication.title');
$activeMenu = 'security_authentication';
$settings   = $settings ?? ['local_login_enabled'=>1,'external_login_enabled'=>0,'allow_auto_user_creation'=>0,'default_role_id'=>null,'require_existing_user'=>1,'allow_account_linking'=>1];
$providers  = $providers ?? [];
$roles      = $roles     ?? [];
$errors     = $errors    ?? [];
$old        = $old       ?? [];

require dirname(dirname(__DIR__)) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('security_authentication.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('security_authentication.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<!-- ══ Configuración general ══════════════════════════════════════════ -->
<div class="row mb-4">
  <div class="col-lg-8">

    <?php if (can('security_authentication.edit')): ?>
    <form id="formSettings" action="<?= BASE_URL ?>/security/authentication/settings/update" method="POST" novalidate>
      <?= \Core\CSRF::field() ?>

      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ph-duotone ph-sign-in me-2 text-primary"></i>
            <?= __('security_authentication.login_methods') ?>
          </h5>
        </div>
        <div class="card-body">

          <!-- Login local -->
          <div class="d-flex align-items-start justify-content-between mb-3 pb-3 border-bottom">
            <div>
              <h6 class="mb-1 fw-semibold">
                <i class="ph-duotone ph-envelope me-1 text-primary"></i>
                <?= __('security_authentication.local_login') ?>
              </h6>
              <p class="text-muted small mb-0"><?= __('security_authentication.local_login_desc') ?></p>
            </div>
            <div class="form-check form-switch ms-3 flex-shrink-0">
              <input class="form-check-input" type="checkbox" role="switch"
                id="localLoginEnabled" name="local_login_enabled" value="1"
                <?= ($settings['local_login_enabled'] ?? 1) ? 'checked' : '' ?>>
              <label class="form-check-label visually-hidden" for="localLoginEnabled">
                <?= __('security_authentication.local_login') ?>
              </label>
            </div>
          </div>

          <!-- Login externo -->
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <h6 class="mb-1 fw-semibold">
                <i class="ph-duotone ph-cloud me-1 text-info"></i>
                <?= __('security_authentication.external_login') ?>
              </h6>
              <p class="text-muted small mb-0"><?= __('security_authentication.external_login_desc') ?></p>
            </div>
            <div class="form-check form-switch ms-3 flex-shrink-0">
              <input class="form-check-input" type="checkbox" role="switch"
                id="externalLoginEnabled" name="external_login_enabled" value="1"
                <?= ($settings['external_login_enabled'] ?? 0) ? 'checked' : '' ?>>
              <label class="form-check-label visually-hidden" for="externalLoginEnabled">
                <?= __('security_authentication.external_login') ?>
              </label>
            </div>
          </div>

        </div>
      </div>

      <!-- Opciones avanzadas -->
      <div class="card mb-3" id="cardExternalOptions"
           style="<?= ($settings['external_login_enabled'] ?? 0) ? '' : 'display:none;' ?>">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ph-duotone ph-sliders me-2 text-secondary"></i>
            <?= __('security_authentication.advanced_options') ?>
          </h5>
        </div>
        <div class="card-body">

          <div class="d-flex align-items-start justify-content-between mb-3 pb-3 border-bottom">
            <div>
              <label class="fw-semibold small mb-0"><?= __('security_authentication.require_existing_user') ?></label>
              <p class="text-muted small mb-0"><?= __('security_authentication.require_existing_user_desc') ?></p>
            </div>
            <div class="form-check form-switch ms-3 flex-shrink-0">
              <input class="form-check-input" type="checkbox" role="switch"
                id="requireExistingUser" name="require_existing_user" value="1"
                <?= ($settings['require_existing_user'] ?? 1) ? 'checked' : '' ?>>
            </div>
          </div>

          <div class="d-flex align-items-start justify-content-between mb-3 pb-3 border-bottom">
            <div>
              <label class="fw-semibold small mb-0"><?= __('security_authentication.allow_account_linking') ?></label>
              <p class="text-muted small mb-0"><?= __('security_authentication.allow_account_linking_desc') ?></p>
            </div>
            <div class="form-check form-switch ms-3 flex-shrink-0">
              <input class="form-check-input" type="checkbox" role="switch"
                id="allowAccountLinking" name="allow_account_linking" value="1"
                <?= ($settings['allow_account_linking'] ?? 1) ? 'checked' : '' ?>>
            </div>
          </div>

          <div class="d-flex align-items-start justify-content-between mb-3 pb-3 border-bottom">
            <div>
              <label class="fw-semibold small mb-0"><?= __('security_authentication.allow_auto_user_creation') ?></label>
              <p class="text-muted small mb-0"><?= __('security_authentication.allow_auto_user_creation_desc') ?></p>
            </div>
            <div class="form-check form-switch ms-3 flex-shrink-0">
              <input class="form-check-input" type="checkbox" role="switch"
                id="allowAutoUserCreation" name="allow_auto_user_creation" value="1"
                <?= ($settings['allow_auto_user_creation'] ?? 0) ? 'checked' : '' ?>>
            </div>
          </div>

          <div id="defaultRoleRow" style="<?= ($settings['allow_auto_user_creation'] ?? 0) ? '' : 'display:none;' ?>">
            <label for="defaultRoleId" class="form-label fw-semibold small">
              <?= __('security_authentication.default_role') ?>
            </label>
            <select name="default_role_id" id="defaultRoleId" class="form-select">
              <option value="">— <?= __('security_authentication.select_role') ?> —</option>
              <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id'] ?>"
                  <?= ((int)($settings['default_role_id'] ?? 0)) === (int)$role['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($role['name'] ?? $role['slug'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text text-muted"><?= __('security_authentication.default_role_desc') ?></div>
          </div>

        </div>
      </div>

      <button type="submit" class="btn btn-primary" id="btnSaveSettings">
        <i class="ph-duotone ph-floppy-disk me-1"></i>
        <?= __('security_authentication.save_settings') ?>
      </button>
    </form>
    <?php else: ?>
    <div class="alert alert-info">
      <i class="ph-duotone ph-info me-2"></i>
      <?= __('roles_permissions.view_only_notice') ?>
    </div>
    <?php endif; ?>

  </div>

  <!-- Panel lateral de estado -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-activity me-2 text-info"></i>
          <?= __('security_authentication.status_card') ?>
        </h6>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-borderless mb-0 small">
          <tbody>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2" style="width:65%"><?= __('security_authentication.local_login') ?></td>
              <td class="py-2">
                <?php if ($settings['local_login_enabled']): ?>
                  <span class="badge bg-success"><i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2"><?= __('security_authentication.external_login') ?></td>
              <td class="py-2">
                <?php if ($settings['external_login_enabled']): ?>
                  <span class="badge bg-success"><i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2"><?= __('security_authentication.require_existing_user') ?></td>
              <td class="py-2">
                <?php if ($settings['require_existing_user']): ?>
                  <span class="badge bg-warning text-dark"><?= __('common.yes') ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.no') ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2 pb-3"><?= __('security_authentication.allow_auto_user_creation') ?></td>
              <td class="py-2 pb-3">
                <?php if ($settings['allow_auto_user_creation']): ?>
                  <span class="badge bg-info"><?= __('common.yes') ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.no') ?></span>
                <?php endif; ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-info me-2 text-secondary"></i>
          <?= __('security_authentication.info') ?>
        </h6>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0 small text-muted">
          <li class="mb-2"><i class="ph-duotone ph-shield-check text-success me-1"></i><?= __('security_authentication.info_local') ?></li>
          <li class="mb-2"><i class="ph-duotone ph-cloud text-info me-1"></i><?= __('security_authentication.info_external') ?></li>
          <li class="mb-2"><i class="ph-duotone ph-warning text-warning me-1"></i><?= __('security_authentication.info_warning') ?></li>
          <li class="mb-0"><i class="ph-duotone ph-lock text-danger me-1"></i><?= __('security_authentication.info_mfa') ?></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- ══ Proveedores externos ═══════════════════════════════════════════ -->
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h5 class="mb-0">
      <i class="ph-duotone ph-plugs-connected me-2 text-primary"></i>
      <?= __('security_authentication.external_providers') ?>
    </h5>
  </div>
  <div class="card-body p-0">
    <?php if (empty($providers)): ?>
      <div class="p-4 text-center text-muted">
        <i class="ph-duotone ph-plugs fs-1 mb-2 d-block"></i>
        <?= __('security_authentication.no_providers') ?>
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th><?= __('security_authentication.provider') ?></th>
            <th><?= __('security_authentication.client_id') ?></th>
            <th><?= __('security_authentication.redirect_uri') ?></th>
            <th><?= __('security_authentication.last_tested_at') ?></th>
            <th><?= __('security_authentication.is_enabled') ?></th>
            <th><?= __('security_authentication.is_verified') ?></th>
            <th class="text-end"><?= __('common.actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($providers as $prov): ?>
          <?php
            $provSlug   = $prov['slug'] ?? '';
            $iconClass  = match ($provSlug) {
                'google'    => 'ph-duotone ph-google-logo text-danger',
                'microsoft' => 'ph-duotone ph-windows-logo text-primary',
                'github'    => 'ph-duotone ph-github-logo',
                default     => 'ph-duotone ph-plugs-connected text-secondary',
            };
            $appUrl   = rtrim((string)env('APP_URL',''), '/');
            $callbackUri = $appUrl . '/auth/external/' . $provSlug . '/callback';
            $hasClientId = !empty($prov['client_id']);
            $hasSecret   = !empty($prov['client_secret']);
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <i class="<?= $iconClass ?>" style="font-size:1.3rem;"></i>
                <span class="fw-semibold"><?= htmlspecialchars($prov['name'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </td>
            <td>
              <?php if ($hasClientId): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                  <i class="ph-duotone ph-check me-1"></i><?= __('security_authentication.configured') ?>
                </span>
              <?php else: ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                  <i class="ph-duotone ph-x me-1"></i><?= __('security_authentication.not_configured') ?>
                </span>
              <?php endif; ?>
            </td>
            <td>
              <code class="small text-muted" title="<?= htmlspecialchars($callbackUri, ENT_QUOTES, 'UTF-8') ?>">
                …/auth/external/<?= htmlspecialchars($provSlug, ENT_QUOTES, 'UTF-8') ?>/callback
              </code>
            </td>
            <td class="small text-muted">
              <?php if (!empty($prov['last_tested_at'])): ?>
                <span class="badge bg-<?= ($prov['last_test_status'] ?? '') === 'success' ? 'success' : 'danger' ?>-subtle
                      text-<?= ($prov['last_test_status'] ?? '') === 'success' ? 'success' : 'danger' ?>">
                  <?= ($prov['last_test_status'] ?? '') === 'success' ? '✓' : '✗' ?>
                </span>
                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($prov['last_tested_at'])), ENT_QUOTES, 'UTF-8') ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($prov['is_enabled']): ?>
                <span class="badge bg-success"><?= __('common.enabled') ?></span>
              <?php else: ?>
                <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($prov['is_verified']): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                  <i class="ph-duotone ph-seal-check me-1"></i><?= __('security_authentication.verified') ?>
                </span>
              <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                  <?= __('security_authentication.not_verified') ?>
                </span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="d-flex justify-content-end gap-1 flex-wrap">

                <?php if (can('security_authentication.providers_edit')): ?>
                <a href="<?= BASE_URL ?>/security/authentication/providers/edit/<?= (int)$prov['id'] ?>"
                   class="btn btn-sm btn-outline-primary">
                  <i class="ph-duotone ph-gear me-1"></i><?= __('security_authentication.configure') ?>
                </a>
                <?php endif; ?>

                <?php if (can('security_authentication.providers_test') && $hasClientId && $hasSecret): ?>
                <form action="<?= BASE_URL ?>/security/authentication/providers/test/<?= (int)$prov['id'] ?>" method="POST" class="d-inline">
                  <?= \Core\CSRF::field() ?>
                  <button type="submit" class="btn btn-sm btn-outline-info">
                    <i class="ph-duotone ph-test-tube me-1"></i><?= __('security_authentication.test') ?>
                  </button>
                </form>
                <?php endif; ?>

                <?php if (can('security_authentication.providers_edit')): ?>
                <form action="<?= BASE_URL ?>/security/authentication/providers/toggle/<?= (int)$prov['id'] ?>" method="POST" class="d-inline form-toggle-provider">
                  <?= \Core\CSRF::field() ?>
                  <button type="submit"
                    class="btn btn-sm <?= $prov['is_enabled'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                    data-enabled="<?= $prov['is_enabled'] ? '1' : '0' ?>"
                    data-name="<?= htmlspecialchars($prov['name'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($prov['is_enabled']): ?>
                      <i class="ph-duotone ph-toggle-left me-1"></i><?= __('buttons.deactivate') ?>
                    <?php else: ?>
                      <i class="ph-duotone ph-toggle-right me-1"></i><?= __('buttons.activate') ?>
                    <?php endif; ?>
                  </button>
                </form>
                <?php endif; ?>

              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>

<?php $extraScript = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
  var externalSwitch = document.getElementById('externalLoginEnabled');
  var localSwitch    = document.getElementById('localLoginEnabled');
  var cardExternal   = document.getElementById('cardExternalOptions');
  var autoCreate     = document.getElementById('allowAutoUserCreation');
  var defaultRoleRow = document.getElementById('defaultRoleRow');
  var btnSave        = document.getElementById('btnSaveSettings');

  function toggleExternalOptions() {
    if (externalSwitch && cardExternal) {
      cardExternal.style.display = externalSwitch.checked ? '' : 'none';
    }
  }

  function toggleDefaultRole() {
    if (autoCreate && defaultRoleRow) {
      defaultRoleRow.style.display = autoCreate.checked ? '' : 'none';
    }
  }

  if (externalSwitch) externalSwitch.addEventListener('change', toggleExternalOptions);
  if (autoCreate)     autoCreate.addEventListener('change', toggleDefaultRole);

  // Warn before disabling local login
  if (btnSave) {
    btnSave.addEventListener('click', function (e) {
      if (localSwitch && !localSwitch.checked && externalSwitch && !externalSwitch.checked) {
        e.preventDefault();
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Debes mantener al menos un método de inicio de sesión activo.',
          confirmButtonColor: '#4680ff'
        });
        return;
      }

      if (localSwitch && !localSwitch.checked) {
        e.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: 'Advertencia de seguridad',
          html: '<strong>¿Desactivar el inicio de sesión local?</strong><br><br>Si desactivas el inicio de sesión con correo y contraseña, solo podrás entrar al sistema mediante proveedores externos.<br><br>Asegúrate de tener al menos un proveedor externo activo y tu cuenta vinculada antes de continuar.',
          showCancelButton: true,
          confirmButtonText: 'Sí, continuar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#d63031',
          cancelButtonColor: '#6c757d'
        }).then(function (result) {
          if (result.isConfirmed) {
            document.getElementById('formSettings').submit();
          }
        });
      }
    });
  }

  // Toggle provider confirmation
  document.querySelectorAll('.form-toggle-provider').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var btn     = form.querySelector('button[type="submit"]');
      var enabled = btn && btn.dataset.enabled === '1';
      var name    = btn && btn.dataset.name ? btn.dataset.name : 'este proveedor';
      if (enabled) {
        e.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: '¿Desactivar ' + name + '?',
          text: 'Los usuarios no podrán iniciar sesión con este proveedor mientras esté inactivo.',
          showCancelButton: true,
          confirmButtonText: 'Sí, desactivar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#e67e22',
          cancelButtonColor: '#6c757d'
        }).then(function (result) {
          if (result.isConfirmed) form.submit();
        });
      }
    });
  });
});
</script>
JS;
?>
