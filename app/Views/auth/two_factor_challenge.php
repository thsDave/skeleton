<?php
use Core\Session;
use Core\CSRF;

$errors  = Session::getFlash('errors', []);
$error   = Session::getFlash('error', '');
$success = Session::getFlash('success', '');
$method  = Session::get('pending_2fa_method', 'email');

if ($error && empty($errors)) {
    $errors = [$error];
}
?>
<!doctype html>
<html lang="es">
<head>
  <title><?= __('2fa.challenge_title') ?> | Skeleton</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.svg" type="image/x-icon" />
  <link href="<?= BASE_URL ?>/assets/fonts/inter/inter.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fonts/phosphor/duotone/style.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fonts/tabler-icons.min.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fonts/feather.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fonts/fontawesome.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fonts/material.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" id="main-style-link" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-preset.css" />
</head>
<body data-pc-preset="preset-1" data-pc-sidebar-theme="dark" data-pc-direction="ltr" data-pc-theme="light">

<div class="loader-bg">
  <div class="pc-loader"><div class="loader-fill"></div></div>
</div>

<div class="auth-main v1">
  <div class="bg-overlay bg-primary"></div>
  <div class="auth-wrapper">
    <div class="auth-form">
      <div class="card my-5">
        <div class="card-body">

          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center gap-2">
              <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center"
                   style="width:44px;height:44px;">
                <i class="ph-duotone ph-shield-check text-white" style="font-size:1.5rem;"></i>
              </div>
              <div class="text-start">
                <h4 class="mb-0 fw-bold text-primary lh-1">Skeleton</h4>
                <small class="text-muted"><?= __('2fa.challenge_subtitle') ?></small>
              </div>
            </div>
          </div>

          <h5 class="mb-1 f-w-500 text-center"><?= __('2fa.challenge_title') ?></h5>
          <p class="text-muted text-center mb-4 small">
            <?php
            $descKey = "2fa.challenge_desc_{$method}";
            echo __($descKey);
            ?>
          </p>

          <?php foreach ($errors as $msg): ?>
            <div class="alert alert-danger py-2 small">
              <i class="ph-duotone ph-warning-circle me-2"></i>
              <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endforeach; ?>

          <?php if ($success): ?>
            <div class="alert alert-success py-2 small">
              <i class="ph-duotone ph-check-circle me-2"></i>
              <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <form action="<?= BASE_URL ?>/two-factor/challenge" method="POST" novalidate>
            <?= CSRF::field() ?>
            <div class="mb-4">
              <label for="code" class="form-label fw-semibold"><?= __('2fa.code_label') ?></label>
              <input type="text"
                     name="code"
                     id="code"
                     class="form-control form-control-lg text-center fw-bold"
                     maxlength="6"
                     pattern="[0-9]{6}"
                     placeholder="000000"
                     autocomplete="one-time-code"
                     inputmode="numeric"
                     autofocus
                     required />
              <div class="form-text text-center"><?= __('2fa.code_hint') ?></div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
              <i class="ph-duotone ph-check-circle me-2"></i><?= __('2fa.verify') ?>
            </button>
          </form>

          <?php if (in_array($method, ['email', 'sms'], true)): ?>
          <div class="text-center mb-2">
            <form action="<?= BASE_URL ?>/two-factor/resend" method="POST" class="d-inline">
              <?= CSRF::field() ?>
              <button type="submit" class="btn btn-link btn-sm p-0">
                <i class="ph-duotone ph-arrows-clockwise me-1"></i><?= __('2fa.resend') ?>
              </button>
            </form>
          </div>
          <?php endif; ?>

          <div class="text-center">
            <a href="<?= BASE_URL ?>/login" class="text-muted small">
              <i class="ph-duotone ph-arrow-left me-1"></i><?= __('2fa.cancel_login') ?>
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/plugins/popper.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/plugins/simplebar.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/plugins/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/fonts/custom-font.js"></script>
<script src="<?= BASE_URL ?>/assets/js/pcoded.js"></script>
<script src="<?= BASE_URL ?>/assets/js/theme.js"></script>
<script src="<?= BASE_URL ?>/assets/js/plugins/feather.min.js"></script>
<script>
  layout_change('light');
  layout_sidebar_change('dark');
  preset_change('preset-1');
</script>
</body>
</html>
