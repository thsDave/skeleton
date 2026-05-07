<?php
use Core\Session;

$method     = Session::get('tf_pending_method', 'email');
$pageTitle  = __('2fa.confirm_title');
$activeMenu = 'profile';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('2fa.confirm_title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/profile"><?= __('menu.my_profile') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/profile/two-factor"><?= __('2fa.title') ?></a></li>
            <li class="breadcrumb-item active"><?= __('2fa.confirm_title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-md-5 col-lg-4">
    <div class="card">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <div class="avtar avtar-l bg-light-primary mx-auto mb-3">
            <i class="ph-duotone ph-<?= $method === 'sms' ? 'device-mobile' : 'envelope-open' ?> text-primary" style="font-size:2rem;"></i>
          </div>
          <h5 class="mb-1"><?= __('2fa.confirm_heading') ?></h5>
          <p class="text-muted small mb-0"><?= __("2fa.confirm_desc_{$method}") ?></p>
        </div>

        <form action="<?= BASE_URL ?>/profile/two-factor/confirm" method="POST">
          <?= \Core\CSRF::field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?= __('2fa.code_label') ?></label>
            <input type="text"
                   name="code"
                   class="form-control form-control-lg text-center fw-bold ls-3"
                   maxlength="6"
                   pattern="[0-9]{6}"
                   placeholder="000000"
                   autocomplete="one-time-code"
                   inputmode="numeric"
                   autofocus
                   required />
            <div class="form-text text-center"><?= __('2fa.code_hint') ?></div>
          </div>
          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-check-circle me-1"></i><?= __('2fa.verify') ?>
            </button>
          </div>
        </form>

        <div class="text-center">
          <form action="<?= BASE_URL ?>/profile/two-factor/resend" method="POST" class="d-inline">
            <?= \Core\CSRF::field() ?>
            <button type="submit" class="btn btn-link btn-sm p-0">
              <i class="ph-duotone ph-arrows-clockwise me-1"></i><?= __('2fa.resend') ?>
            </button>
          </form>
          &nbsp;&middot;&nbsp;
          <a href="<?= BASE_URL ?>/profile/two-factor" class="text-muted small">
            <?= __('buttons.cancel') ?>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
