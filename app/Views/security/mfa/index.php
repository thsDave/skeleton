<?php
$pageTitle  = __('mfa.title');
$activeMenu = 'security_mfa';
$errors     = \Core\Session::getFlash('errors', []);
$old        = \Core\Session::getFlash('old', []);

$v = fn(string $k, string $default = '') =>
    htmlspecialchars((string) ($old[$k] ?? $settings[$k] ?? $default));

$hasSmsConfig = (
    !empty($settings['sms_provider']) &&
    !empty($settings['sms_api_key']) &&
    !empty($settings['sms_api_secret_enc']) &&
    !empty($settings['sms_from'])
);

require dirname(dirname(__DIR__)) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('mfa.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('mfa.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">

  <!-- ── Columna principal ──────────────────────────────────────────────── -->
  <div class="col-lg-8">

    <?php if (can('security_mfa.edit')): ?>
    <form action="<?= BASE_URL ?>/security/mfa/update" method="POST" id="mfaForm" novalidate>
      <?= \Core\CSRF::field() ?>

      <!-- ── MFA por Correo ─────────────────────────────────────────────── -->
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">
            <i class="ph-duotone ph-envelope-simple me-2 text-primary"></i>
            <?= __('mfa.method_email') ?>
          </h5>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
              name="email_enabled" id="emailEnabled"
              value="1"
              <?= (int) ($old['email_enabled'] ?? $settings['email_enabled'] ?? 0) ? 'checked' : '' ?>
            />
          </div>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-2"><?= __('mfa.email_description') ?></p>
          <?php if (!$smtpReady): ?>
          <div class="alert alert-warning d-flex align-items-center gap-2 mb-0 py-2" role="alert">
            <i class="ph-duotone ph-warning flex-shrink-0"></i>
            <div class="small">
              <?= __('mfa.alert.smtp_required') ?>
              <?php if (can('security_smtp.view')): ?>
                <a href="<?= BASE_URL ?>/security/smtp" class="alert-link"><?= __('mfa.alert.go_to_smtp') ?></a>
              <?php endif; ?>
            </div>
          </div>
          <?php else: ?>
          <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-2" role="alert">
            <i class="ph-duotone ph-check-circle flex-shrink-0"></i>
            <div class="small"><?= __('mfa.alert.smtp_ready') ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── MFA por SMS ────────────────────────────────────────────────── -->
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">
            <i class="ph-duotone ph-device-mobile me-2 text-success"></i>
            <?= __('mfa.method_sms') ?>
          </h5>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
              name="sms_enabled" id="smsEnabled"
              value="1"
              <?= (int) ($old['sms_enabled'] ?? $settings['sms_enabled'] ?? 0) ? 'checked' : '' ?>
            />
          </div>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3"><?= __('mfa.sms_description') ?></p>

          <div class="row g-3">
            <div class="col-md-6">
              <label for="smsProvider" class="form-label fw-semibold">
                <?= __('mfa.sms_provider') ?> <span class="text-danger">*</span>
              </label>
              <select name="sms_provider" id="smsProvider" class="form-select">
                <?php
                $prov = $old['sms_provider'] ?? $settings['sms_provider'] ?? '';
                foreach (['twilio' => 'Twilio', 'vonage' => 'Vonage / Nexmo', 'custom' => __('mfa.sms_provider_custom')] as $val => $label):
                ?>
                <option value="<?= $val ?>" <?= $prov === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="smsFrom" class="form-label fw-semibold">
                <?= __('mfa.sms_from') ?> <span class="text-danger">*</span>
              </label>
              <input type="text" name="sms_from" id="smsFrom"
                class="form-control"
                value="<?= htmlspecialchars((string) ($old['sms_from'] ?? $settings['sms_from'] ?? '')) ?>"
                placeholder="+1234567890"
              />
              <div class="form-text"><?= __('mfa.sms_from_hint') ?></div>
            </div>
          </div>

          <div class="row g-3 mt-0">
            <div class="col-md-6">
              <label for="smsApiKey" class="form-label fw-semibold">
                <?= __('mfa.sms_api_key') ?> <span class="text-danger">*</span>
              </label>
              <input type="text" name="sms_api_key" id="smsApiKey"
                class="form-control"
                value="<?= htmlspecialchars((string) ($old['sms_api_key'] ?? $settings['sms_api_key'] ?? '')) ?>"
                placeholder="<?= __('mfa.sms_api_key_placeholder') ?>"
                autocomplete="off"
              />
            </div>
            <div class="col-md-6">
              <label for="smsApiSecret" class="form-label fw-semibold">
                <?= __('mfa.sms_api_secret') ?>
                <?php if (!empty($settings['sms_api_secret_enc'])): ?>
                  <span class="badge bg-success ms-1" style="font-size:0.7rem;">
                    <i class="ph-duotone ph-lock me-1"></i><?= __('smtp.password_saved') ?>
                  </span>
                <?php else: ?>
                  <span class="text-danger">*</span>
                <?php endif; ?>
              </label>
              <div class="input-group">
                <input type="password" name="sms_api_secret" id="smsApiSecret"
                  class="form-control"
                  placeholder="<?= !empty($settings['sms_api_secret_enc']) ? __('mfa.sms_api_secret_stored') : __('mfa.sms_api_secret_placeholder') ?>"
                  autocomplete="new-password"
                />
                <button class="btn btn-outline-secondary" type="button" id="toggleSecret" tabindex="-1">
                  <i class="ph-duotone ph-eye" id="secretEyeIcon"></i>
                </button>
              </div>
              <div class="form-text">
                <?= !empty($settings['sms_api_secret_enc']) ? __('mfa.sms_api_secret_hint_stored') : __('mfa.sms_api_secret_hint') ?>
              </div>
            </div>
          </div>

          <div class="row g-3 mt-0">
            <div class="col-md-12">
              <label for="smsEndpoint" class="form-label fw-semibold">
                <?= __('mfa.sms_endpoint') ?>
                <span class="text-muted fw-normal small">(<?= __('common.optional') ?>)</span>
              </label>
              <input type="text" name="sms_endpoint" id="smsEndpoint"
                class="form-control"
                value="<?= htmlspecialchars((string) ($old['sms_endpoint'] ?? $settings['sms_endpoint'] ?? '')) ?>"
                placeholder="https://api.proveedor.com/v1/messages"
              />
              <div class="form-text"><?= __('mfa.sms_endpoint_hint') ?></div>
            </div>
          </div>

          <div class="row g-3 mt-0">
            <div class="col-md-12">
              <label for="smsExtraConfig" class="form-label fw-semibold">
                <?= __('mfa.sms_extra_config') ?>
                <span class="text-muted fw-normal small">(<?= __('common.optional') ?>, JSON)</span>
              </label>
              <textarea name="sms_extra_config" id="smsExtraConfig"
                class="form-control font-monospace"
                rows="3"
                placeholder='{"region": "us-east-1"}'
                spellcheck="false"><?= htmlspecialchars((string) ($old['sms_extra_config'] ?? ($settings['sms_extra_config'] ?? ''))) ?></textarea>
              <div class="form-text"><?= __('mfa.sms_extra_config_hint') ?></div>
            </div>
          </div>

        </div>
      </div>

      <!-- ── MFA por Aplicación de Autenticación ───────────────────────── -->
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">
            <i class="ph-duotone ph-qr-code me-2 text-warning"></i>
            <?= __('mfa.method_authenticator') ?>
          </h5>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
              name="authenticator_enabled" id="authenticatorEnabled"
              value="1"
              <?= (int) ($old['authenticator_enabled'] ?? $settings['authenticator_enabled'] ?? 1) ? 'checked' : '' ?>
            />
          </div>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-0"><?= __('mfa.authenticator_description') ?></p>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="ph-duotone ph-floppy-disk me-1"></i> <?= __('buttons.save') ?>
      </button>

    </form>
    <?php else: ?>
    <div class="alert alert-info mb-0">
      <i class="ph-duotone ph-info me-2"></i><?= __('roles_permissions.view_only_notice') ?>
    </div>
    <?php endif; ?>

  </div>

  <!-- ── Panel lateral ─────────────────────────────────────────────────── -->
  <div class="col-lg-4">

    <!-- Estado actual -->
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-activity me-2 text-info"></i><?= __('mfa.status_card') ?>
        </h6>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-borderless mb-0 small">
          <tbody>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2" style="width:55%"><?= __('mfa.method_email') ?></td>
              <td class="py-2">
                <?php if ($settings['email_enabled']): ?>
                  <span class="badge bg-success"><i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2"><?= __('mfa.method_sms') ?></td>
              <td class="py-2">
                <?php if ($settings['sms_enabled']): ?>
                  <span class="badge bg-success"><i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2 pb-2"><?= __('mfa.method_authenticator') ?></td>
              <td class="py-2 pb-2">
                <?php if ($settings['authenticator_enabled']): ?>
                  <span class="badge bg-success"><i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                <?php endif; ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Prueba SMS -->
    <?php if (can('security_mfa.test')): ?>
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-paper-plane-tilt me-2 text-primary"></i>
          <?= __('mfa.test_sms') ?>
        </h6>
      </div>
      <div class="card-body">
        <?php if (!$hasSmsConfig): ?>
          <div class="alert alert-warning small mb-0 p-2">
            <i class="ph-duotone ph-warning me-1"></i><?= __('mfa.alert.sms_save_first') ?>
          </div>
        <?php else: ?>
        <form action="<?= BASE_URL ?>/security/mfa/test-sms" method="POST" id="testSmsForm">
          <?= \Core\CSRF::field() ?>
          <div class="mb-3">
            <label for="testPhone" class="form-label fw-semibold small">
              <?= __('mfa.test_phone') ?>
            </label>
            <input type="tel" name="test_phone" id="testPhone"
              class="form-control form-control-sm"
              placeholder="+1234567890"
              required
            />
            <div class="form-text"><?= __('mfa.test_phone_hint') ?></div>
          </div>
          <button type="submit" class="btn btn-success w-100" id="btnTestSms">
            <i class="ph-duotone ph-paper-plane-tilt me-1"></i>
            <?= __('mfa.test_sms') ?>
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Información -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-info me-2 text-secondary"></i><?= __('smtp.info_card') ?>
        </h6>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0 small text-muted">
          <li class="mb-2">
            <i class="ph-duotone ph-check-circle text-success me-1"></i>
            <?= __('mfa.info.email_requires_smtp') ?>
          </li>
          <li class="mb-2">
            <i class="ph-duotone ph-check-circle text-success me-1"></i>
            <?= __('mfa.info.sms_requires_provider') ?>
          </li>
          <li class="mb-2">
            <i class="ph-duotone ph-check-circle text-success me-1"></i>
            <?= __('mfa.info.authenticator_no_provider') ?>
          </li>
          <li class="mb-0">
            <i class="ph-duotone ph-warning text-warning me-1"></i>
            <?= __('mfa.info.phase_note') ?>
          </li>
        </ul>
      </div>
    </div>

  </div>
</div>

<?php require dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>

<script>
(function () {
  // Toggle visibilidad de API Secret
  var toggleBtn  = document.getElementById('toggleSecret');
  var secretInput = document.getElementById('smsApiSecret');
  var eyeIcon    = document.getElementById('secretEyeIcon');
  if (toggleBtn && secretInput) {
    toggleBtn.addEventListener('click', function () {
      var show = secretInput.type === 'password';
      secretInput.type = show ? 'text' : 'password';
      eyeIcon.className = show ? 'ph-duotone ph-eye-slash' : 'ph-duotone ph-eye';
    });
  }

  // Confirmación Swal antes de enviar prueba SMS
  var testSmsForm = document.getElementById('testSmsForm');
  if (testSmsForm && typeof Swal !== 'undefined') {
    testSmsForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var phone = document.getElementById('testPhone');
      var phoneVal = phone ? phone.value.trim() : '';
      Swal.fire({
        title: <?= json_encode(__('mfa.test_sms_confirm_title')) ?>,
        text: <?= json_encode(__('mfa.test_sms_confirm_text')) ?> + (phoneVal ? ' ' + phoneVal : ''),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: <?= json_encode(__('smtp.test.confirm_yes')) ?>,
        cancelButtonText: <?= json_encode(__('alerts.cancel')) ?>,
        confirmButtonColor: '#0d6efd'
      }).then(function (result) {
        if (result.isConfirmed) {
          var btn = document.getElementById('btnTestSms');
          if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span><?= __('smtp.test.sending') ?>';
          }
          testSmsForm.submit();
        }
      });
    });
  }
})();
</script>
