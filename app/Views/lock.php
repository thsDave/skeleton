<?php
use Core\Session;
use Core\CSRF;

$_lockError  = Session::getFlash('lock_error');
$_lockTheme  = Session::get('user_theme', 'light');
$_lockBsTheme = ($_lockTheme === 'dark') ? 'dark' : 'light';
$_lockName   = trim(Session::get('user_nombres', '') . ' ' . Session::get('user_apellidos', ''));
if (!$_lockName) $_lockName = Session::get('user_name', 'Usuario');
$_lockImg    = Session::get('user_profile_image');
$_lockAvatar = $_lockImg
    ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($_lockImg, ENT_QUOTES, 'UTF-8')
    : null;
?>
<!doctype html>
<html lang="es">
<head>
  <title><?= __('lock.title') ?> | Skeleton</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="csrf-token" content="<?= htmlspecialchars(\Core\CSRF::token(), ENT_QUOTES, 'UTF-8') ?>" />

  <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.svg" type="image/x-icon" />
  <link href="<?= BASE_URL ?>/assets/fonts/inter/inter.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fonts/phosphor/duotone/style.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" id="main-style-link" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-preset.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dark-mode.css" />
</head>
<body
  data-pc-preset="preset-1"
  data-pc-sidebar-theme="dark"
  data-pc-direction="ltr"
  data-pc-theme="<?= htmlspecialchars($_lockTheme, ENT_QUOTES, 'UTF-8') ?>"
  data-bs-theme="<?= $_lockBsTheme ?>"
>

<div class="loader-bg">
  <div class="pc-loader"><div class="loader-fill"></div></div>
</div>

<div class="auth-main v1">
  <div class="bg-overlay bg-primary"></div>
  <div class="auth-wrapper">
    <div class="auth-form">
      <div class="card my-5">
        <div class="card-body text-center py-4">

          <!-- Avatar -->
          <?php if ($_lockAvatar): ?>
            <img src="<?= $_lockAvatar ?>"
                 alt="avatar"
                 class="rounded-circle mx-auto d-block mb-3"
                 style="width:80px;height:80px;object-fit:cover;"
                 onerror="this.outerHTML='<div class=\'avtar avtar-xl bg-light-primary mx-auto mb-3\' style=\'width:80px;height:80px;display:flex;align-items:center;justify-content:center;border-radius:50%;\'><i class=\'ph-duotone ph-user-circle text-primary\' style=\'font-size:3.5rem;\'></i></div>'">
          <?php else: ?>
            <div class="avtar avtar-xl bg-light-primary mx-auto mb-3"
                 style="width:80px;height:80px;display:flex;align-items:center;justify-content:center;border-radius:50%;">
              <i class="ph-duotone ph-user-circle text-primary" style="font-size:3.5rem;"></i>
            </div>
          <?php endif; ?>

          <div class="mb-1">
            <i class="ph-duotone ph-lock text-warning" style="font-size:1.4rem;"></i>
          </div>
          <h5 class="mb-1 f-w-500"><?= htmlspecialchars($_lockName, ENT_QUOTES, 'UTF-8') ?></h5>
          <p class="text-muted small mb-4"><?= __('lock.message') ?></p>

          <?php if ($_lockError): ?>
            <div class="alert alert-danger py-2 small text-start">
              <i class="ph-duotone ph-warning-circle me-2"></i>
              <?= htmlspecialchars($_lockError, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <form action="<?= BASE_URL ?>/unlock" method="POST" novalidate>
            <?= CSRF::field() ?>

            <div class="mb-3 text-start">
              <label for="password" class="form-label fw-semibold"><?= __('lock.password') ?></label>
              <div class="input-group">
                <span class="input-group-text"><i class="ph-duotone ph-lock"></i></span>
                <input
                  type="password"
                  name="password"
                  id="password"
                  class="form-control"
                  placeholder="••••••••"
                  required
                  autocomplete="current-password"
                  autofocus
                />
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
              <i class="ph-duotone ph-lock-open me-2"></i> <?= __('lock.unlock') ?>
            </button>
          </form>

          <form action="<?= BASE_URL ?>/logout" method="POST">
            <?= CSRF::field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
              <i class="ph-duotone ph-sign-out me-2"></i> <?= __('lock.logout') ?>
            </button>
          </form>

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
<?php if ($_lockTheme === 'default'): ?>
<script>
(function () {
  var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  var t = prefersDark ? 'dark' : 'light';
  layout_change(t);
  document.body.setAttribute('data-bs-theme', t);
})();
</script>
<?php else: ?>
<script>
  layout_change('<?= htmlspecialchars($_lockTheme, ENT_QUOTES, 'UTF-8') ?>');
</script>
<?php endif; ?>
<script>
  layout_sidebar_change('dark');
  preset_change('preset-1');
</script>
</body>
</html>
