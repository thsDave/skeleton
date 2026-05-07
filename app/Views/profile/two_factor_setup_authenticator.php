<?php
// Variables in scope from controller: $qrSvg (string), $secret (string)
$pageTitle  = __('2fa.setup_authenticator');
$activeMenu = 'profile';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('2fa.setup_authenticator') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/profile"><?= __('menu.my_profile') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/profile/two-factor"><?= __('2fa.title') ?></a></li>
            <li class="breadcrumb-item active"><?= __('2fa.setup_authenticator') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-md-7 col-lg-5">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ph-duotone ph-qr-code me-2 text-warning"></i><?= __('2fa.setup_authenticator') ?></h5>
      </div>
      <div class="card-body">
        <p class="text-muted small"><?= __('2fa.setup_authenticator_desc') ?></p>

        <ol class="small mb-4">
          <li class="mb-1"><?= __('2fa.setup_step1') ?></li>
          <li class="mb-1"><?= __('2fa.setup_step2') ?></li>
          <li><?= __('2fa.setup_step3') ?></li>
        </ol>

        <div class="text-center mb-4 p-3 bg-light rounded">
          <?= $qrSvg ?>
        </div>

        <div class="mb-4">
          <label class="form-label text-muted small"><?= __('2fa.setup_manual_label') ?></label>
          <div class="input-group input-group-sm">
            <input type="text"
                   id="secretKey"
                   class="form-control font-monospace"
                   value="<?= htmlspecialchars($secret, ENT_QUOTES, 'UTF-8') ?>"
                   readonly />
            <button class="btn btn-outline-secondary"
                    type="button"
                    onclick="navigator.clipboard.writeText(document.getElementById('secretKey').value)
                             .then(() => this.innerHTML = '<i class=\'ph-duotone ph-check\'></i>')
                             .catch(() => {})">
              <i class="ph-duotone ph-copy"></i>
            </button>
          </div>
        </div>

        <form action="<?= BASE_URL ?>/profile/two-factor/confirm-authenticator" method="POST">
          <?= \Core\CSRF::field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?= __('2fa.setup_verify_label') ?></label>
            <input type="text"
                   name="code"
                   class="form-control form-control-lg text-center fw-bold"
                   maxlength="6"
                   pattern="[0-9]{6}"
                   placeholder="000000"
                   autocomplete="one-time-code"
                   inputmode="numeric"
                   autofocus
                   required />
            <div class="form-text"><?= __('2fa.setup_verify_hint') ?></div>
          </div>
          <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/profile/two-factor" class="btn btn-outline-secondary flex-fill">
              <?= __('buttons.cancel') ?>
            </a>
            <button type="submit" class="btn btn-warning flex-fill">
              <i class="ph-duotone ph-check me-1"></i><?= __('2fa.verify') ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
