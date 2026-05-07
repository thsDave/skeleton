<?php
// $errorContext and $isDebug are injected by ErrorHandler::renderGeneral()
/** @var array  $errorContext */
/** @var bool   $isDebug */

if (!function_exists('__')) {
    function __($key, $params = []) { return $key; }
}

$_code    = (int)($errorContext['code']    ?? 500);
$_type    = (string)($errorContext['type']    ?? '');
$_message = (string)($errorContext['message'] ?? '');
$_file    = (string)($errorContext['file']    ?? '');
$_line    = (int)($errorContext['line']    ?? 0);
$_trace   = (string)($errorContext['trace']   ?? '');

$_useLayout = false;
try {
    $_useLayout = defined('BASE_URL')
        && class_exists('\Core\Session', false)
        && \Core\Session::has('user_id');
} catch (\Throwable) {}

$_base = defined('BASE_URL') ? BASE_URL : '';

if ($_useLayout) {
    $pageTitle  = __('errors.general_title');
    $activeMenu = '';
    require dirname(__DIR__) . '/layouts/main.php';
} else { ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title><?= $_code ?> | <?= __('errors.general_title') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <link rel="icon" href="<?= $_base ?>/assets/images/favicon.svg" type="image/x-icon" />
  <link href="<?= $_base ?>/assets/fonts/inter/inter.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?= $_base ?>/assets/fonts/phosphor/duotone/style.css" />
  <link rel="stylesheet" href="<?= $_base ?>/assets/css/style.css" id="main-style-link" />
  <style>
    .error-page-wrap { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:2rem 0; }
  </style>
</head>
<body data-pc-preset="preset-1" data-pc-sidebar-theme="dark" data-pc-direction="ltr" data-pc-theme="light">
<div class="error-page-wrap">
<?php } ?>

<div class="row justify-content-center w-100">
  <div class="col-xl-7 col-lg-8 col-md-10">

    <div class="card shadow-sm <?= $isDebug ? 'mb-4' : '' ?>">
      <div class="card-body py-5 px-4 text-center">

        <div class="mb-3">
          <i class="ph-duotone ph-bug text-danger" style="font-size:4.5rem;"></i>
        </div>

        <h1 class="display-2 fw-bold text-danger mb-2"><?= $_code ?></h1>
        <h4 class="mb-3"><?= __('errors.general_title') ?></h4>
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

    <?php if ($isDebug): ?>
    <div class="card shadow-sm border-danger">
      <div class="card-header bg-danger text-white d-flex align-items-center gap-2">
        <i class="ph-duotone ph-terminal-window"></i>
        <strong><?= __('errors.debug_details') ?></strong>
        <span class="badge bg-light text-danger ms-auto">DEBUG</span>
      </div>
      <div class="card-body p-0">

        <table class="table table-sm mb-0">
          <tbody>
            <?php if ($_type !== ''): ?>
            <tr>
              <th class="ps-3 text-nowrap" style="width:130px;"><?= __('errors.debug_type') ?></th>
              <td><code><?= htmlspecialchars($_type, ENT_QUOTES, 'UTF-8') ?></code></td>
            </tr>
            <?php endif; ?>
            <?php if ($_message !== ''): ?>
            <tr>
              <th class="ps-3 text-nowrap"><?= __('errors.debug_message') ?></th>
              <td><?= htmlspecialchars($_message, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($_file !== ''): ?>
            <tr>
              <th class="ps-3 text-nowrap"><?= __('errors.debug_file') ?></th>
              <td><code class="small" style="word-break:break-all;"><?= htmlspecialchars($_file, ENT_QUOTES, 'UTF-8') ?></code></td>
            </tr>
            <?php endif; ?>
            <?php if ($_line > 0): ?>
            <tr>
              <th class="ps-3 text-nowrap"><?= __('errors.debug_line') ?></th>
              <td><span class="badge bg-secondary"><?= $_line ?></span></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>

        <?php if ($_trace !== ''): ?>
        <div class="border-top p-3">
          <p class="fw-semibold mb-2 small text-muted text-uppercase"><?= __('errors.debug_trace') ?></p>
          <pre class="mb-0 small" style="white-space:pre-wrap;word-break:break-all;max-height:400px;overflow-y:auto;"><?= htmlspecialchars($_trace, ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php if ($_useLayout): ?>
  <?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
<?php else: ?>
</div><!-- /.error-page-wrap -->
</body>
</html>
<?php endif; ?>
