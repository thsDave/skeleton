<?php
$_bodyTheme     = \Core\Auth::check() ? \Core\Auth::theme() : 'light'; // 'light'|'dark'|'default'
$_isDark        = ($_bodyTheme === 'dark');
// Para 'default', el server asume 'light'; JS corregirá según preferencia del SO
$_bsTheme       = $_isDark ? 'dark' : 'light';
$_headerTheme   = $_isDark ? 'dark' : 'light';
?>
<!doctype html>
<html lang="es">
<head>
  <title><?= htmlspecialchars($pageTitle ?? 'Skeleton MVC', ENT_QUOTES, 'UTF-8') ?> | Skeleton</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="csrf-token" content="<?= htmlspecialchars(\Core\CSRF::token(), ENT_QUOTES, 'UTF-8') ?>" />

  <?php
  $_headerAppearance = (new \App\Models\AppearanceSetting())->get();
  if (!empty($_headerAppearance['favicon_path'])) {
      $_faviconExt  = strtolower(pathinfo($_headerAppearance['favicon_path'], PATHINFO_EXTENSION));
      $_faviconMime = ($_faviconExt === 'ico') ? 'image/x-icon' : 'image/png';
      $_faviconHref = BASE_URL . '/uploads/appearance/favicon/' . htmlspecialchars($_headerAppearance['favicon_path'], ENT_QUOTES, 'UTF-8');
  } else {
      $_faviconMime = 'image/x-icon';
      $_faviconHref = BASE_URL . '/assets/images/favicon.svg';
  }
  ?>
  <link rel="icon" href="<?= $_faviconHref ?>" type="<?= $_faviconMime ?>" />
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

  <!-- Dark mode overrides: must load after DashboardKit, Select2 and DataTables -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dark-mode.css" />

  <?php if (isset($extraHead)) echo $extraHead; ?>
  <?php
  $_pc = $_headerAppearance['primary_color'] ?? null;
  $_sc = $_headerAppearance['sidebar_color'] ?? null;
  if ($_pc || $_sc):
  ?>
  <style>
    :root {
      <?= $_pc ? '--app-primary:' . htmlspecialchars($_pc, ENT_QUOTES, 'UTF-8') . ';' : '' ?>
      <?= $_sc ? '--app-sidebar:' . htmlspecialchars($_sc, ENT_QUOTES, 'UTF-8') . ';' : '' ?>
    }
    <?php if ($_pc): ?>
    .btn-primary { background-color: var(--app-primary) !important; border-color: var(--app-primary) !important; }
    .bg-primary   { background-color: var(--app-primary) !important; }
    .text-primary  { color: var(--app-primary) !important; }
    .page-header .breadcrumb-item a { color: var(--app-primary); }
    <?php endif; ?>
    <?php if ($_sc): ?>
    .pc-sidebar .navbar-wrapper { background-color: var(--app-sidebar) !important; }
    <?php endif; ?>
  </style>
  <?php endif; ?>
</head>
<body
  data-pc-preset="preset-1"
  data-pc-sidebar-theme="dark"
  data-pc-header-theme="<?= $_headerTheme ?>"
  data-pc-sidebar-caption="true"
  data-pc-direction="ltr"
  data-pc-theme="<?= htmlspecialchars($_bodyTheme, ENT_QUOTES, 'UTF-8') ?>"
  data-bs-theme="<?= $_bsTheme ?>"
>

<div class="loader-bg">
  <div class="pc-loader"><div class="loader-fill"></div></div>
</div>
