<?php
$pageTitle     = __('authentication.title');
$activeMenu    = 'security_mfa';
$activeTab     = $activeTab ?? 'mfa';
$loginSecurity = $loginSecurity ?? [
    'failed_login_protection_enabled' => 1,
    'max_failed_attempts_user'        => 5,
    'user_attempt_window_minutes'     => 15,
    'user_lockout_minutes'            => 15,
    'ip_protection_enabled'           => 1,
    'max_failed_attempts_ip'          => 20,
    'ip_attempt_window_minutes'       => 15,
    'ip_lockout_minutes'              => 30,
];

require dirname(dirname(__DIR__)) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('authentication.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('authentication.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<!-- Pestañas de navegación -->
<ul class="nav nav-tabs mb-4" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= $activeTab === 'mfa' ? 'active' : '' ?>"
            id="btn-tab-mfa"
            data-bs-toggle="tab"
            data-bs-target="#tab-mfa"
            type="button"
            role="tab"
            aria-controls="tab-mfa"
            aria-selected="<?= $activeTab === 'mfa' ? 'true' : 'false' ?>">
      <i class="ph-duotone ph-shield-plus me-1"></i>
      <?= __('authentication.tab_mfa') ?>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= $activeTab === 'failed-attempts' ? 'active' : '' ?>"
            id="btn-tab-failed"
            data-bs-toggle="tab"
            data-bs-target="#tab-failed"
            type="button"
            role="tab"
            aria-controls="tab-failed"
            aria-selected="<?= $activeTab === 'failed-attempts' ? 'true' : 'false' ?>">
      <i class="ph-duotone ph-lock me-1"></i>
      <?= __('authentication.tab_failed_attempts') ?>
    </button>
  </li>
</ul>

<div class="tab-content">

  <!-- ══════════════════════════════════════════════════════════════════════════
       PESTAÑA 1: MFA
  ══════════════════════════════════════════════════════════════════════════ -->
  <div class="tab-pane <?= $activeTab === 'mfa' ? 'active' : '' ?>"
       id="tab-mfa" role="tabpanel" aria-labelledby="btn-tab-mfa">

    <div class="row">

      <!-- Columna principal -->
      <div class="col-lg-8">

        <?php if (can('security_mfa.edit')): ?>
        <form action="<?= BASE_URL ?>/security/mfa/update" method="POST" id="mfaForm" novalidate>
          <?= \Core\CSRF::field() ?>

          <!-- MFA por Correo electrónico -->
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
                  <?= (int)($old['email_enabled'] ?? $settings['email_enabled'] ?? 0) ? 'checked' : '' ?>
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

          <!-- MFA por Aplicación de Autenticación -->
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
                  <?= (int)($old['authenticator_enabled'] ?? $settings['authenticator_enabled'] ?? 1) ? 'checked' : '' ?>
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

      <!-- Panel lateral MFA -->
      <div class="col-lg-4">

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
                  <td class="text-muted ps-3 pe-2 py-2" style="width:60%"><?= __('mfa.method_email') ?></td>
                  <td class="py-2">
                    <?php if ($settings['email_enabled']): ?>
                      <span class="badge bg-success"><i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?></span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted ps-3 pe-2 py-2 pb-3"><?= __('mfa.method_authenticator') ?></td>
                  <td class="py-2 pb-3">
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
              <li class="mb-0">
                <i class="ph-duotone ph-check-circle text-success me-1"></i>
                <?= __('mfa.info.authenticator_no_provider') ?>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════════
       PESTAÑA 2: INTENTOS FALLIDOS
  ══════════════════════════════════════════════════════════════════════════ -->
  <div class="tab-pane <?= $activeTab === 'failed-attempts' ? 'active' : '' ?>"
       id="tab-failed" role="tabpanel" aria-labelledby="btn-tab-failed">

    <div class="row">

      <!-- Formulario de intentos fallidos -->
      <div class="col-lg-8">

        <?php if (can('security_mfa.edit')): ?>
        <form action="<?= BASE_URL ?>/security/mfa/update-login-security" method="POST" id="loginSecurityForm" novalidate>
          <?= \Core\CSRF::field() ?>

          <!-- Protección por usuario/correo -->
          <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="mb-0">
                <i class="ph-duotone ph-user-lock me-2 text-danger"></i>
                <?= __('authentication.failed_attempts.section_user') ?>
              </h5>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                  name="failed_login_protection_enabled"
                  id="failedLoginProtectionEnabled"
                  value="1"
                  <?= (int)($loginSecurity['failed_login_protection_enabled'] ?? 1) ? 'checked' : '' ?>
                />
              </div>
            </div>
            <div class="card-body">
              <p class="text-muted small mb-3"><?= __('authentication.failed_attempts.section_user_desc') ?></p>

              <div class="row g-3">
                <div class="col-md-4">
                  <label for="max_failed_attempts_user" class="form-label fw-semibold small">
                    <?= __('authentication.failed_attempts.max_user_attempts') ?>
                  </label>
                  <input type="number" name="max_failed_attempts_user" id="max_failed_attempts_user"
                         class="form-control"
                         value="<?= (int)($loginSecurity['max_failed_attempts_user'] ?? 5) ?>"
                         min="1" max="20" required>
                  <div class="form-text"><?= __('authentication.failed_attempts.range_1_20') ?></div>
                </div>
                <div class="col-md-4">
                  <label for="user_attempt_window_minutes" class="form-label fw-semibold small">
                    <?= __('authentication.failed_attempts.user_window') ?>
                  </label>
                  <div class="input-group">
                    <input type="number" name="user_attempt_window_minutes" id="user_attempt_window_minutes"
                           class="form-control"
                           value="<?= (int)($loginSecurity['user_attempt_window_minutes'] ?? 15) ?>"
                           min="1" max="1440" required>
                    <span class="input-group-text small"><?= __('authentication.failed_attempts.minutes') ?></span>
                  </div>
                </div>
                <div class="col-md-4">
                  <label for="user_lockout_minutes" class="form-label fw-semibold small">
                    <?= __('authentication.failed_attempts.user_lockout') ?>
                  </label>
                  <div class="input-group">
                    <input type="number" name="user_lockout_minutes" id="user_lockout_minutes"
                           class="form-control"
                           value="<?= (int)($loginSecurity['user_lockout_minutes'] ?? 15) ?>"
                           min="1" max="1440" required>
                    <span class="input-group-text small"><?= __('authentication.failed_attempts.minutes') ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Protección por IP -->
          <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="mb-0">
                <i class="ph-duotone ph-globe me-2 text-warning"></i>
                <?= __('authentication.failed_attempts.section_ip') ?>
              </h5>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                  name="ip_protection_enabled"
                  id="ipProtectionEnabled"
                  value="1"
                  <?= (int)($loginSecurity['ip_protection_enabled'] ?? 1) ? 'checked' : '' ?>
                />
              </div>
            </div>
            <div class="card-body">
              <p class="text-muted small mb-3"><?= __('authentication.failed_attempts.section_ip_desc') ?></p>

              <div class="row g-3">
                <div class="col-md-4">
                  <label for="max_failed_attempts_ip" class="form-label fw-semibold small">
                    <?= __('authentication.failed_attempts.max_ip_attempts') ?>
                  </label>
                  <input type="number" name="max_failed_attempts_ip" id="max_failed_attempts_ip"
                         class="form-control"
                         value="<?= (int)($loginSecurity['max_failed_attempts_ip'] ?? 20) ?>"
                         min="1" max="200" required>
                  <div class="form-text"><?= __('authentication.failed_attempts.range_1_200') ?></div>
                </div>
                <div class="col-md-4">
                  <label for="ip_attempt_window_minutes" class="form-label fw-semibold small">
                    <?= __('authentication.failed_attempts.ip_window') ?>
                  </label>
                  <div class="input-group">
                    <input type="number" name="ip_attempt_window_minutes" id="ip_attempt_window_minutes"
                           class="form-control"
                           value="<?= (int)($loginSecurity['ip_attempt_window_minutes'] ?? 15) ?>"
                           min="1" max="1440" required>
                    <span class="input-group-text small"><?= __('authentication.failed_attempts.minutes') ?></span>
                  </div>
                </div>
                <div class="col-md-4">
                  <label for="ip_lockout_minutes" class="form-label fw-semibold small">
                    <?= __('authentication.failed_attempts.ip_lockout') ?>
                  </label>
                  <div class="input-group">
                    <input type="number" name="ip_lockout_minutes" id="ip_lockout_minutes"
                           class="form-control"
                           value="<?= (int)($loginSecurity['ip_lockout_minutes'] ?? 30) ?>"
                           min="1" max="1440" required>
                    <span class="input-group-text small"><?= __('authentication.failed_attempts.minutes') ?></span>
                  </div>
                </div>
              </div>
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

      <!-- Panel lateral Intentos fallidos -->
      <div class="col-lg-4">

        <div class="card mb-3">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="ph-duotone ph-activity me-2 text-info"></i>
              <?= __('authentication.failed_attempts.status_card') ?>
            </h6>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm table-borderless mb-0 small">
              <tbody>
                <tr>
                  <td class="text-muted ps-3 pe-2 py-2" style="width:65%">
                    <?= __('authentication.failed_attempts.enabled') ?>
                  </td>
                  <td class="py-2">
                    <?php if ($loginSecurity['failed_login_protection_enabled']): ?>
                      <span class="badge bg-success"><i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?></span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted ps-3 pe-2 py-2">
                    <?= __('authentication.failed_attempts.ip_enabled') ?>
                  </td>
                  <td class="py-2">
                    <?php if ($loginSecurity['ip_protection_enabled']): ?>
                      <span class="badge bg-success"><i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?></span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted ps-3 pe-2 py-2">
                    <?= __('authentication.failed_attempts.max_user_attempts') ?>
                  </td>
                  <td class="py-2">
                    <strong><?= (int)$loginSecurity['max_failed_attempts_user'] ?></strong>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted ps-3 pe-2 py-2 pb-3">
                    <?= __('authentication.failed_attempts.user_lockout') ?>
                  </td>
                  <td class="py-2 pb-3">
                    <strong><?= (int)$loginSecurity['user_lockout_minutes'] ?></strong>
                    <span class="text-muted"><?= __('authentication.failed_attempts.minutes') ?></span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

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
                <?= __('authentication.failed_attempts.info_user_protection') ?>
              </li>
              <li class="mb-2">
                <i class="ph-duotone ph-check-circle text-success me-1"></i>
                <?= __('authentication.failed_attempts.info_ip_protection') ?>
              </li>
              <li class="mb-0">
                <i class="ph-duotone ph-check-circle text-success me-1"></i>
                <?= __('authentication.failed_attempts.info_unlock') ?>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>

<?php
$extraScript = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            bootstrap.Tab.getOrCreateInstance(btn).show();
        });
    });
});
</script>
SCRIPT;
?>
<?php require dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
