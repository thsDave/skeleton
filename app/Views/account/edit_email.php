<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = __('account.change_email');
$activeMenu = 'account';
$errors     = Session::getFlash('errors', []);
$old        = Session::getFlash('old', []);

require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('account.change_email') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('menu.dashboard') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/account"><?= __('account.title') ?></a></li>
            <li class="breadcrumb-item active"><?= __('account.change_email') ?></li>
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
          <i class="ph-duotone ph-envelope me-2 text-primary"></i><?= __('account.change_email_title') ?>
        </h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info py-2 mb-4">
          <i class="ph-duotone ph-info me-2"></i>
          <?= __('account.current_email') ?>:
          <strong><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>

        <?php if (!empty($pending)): ?>
        <div class="alert alert-warning py-2 mb-4">
          <i class="ph-duotone ph-clock me-2"></i>
          <?= __('account.email_change_pending_notice', ['email' => htmlspecialchars($maskedPendingEmail ?? '', ENT_QUOTES, 'UTF-8')]) ?>
          <div class="mt-2">
            <a href="<?= BASE_URL ?>/account/email/verify" class="btn btn-sm btn-outline-primary">
              <i class="ph-duotone ph-check-circle me-1"></i><?= __('account.verify_code') ?>
            </a>
          </div>
        </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/account/update-email" method="POST" novalidate>
          <?= CSRF::field() ?>

          <div class="mb-4">
            <label for="email" class="form-label fw-semibold">
              <?= __('account.new_email') ?> <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text"><i data-feather="mail"></i></span>
              <input
                type="email"
                name="email"
                id="email"
                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                maxlength="150"
                required
                autocomplete="email"
                placeholder="<?= htmlspecialchars(__('account.email_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
              />
              <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
            <div class="form-text"><?= __('account.email_change_help') ?></div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-paper-plane-tilt me-1"></i><?= __('account.send_verification_code') ?>
            </button>
            <a href="<?= BASE_URL ?>/account" class="btn btn-outline-secondary">
              <i class="ph-duotone ph-x me-1"></i><?= __('account.cancel') ?>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
