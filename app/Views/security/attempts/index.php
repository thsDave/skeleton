<?php
$pageTitle     = __('security_attempts.title');
$activeMenu    = 'security_attempts';
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
$errors = $errors ?? [];
$old    = $old    ?? [];

require dirname(dirname(__DIR__)) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('security_attempts.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a>
            </li>
            <li class="breadcrumb-item active"><?= __('security_attempts.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">

  <!-- Formulario principal -->
  <div class="col-lg-8">

    <?php if (can('security_attempts.edit')): ?>
    <form action="<?= BASE_URL ?>/security/attempts/update" method="POST" novalidate>
      <?= \Core\CSRF::field() ?>

      <!-- ── Protección por usuario / correo ── -->
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">
            <i class="ph-duotone ph-user-lock me-2 text-danger"></i>
            <?= __('security_attempts.user_protection') ?>
          </h5>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
              id="failedLoginProtectionEnabled"
              name="failed_login_protection_enabled"
              value="1"
              <?= (int)($loginSecurity['failed_login_protection_enabled'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label visually-hidden" for="failedLoginProtectionEnabled">
              <?= __('security_attempts.enabled') ?>
            </label>
          </div>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3"><?= __('security_attempts.user_protection_desc') ?></p>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="max_failed_attempts_user" class="form-label fw-semibold small">
                <?= __('security_attempts.max_user_attempts') ?>
              </label>
              <input type="number"
                id="max_failed_attempts_user"
                name="max_failed_attempts_user"
                class="form-control"
                value="<?= (int)($loginSecurity['max_failed_attempts_user'] ?? 5) ?>"
                min="1" max="20" required>
              <div class="form-text"><?= __('security_attempts.range_1_20') ?></div>
            </div>
            <div class="col-md-4">
              <label for="user_attempt_window_minutes" class="form-label fw-semibold small">
                <?= __('security_attempts.user_window') ?>
              </label>
              <div class="input-group">
                <input type="number"
                  id="user_attempt_window_minutes"
                  name="user_attempt_window_minutes"
                  class="form-control"
                  value="<?= (int)($loginSecurity['user_attempt_window_minutes'] ?? 15) ?>"
                  min="1" max="1440" required>
                <span class="input-group-text small"><?= __('security_attempts.minutes') ?></span>
              </div>
            </div>
            <div class="col-md-4">
              <label for="user_lockout_minutes" class="form-label fw-semibold small">
                <?= __('security_attempts.user_lockout') ?>
              </label>
              <div class="input-group">
                <input type="number"
                  id="user_lockout_minutes"
                  name="user_lockout_minutes"
                  class="form-control"
                  value="<?= (int)($loginSecurity['user_lockout_minutes'] ?? 15) ?>"
                  min="1" max="1440" required>
                <span class="input-group-text small"><?= __('security_attempts.minutes') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Protección por IP ── -->
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">
            <i class="ph-duotone ph-globe me-2 text-warning"></i>
            <?= __('security_attempts.ip_protection') ?>
          </h5>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch"
              id="ipProtectionEnabled"
              name="ip_protection_enabled"
              value="1"
              <?= (int)($loginSecurity['ip_protection_enabled'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label visually-hidden" for="ipProtectionEnabled">
              <?= __('security_attempts.ip_enabled') ?>
            </label>
          </div>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3"><?= __('security_attempts.ip_protection_desc') ?></p>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="max_failed_attempts_ip" class="form-label fw-semibold small">
                <?= __('security_attempts.max_ip_attempts') ?>
              </label>
              <input type="number"
                id="max_failed_attempts_ip"
                name="max_failed_attempts_ip"
                class="form-control"
                value="<?= (int)($loginSecurity['max_failed_attempts_ip'] ?? 20) ?>"
                min="1" max="200" required>
              <div class="form-text"><?= __('security_attempts.range_1_200') ?></div>
            </div>
            <div class="col-md-4">
              <label for="ip_attempt_window_minutes" class="form-label fw-semibold small">
                <?= __('security_attempts.ip_window') ?>
              </label>
              <div class="input-group">
                <input type="number"
                  id="ip_attempt_window_minutes"
                  name="ip_attempt_window_minutes"
                  class="form-control"
                  value="<?= (int)($loginSecurity['ip_attempt_window_minutes'] ?? 15) ?>"
                  min="1" max="1440" required>
                <span class="input-group-text small"><?= __('security_attempts.minutes') ?></span>
              </div>
            </div>
            <div class="col-md-4">
              <label for="ip_lockout_minutes" class="form-label fw-semibold small">
                <?= __('security_attempts.ip_lockout') ?>
              </label>
              <div class="input-group">
                <input type="number"
                  id="ip_lockout_minutes"
                  name="ip_lockout_minutes"
                  class="form-control"
                  value="<?= (int)($loginSecurity['ip_lockout_minutes'] ?? 30) ?>"
                  min="1" max="1440" required>
                <span class="input-group-text small"><?= __('security_attempts.minutes') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="ph-duotone ph-floppy-disk me-1"></i>
        <?= __('security_attempts.save') ?>
      </button>
    </form>
    <?php else: ?>
    <div class="alert alert-info">
      <i class="ph-duotone ph-info me-2"></i>
      <?= __('roles_permissions.view_only_notice') ?>
    </div>
    <?php endif; ?>

  </div>

  <!-- Panel lateral -->
  <div class="col-lg-4">

    <!-- Estado actual -->
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-activity me-2 text-info"></i>
          <?= __('security_attempts.status_card') ?>
        </h6>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-borderless mb-0 small">
          <tbody>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2" style="width:65%">
                <?= __('security_attempts.enabled') ?>
              </td>
              <td class="py-2">
                <?php if ($loginSecurity['failed_login_protection_enabled']): ?>
                  <span class="badge bg-success">
                    <i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?>
                  </span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2">
                <?= __('security_attempts.ip_enabled') ?>
              </td>
              <td class="py-2">
                <?php if ($loginSecurity['ip_protection_enabled']): ?>
                  <span class="badge bg-success">
                    <i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?>
                  </span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2">
                <?= __('security_attempts.max_user_attempts') ?>
              </td>
              <td class="py-2">
                <strong><?= (int)($loginSecurity['max_failed_attempts_user'] ?? 5) ?></strong>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2 pb-3">
                <?= __('security_attempts.user_lockout') ?>
              </td>
              <td class="py-2 pb-3">
                <strong><?= (int)($loginSecurity['user_lockout_minutes'] ?? 15) ?></strong>
                <span class="text-muted"><?= __('security_attempts.minutes') ?></span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Información -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-info me-2 text-secondary"></i>
          <?= __('security_attempts.info') ?>
        </h6>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0 small text-muted">
          <li class="mb-2">
            <i class="ph-duotone ph-check-circle text-success me-1"></i>
            <?= __('security_attempts.info_brute_force') ?>
          </li>
          <li class="mb-2">
            <i class="ph-duotone ph-check-circle text-success me-1"></i>
            <?= __('security_attempts.info_generic_messages') ?>
          </li>
          <li class="mb-2">
            <i class="ph-duotone ph-check-circle text-success me-1"></i>
            <?= __('security_attempts.info_unlock') ?>
          </li>
          <li class="mb-0">
            <i class="ph-duotone ph-check-circle text-success me-1"></i>
            <?= __('security_attempts.info_password_reset') ?>
          </li>
        </ul>
      </div>
    </div>

  </div>
</div>

<?php require dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
