<?php
use Core\Session;
use Core\CSRF;

$errors = Session::getFlash('errors', []);
$old    = Session::getFlash('old', []);
?>
<!doctype html>
<html lang="es">
<head>
  <title>Iniciar Sesión | Skeleton</title>
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

          <!-- Logo: icono + texto, siempre visible sin depender de archivos externos -->
          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center gap-2">
              <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center"
                   style="width:44px;height:44px;">
                <i class="ph-duotone ph-shield-check text-white" style="font-size:1.5rem;"></i>
              </div>
              <div class="text-start">
                <h4 class="mb-0 fw-bold text-primary lh-1">Skeleton</h4>
                <small class="text-muted">Sistema MVC</small>
              </div>
            </div>
          </div>

          <h5 class="mb-1 f-w-500 text-center">Iniciar Sesión</h5>
          <p class="text-muted text-center mb-4 small">Ingresa tus credenciales para acceder</p>

          <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $msg): ?>
              <div class="alert alert-danger py-2 small">
                <i class="ph-duotone ph-warning-circle me-2"></i>
                <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <form action="<?= BASE_URL ?>/login" method="POST" novalidate>
            <?= CSRF::field() ?>

            <div class="mb-3">
              <label for="email" class="form-label fw-semibold">Correo electrónico</label>
              <div class="input-group">
                <span class="input-group-text"><i data-feather="mail"></i></span>
                <input
                  type="email"
                  name="email"
                  id="email"
                  class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                  placeholder="correo@ejemplo.com"
                  value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  required
                  autocomplete="email"
                />
              </div>
            </div>

            <div class="mb-4">
              <label for="password" class="form-label fw-semibold">Contraseña</label>
              <div class="input-group">
                <span class="input-group-text"><i data-feather="lock"></i></span>
                <input
                  type="password"
                  name="password"
                  id="password"
                  class="form-control"
                  placeholder="••••••••"
                  required
                  autocomplete="current-password"
                />
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
              <i class="ph-duotone ph-sign-in me-2"></i> Iniciar Sesión
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
<script src="<?= BASE_URL ?>/assets/js/plugins/feather.min.js"></script>
<script>
  layout_change('light');
  layout_sidebar_change('dark');
  preset_change('preset-1');
</script>
</body>
</html>
