<?php
if (!function_exists('__')) {
    function __($key, $params = []) { return $key; }
}

$_useLayout = false;
try {
    $_useLayout = defined('BASE_URL')
        && class_exists('\Core\Session', false)
        && \Core\Session::has('user_id');
} catch (\Throwable) {}

$_base = defined('BASE_URL') ? BASE_URL : '';

if ($_useLayout) {
    $pageTitle  = __('errors.500_title');
    $activeMenu = '';
    require dirname(__DIR__) . '/layouts/main.php';
} else { ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>500 | <?= __('errors.500_title') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <link rel="icon" href="<?= $_base ?>/assets/images/favicon.svg" type="image/x-icon" />
  <link href="<?= $_base ?>/assets/fonts/inter/inter.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?= $_base ?>/assets/fonts/phosphor/duotone/style.css" />
  <link rel="stylesheet" href="<?= $_base ?>/assets/css/style.css" id="main-style-link" />
  <style>
    .error-page-wrap { display:flex; align-items:center; justify-content:center; min-height:100vh; }
  </style>
</head>
<body data-pc-preset="preset-1" data-pc-sidebar-theme="dark" data-pc-direction="ltr" data-pc-theme="light">
<div class="error-page-wrap">
<?php } ?>

<div class="row justify-content-center w-100">
  <div class="col-lg-5 col-md-7 col-sm-10">
    <div class="card text-center shadow-sm">
      <div class="card-body py-5 px-4">

        <div class="mb-3">
          <i class="ph-duotone ph-warning-circle text-danger" style="font-size:4.5rem;"></i>
        </div>

        <h1 class="display-2 fw-bold text-danger mb-2">500</h1>
        <h4 class="mb-3"><?= __('errors.500_title') ?></h4>
        <p class="text-muted mb-4"><?= __('errors.500_message') ?></p>

        <div class="d-flex justify-content-center gap-2 flex-wrap">
          <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="ph-duotone ph-arrow-left me-1"></i>
            <?= __('errors.go_back') ?>
          </a>
          <a href="<?= $_base ?>/dashboard" class="btn btn-primary">
            <i class="ph-duotone ph-house me-1"></i>
            <?= __('errors.go_dashboard') ?>
          </a>
        </div>

      </div>
    </div>
  </div>
</div>

<?php if ($_useLayout): ?>
  <?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
<?php else: ?>
</div><!-- /.error-page-wrap -->
</body>
</html>
<?php endif; ?>
