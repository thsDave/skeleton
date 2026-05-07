<?php
?>
<!doctype html>
<html lang="es">
<head>
  <title><?= __('auth.password_reset_success_title') ?> | <?= htmlspecialchars((string) env('APP_NAME', 'Skeleton'), ENT_QUOTES, 'UTF-8') ?></title>
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
        <div class="card-body text-center py-5">

          <div class="mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle"
                 style="width:80px;height:80px;">
              <i class="ph-duotone ph-check-circle text-success" style="font-size:3rem;"></i>
            </div>
          </div>

          <h5 class="mb-2 f-w-500"><?= __('auth.password_reset_success_title') ?></h5>
          <p class="text-muted small mb-4"><?= __('auth.password_reset_success') ?></p>

          <a href="<?= BASE_URL ?>/login" class="btn btn-primary w-100">
            <i class="ph-duotone ph-sign-in me-2"></i><?= __('auth.login') ?>
          </a>

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
