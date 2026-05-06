<!doctype html>
<html lang="es">
<head>
  <title><?= htmlspecialchars($pageTitle ?? 'Skeleton MVC', ENT_QUOTES, 'UTF-8') ?> | Skeleton</title>
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
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css" />

  <!-- Select2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />

  <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body data-pc-preset="preset-1" data-pc-sidebar-theme="dark" data-pc-header-theme="light" data-pc-sidebar-caption="true" data-pc-direction="ltr" data-pc-theme="<?= \Core\Auth::check() ? htmlspecialchars(\Core\Auth::theme(), ENT_QUOTES, 'UTF-8') : 'light' ?>">

<div class="loader-bg">
  <div class="pc-loader"><div class="loader-fill"></div></div>
</div>
