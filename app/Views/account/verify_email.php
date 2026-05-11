<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = __('account.email_change_verify_title');
$activeMenu = 'account';
$errors     = Session::getFlash('errors', []);

require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('account.email_change_verify_title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('menu.dashboard') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/account"><?= __('account.title') ?></a></li>
            <li class="breadcrumb-item active"><?= __('account.email_change_verify_title') ?></li>
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
        <h5 class="mb-0">
          <i class="ph-duotone ph-shield-check me-2 text-primary"></i><?= __('account.email_change_verify_title') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info py-2 mb-4">
          <i class="ph-duotone ph-envelope-simple me-2"></i>
          <?= __('account.email_change_verify_message', ['email' => htmlspecialchars($maskedEmail ?? '', ENT_QUOTES, 'UTF-8')]) ?>
        </div>

        <form action="<?= BASE_URL ?>/account/email/verify" method="POST" novalidate>
          <?= CSRF::field() ?>

          <div class="mb-4">
            <label for="code" class="form-label fw-semibold">
              <?= __('account.email_change_code') ?> <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              inputmode="numeric"
              pattern="[0-9]{6}"
              maxlength="6"
              name="code"
              id="code"
              class="form-control form-control-lg text-center <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
              autocomplete="one-time-code"
              required
            />
            <?php if (isset($errors['code'])): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['code'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div class="form-text"><?= __('account.email_change_code_help') ?></div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-check-circle me-1"></i><?= __('account.verify_code') ?>
            </button>
          </div>
        </form>

        <hr>

        <div class="d-flex gap-2 flex-wrap">
          <form action="<?= BASE_URL ?>/account/email/resend-code" method="POST">
            <?= CSRF::field() ?>
            <button type="submit" class="btn btn-outline-primary">
              <i class="ph-duotone ph-arrow-clockwise me-1"></i><?= __('account.resend_code') ?>
            </button>
          </form>

          <form action="<?= BASE_URL ?>/account/email/cancel-change" method="POST">
            <?= CSRF::field() ?>
            <button type="submit" class="btn btn-outline-danger">
              <i class="ph-duotone ph-x-circle me-1"></i><?= __('account.cancel_email_change') ?>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
