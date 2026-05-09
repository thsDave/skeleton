<?php
$pageTitle = __('mfa.title');
$activeMenu = 'security_mfa';
$errors    = $errors ?? [];
$old       = $old    ?? [];

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
            <li class="breadcrumb-item">
              <a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a>
            </li>
            <li class="breadcrumb-item active"><?= __('mfa.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">

  <!-- Columna principal -->
  <div class="col-lg-8">

    <?php if (can('security_mfa.edit')): ?>
    <form action="<?= BASE_URL ?>/security/mfa/update" method="POST" novalidate>
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
              id="emailEnabled" name="email_enabled" value="1"
              <?= (int)($old['email_enabled'] ?? $settings['email_enabled'] ?? 0) ? 'checked' : '' ?>>
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
                <a href="<?= BASE_URL ?>/security/smtp" class="alert-link ms-1">
                  <?= __('mfa.alert.go_to_smtp') ?>
                </a>
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
              id="authenticatorEnabled" name="authenticator_enabled" value="1"
              <?= (int)($old['authenticator_enabled'] ?? $settings['authenticator_enabled'] ?? 1) ? 'checked' : '' ?>>
          </div>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-0"><?= __('mfa.authenticator_description') ?></p>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="ph-duotone ph-floppy-disk me-1"></i>
        <?= __('buttons.save') ?>
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

    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-activity me-2 text-info"></i>
          <?= __('mfa.status_card') ?>
        </h6>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-borderless mb-0 small">
          <tbody>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2" style="width:60%">
                <?= __('mfa.method_email') ?>
              </td>
              <td class="py-2">
                <?php if ($settings['email_enabled']): ?>
                  <span class="badge bg-success">
                    <i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?>
                  </span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= __('common.disabled') ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 pe-2 py-2 pb-3">
                <?= __('mfa.method_authenticator') ?>
              </td>
              <td class="py-2 pb-3">
                <?php if ($settings['authenticator_enabled']): ?>
                  <span class="badge bg-success">
                    <i class="ph-duotone ph-check me-1"></i><?= __('common.enabled') ?>
                  </span>
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
          <i class="ph-duotone ph-info me-2 text-secondary"></i>
          <?= __('smtp.info_card') ?>
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

<?php require dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
