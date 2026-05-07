<?php
$pageTitle  = __('2fa.title');
$activeMenu = 'profile';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('2fa.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/profile"><?= __('menu.my_profile') ?></a></li>
            <li class="breadcrumb-item active"><?= __('2fa.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<?php
$tf_enabled = !empty($user['two_factor_enabled']);
$tf_method  = $user['two_factor_method'] ?? null;
?>

<div class="row">
  <div class="col-12 mb-4">
    <div class="card border-<?= $tf_enabled ? 'success' : 'secondary' ?>">
      <div class="card-body d-flex align-items-center gap-3 flex-wrap">
        <div class="avtar avtar-s bg-light-<?= $tf_enabled ? 'success' : 'secondary' ?>">
          <i class="ph-duotone ph-shield-<?= $tf_enabled ? 'check' : 'warning' ?> text-<?= $tf_enabled ? 'success' : 'secondary' ?>" style="font-size:1.5rem;"></i>
        </div>
        <div>
          <h6 class="mb-1"><?= __('2fa.status_label') ?></h6>
          <span class="badge bg-<?= $tf_enabled ? 'success' : 'secondary' ?>">
            <?= $tf_enabled ? __('2fa.status_enabled') : __('2fa.status_disabled') ?>
          </span>
          <?php if ($tf_enabled && $tf_method): ?>
            <span class="ms-2 text-muted small"><?= __("2fa.method_{$tf_method}") ?></span>
          <?php endif; ?>
        </div>
        <?php if ($tf_enabled): ?>
        <div class="ms-auto">
          <form action="<?= BASE_URL ?>/profile/two-factor/disable" method="POST"
                onsubmit="return confirm('<?= htmlspecialchars(__('2fa.confirm_disable'), ENT_QUOTES) ?>')">
            <?= \Core\CSRF::field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="ph-duotone ph-shield-slash me-1"></i><?= __('2fa.disable') ?>
            </button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if (!$tf_enabled): ?>

<div class="row mb-3">
  <div class="col-12">
    <p class="text-muted"><?= __('2fa.choose_method') ?></p>
  </div>
</div>

<div class="row g-4">

  <?php if (!empty($mfaSettings['email_enabled'])): ?>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0"><i class="ph-duotone ph-envelope me-2 text-primary"></i><?= __('2fa.method_email') ?></h6>
      </div>
      <div class="card-body d-flex flex-column">
        <p class="text-muted small flex-grow-1"><?= __('2fa.method_email_desc') ?></p>
        <form action="<?= BASE_URL ?>/profile/two-factor/enable-email" method="POST">
          <?= \Core\CSRF::field() ?>
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="ph-duotone ph-envelope-open me-1"></i><?= __('2fa.enable_email') ?>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($mfaSettings['sms_enabled'])): ?>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0"><i class="ph-duotone ph-device-mobile me-2 text-success"></i><?= __('2fa.method_sms') ?></h6>
      </div>
      <div class="card-body d-flex flex-column">
        <p class="text-muted small mb-3"><?= __('2fa.method_sms_desc') ?></p>
        <form action="<?= BASE_URL ?>/profile/two-factor/enable-sms" method="POST" class="mt-auto">
          <?= \Core\CSRF::field() ?>
          <div class="mb-2">
            <label class="form-label small fw-semibold"><?= __('2fa.phone_label') ?></label>
            <input type="tel" name="phone" class="form-control form-control-sm" placeholder="+50212345678" required>
            <div class="form-text"><?= __('2fa.phone_hint') ?></div>
          </div>
          <button type="submit" class="btn btn-success btn-sm w-100">
            <i class="ph-duotone ph-device-mobile me-1"></i><?= __('2fa.enable_sms') ?>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($mfaSettings['authenticator_enabled'])): ?>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0"><i class="ph-duotone ph-qr-code me-2 text-warning"></i><?= __('2fa.method_authenticator') ?></h6>
      </div>
      <div class="card-body d-flex flex-column">
        <p class="text-muted small flex-grow-1"><?= __('2fa.method_authenticator_desc') ?></p>
        <a href="<?= BASE_URL ?>/profile/two-factor/setup-authenticator" class="btn btn-warning btn-sm w-100">
          <i class="ph-duotone ph-qr-code me-1"></i><?= __('2fa.setup_authenticator_btn') ?>
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (empty($mfaSettings['email_enabled']) && empty($mfaSettings['sms_enabled']) && empty($mfaSettings['authenticator_enabled'])): ?>
  <div class="col-12">
    <div class="alert alert-warning">
      <i class="ph-duotone ph-warning me-2"></i><?= __('2fa.no_methods_available') ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php endif; ?>

<div class="row mt-4">
  <div class="col-12">
    <a href="<?= BASE_URL ?>/profile" class="btn btn-outline-secondary btn-sm">
      <i class="ph-duotone ph-arrow-left me-1"></i><?= __('buttons.back') ?>
    </a>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
