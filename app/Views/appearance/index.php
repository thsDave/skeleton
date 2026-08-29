<?php
$pageTitle  = __('appearance.title');
$activeMenu = 'appearance';
$errors     = $errors ?? [];
$appearance = $appearance ?? [];

require dirname(__DIR__) . '/layouts/main.php';

$base      = BASE_URL;
$logoUrl   = !empty($appearance['logo_path'])
    ? $base . '/uploads/appearance/logo/' . htmlspecialchars($appearance['logo_path'], ENT_QUOTES, 'UTF-8')
    : null;
$faviconUrl = !empty($appearance['favicon_path'])
    ? $base . '/uploads/appearance/favicon/' . htmlspecialchars($appearance['favicon_path'], ENT_QUOTES, 'UTF-8')
    : null;
$loginBgUrl = !empty($appearance['login_background_path'])
    ? $base . '/uploads/appearance/login/' . htmlspecialchars($appearance['login_background_path'], ENT_QUOTES, 'UTF-8')
    : null;
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">
            <i class="ph-duotone ph-palette me-2 text-primary"></i>
            <?= __('appearance.title') ?>
          </h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= $base ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('appearance.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">

  <?php if (!empty($errors)): ?>
  <div class="col-12">
    <div class="alert alert-danger">
      <ul class="mb-0 ps-3">
        <?php foreach ($errors as $msg): ?>
          <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <!-- Columna principal -->
  <div class="col-lg-8">
    <form action="<?= $base ?>/appearance/update" method="POST" enctype="multipart/form-data" novalidate>
      <?= \Core\CSRF::field() ?>

      <!-- Identidad visual -->
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ph-duotone ph-identification-card me-2 text-primary"></i>
            <?= __('appearance.identity') ?>
          </h5>
        </div>
        <div class="card-body">

          <div class="mb-3">
            <label for="app_display_name" class="form-label fw-semibold small">
              <?= __('appearance.app_display_name') ?>
            </label>
            <input type="text" name="app_display_name" id="app_display_name"
              class="form-control <?= isset($errors['app_display_name']) ? 'is-invalid' : '' ?>"
              maxlength="150"
              value="<?= htmlspecialchars($appearance['app_display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              placeholder="Skeleton">
            <?php if (isset($errors['app_display_name'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['app_display_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="app_tagline" class="form-label fw-semibold small">
              <?= __('appearance.app_tagline') ?>
            </label>
            <input type="text" name="app_tagline" id="app_tagline"
              class="form-control <?= isset($errors['app_tagline']) ? 'is-invalid' : '' ?>"
              maxlength="255"
              value="<?= htmlspecialchars($appearance['app_tagline'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              placeholder="<?= __('appearance.app_tagline_placeholder') ?>">
            <?php if (isset($errors['app_tagline'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['app_tagline'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <!-- Logo -->
          <div class="mb-3">
            <label class="form-label fw-semibold small"><?= __('appearance.logo') ?></label>
            <?php if ($logoUrl): ?>
            <div class="mb-2 p-2 border rounded bg-light d-inline-block">
              <img src="<?= $logoUrl ?>" alt="Logo" style="max-height:60px;max-width:200px;object-fit:contain;">
              <div class="mt-1 small text-muted"><?= htmlspecialchars($appearance['logo_path'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php else: ?>
            <div class="mb-2 p-2 border rounded bg-light d-inline-flex align-items-center gap-2 text-muted small">
              <i class="ph-duotone ph-image text-secondary" style="font-size:1.5rem;"></i>
              <?= __('appearance.no_logo') ?>
            </div>
            <?php endif; ?>
            <input type="file" name="logo" id="logo"
              class="form-control <?= isset($errors['logo']) ? 'is-invalid' : '' ?>"
              accept="image/jpeg,image/png,image/webp">
            <div class="form-text text-muted"><?= __('appearance.logo_hint') ?></div>
            <?php if (isset($errors['logo'])): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['logo'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

        </div>
      </div>

      <!-- Favicon -->
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ph-duotone ph-browser me-2 text-info"></i>
            <?= __('appearance.favicon') ?>
          </h5>
        </div>
        <div class="card-body">

          <div class="mb-3">
            <label class="form-label fw-semibold small"><?= __('appearance.current_favicon') ?></label>
            <?php if ($faviconUrl): ?>
            <div class="mb-2 p-2 border rounded bg-light d-inline-flex align-items-center gap-2">
              <img src="<?= $faviconUrl ?>" alt="Favicon" style="width:32px;height:32px;object-fit:contain;">
              <span class="small text-muted"><?= htmlspecialchars($appearance['favicon_path'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php else: ?>
            <div class="mb-2 p-2 border rounded bg-light d-inline-flex align-items-center gap-2 text-muted small">
              <i class="ph-duotone ph-browser text-secondary" style="font-size:1.2rem;"></i>
              <?= __('appearance.no_favicon') ?>
            </div>
            <?php endif; ?>
            <input type="file" name="favicon" id="favicon"
              class="form-control <?= isset($errors['favicon']) ? 'is-invalid' : '' ?>"
              accept="image/x-icon,image/png,.ico">
            <div class="form-text text-muted"><?= __('appearance.favicon_hint') ?></div>
            <?php if (isset($errors['favicon'])): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['favicon'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

        </div>
      </div>

      <!-- Login -->
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ph-duotone ph-sign-in me-2 text-warning"></i>
            <?= __('appearance.login') ?>
          </h5>
        </div>
        <div class="card-body">

          <div class="mb-3">
            <label class="form-label fw-semibold small"><?= __('appearance.login_background') ?></label>
            <?php if ($loginBgUrl): ?>
            <div class="mb-2 border rounded overflow-hidden" style="max-width:300px;">
              <img src="<?= $loginBgUrl ?>" alt="Fondo login" class="img-fluid" style="max-height:120px;width:100%;object-fit:cover;">
            </div>
            <?php else: ?>
            <div class="mb-2 p-2 border rounded bg-light d-inline-flex align-items-center gap-2 text-muted small">
              <i class="ph-duotone ph-image text-secondary" style="font-size:1.2rem;"></i>
              <?= __('appearance.no_login_background') ?>
            </div>
            <?php endif; ?>
            <input type="file" name="login_background" id="login_background"
              class="form-control <?= isset($errors['login_background']) ? 'is-invalid' : '' ?>"
              accept="image/jpeg,image/png,image/webp">
            <div class="form-text text-muted"><?= __('appearance.login_background_hint') ?></div>
            <?php if (isset($errors['login_background'])): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['login_background'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <label for="login_overlay_color" class="form-label fw-semibold small">
                <?= __('appearance.login_overlay_color') ?>
              </label>
              <div class="input-group">
                <input type="color" name="login_overlay_color" id="login_overlay_color"
                  class="form-control form-control-color <?= isset($errors['login_overlay_color']) ? 'is-invalid' : '' ?>"
                  value="<?= htmlspecialchars($appearance['login_overlay_color'] ?? '#4680ff', ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" id="login_overlay_color_text"
                  class="form-control font-monospace small"
                  maxlength="7"
                  value="<?= htmlspecialchars($appearance['login_overlay_color'] ?? '#4680ff', ENT_QUOTES, 'UTF-8') ?>"
                  placeholder="#4680ff">
              </div>
            </div>
            <div class="col-sm-6">
              <label for="login_overlay_opacity" class="form-label fw-semibold small">
                <?= __('appearance.login_overlay_opacity') ?>
                <span class="text-muted fw-normal">(0 – 1)</span>
              </label>
              <input type="number" name="login_overlay_opacity" id="login_overlay_opacity"
                class="form-control <?= isset($errors['login_overlay_opacity']) ? 'is-invalid' : '' ?>"
                step="0.01" min="0" max="1"
                value="<?= htmlspecialchars((string)($appearance['login_overlay_opacity'] ?? '0.40'), ENT_QUOTES, 'UTF-8') ?>"
                placeholder="0.40">
              <?php if (isset($errors['login_overlay_opacity'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['login_overlay_opacity'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>

      <!-- Colores -->
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ph-duotone ph-paint-bucket me-2 text-success"></i>
            <?= __('appearance.colors') ?>
          </h5>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3"><?= __('appearance.colors_desc') ?></p>
          <div class="row g-3">
            <div class="col-sm-6">
              <label for="primary_color" class="form-label fw-semibold small">
                <?= __('appearance.primary_color') ?>
              </label>
              <div class="input-group">
                <input type="color" name="primary_color" id="primary_color"
                  class="form-control form-control-color <?= isset($errors['primary_color']) ? 'is-invalid' : '' ?>"
                  value="<?= htmlspecialchars($appearance['primary_color'] ?? '#4680ff', ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" id="primary_color_text"
                  class="form-control font-monospace small"
                  maxlength="7"
                  value="<?= htmlspecialchars($appearance['primary_color'] ?? '#4680ff', ENT_QUOTES, 'UTF-8') ?>"
                  placeholder="#4680ff">
              </div>
              <?php if (isset($errors['primary_color'])): ?>
                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['primary_color'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
            <div class="col-sm-6">
              <label for="sidebar_color" class="form-label fw-semibold small">
                <?= __('appearance.sidebar_color') ?>
              </label>
              <div class="input-group">
                <input type="color" name="sidebar_color" id="sidebar_color"
                  class="form-control form-control-color <?= isset($errors['sidebar_color']) ? 'is-invalid' : '' ?>"
                  value="<?= htmlspecialchars($appearance['sidebar_color'] ?? '#1c232f', ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" id="sidebar_color_text"
                  class="form-control font-monospace small"
                  maxlength="7"
                  value="<?= htmlspecialchars($appearance['sidebar_color'] ?? '#1c232f', ENT_QUOTES, 'UTF-8') ?>"
                  placeholder="#1c232f">
              </div>
              <?php if (isset($errors['sidebar_color'])): ?>
                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['sidebar_color'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón guardar -->
      <?php if (can('appearance.edit')): ?>
      <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary">
          <i class="ph-duotone ph-floppy-disk me-1"></i>
          <?= __('appearance.save') ?>
        </button>
        <a href="<?= $base ?>/appearance" class="btn btn-outline-secondary">
          <i class="ph-duotone ph-arrow-counter-clockwise me-1"></i>
          <?= __('buttons.cancel') ?>
        </a>
      </div>
      <?php endif; ?>

    </form>
  </div>

  <!-- Panel lateral: Restablecer -->
  <div class="col-lg-4">

      <?php if (can('appearance.reset')): ?>
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="mb-0 text-warning">
            <i class="ph-duotone ph-arrow-counter-clockwise me-2"></i>
            <?= __('appearance.reset_section') ?>
          </h6>
        </div>
        <div class="card-body d-grid gap-2">

          <button type="button" class="btn btn-outline-secondary btn-sm w-100 btn-reset-confirm"
            data-form="frmResetLogo"
            data-msg="<?= htmlspecialchars(__('appearance.confirm_reset_logo'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph-duotone ph-image me-1"></i><?= __('appearance.reset_logo') ?>
          </button>

          <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-confirm"
            data-form="frmResetFavicon"
            data-msg="<?= htmlspecialchars(__('appearance.confirm_reset_favicon'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph-duotone ph-browser me-1"></i><?= __('appearance.reset_favicon') ?>
          </button>

          <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-confirm"
            data-form="frmResetLoginBg"
            data-msg="<?= htmlspecialchars(__('appearance.confirm_reset_login_bg'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph-duotone ph-sign-in me-1"></i><?= __('appearance.reset_login_background') ?>
          </button>

          <button type="button" class="btn btn-outline-warning btn-sm btn-reset-confirm"
            data-form="frmResetColors"
            data-msg="<?= htmlspecialchars(__('appearance.confirm_reset_colors'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="ph-duotone ph-paint-bucket me-1"></i><?= __('appearance.reset_colors') ?>
          </button>

        </div>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">
            <i class="ph-duotone ph-info me-2 text-secondary"></i>
            <?= __('appearance.notes_title') ?>
          </h6>
        </div>
        <div class="card-body small text-muted">
          <ul class="ps-3 mb-0">
            <li class="mb-1"><?= __('appearance.note_logo') ?></li>
            <li class="mb-1"><?= __('appearance.note_favicon') ?></li>
            <li class="mb-1"><?= __('appearance.note_colors') ?></li>
            <li class="mb-0"><?= __('appearance.note_reset') ?></li>
          </ul>
        </div>
      </div>

  </div>
</div>

<?php if (can('appearance.reset')): ?>
<form id="frmResetLogo" action="<?= $base ?>/appearance/reset-logo" method="POST">
  <?= \Core\CSRF::field() ?>
</form>
<form id="frmResetFavicon" action="<?= $base ?>/appearance/reset-favicon" method="POST">
  <?= \Core\CSRF::field() ?>
</form>
<form id="frmResetLoginBg" action="<?= $base ?>/appearance/reset-login-background" method="POST">
  <?= \Core\CSRF::field() ?>
</form>
<form id="frmResetColors" action="<?= $base ?>/appearance/reset-colors" method="POST">
  <?= \Core\CSRF::field() ?>
</form>
<?php endif; ?>

<?php $extraScript = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {

  // Sincronizar color picker <-> text input
  function syncColor(pickerId, textId) {
    var picker = document.getElementById(pickerId);
    var text   = document.getElementById(textId);
    if (!picker || !text) return;
    picker.addEventListener('input', function () { text.value = picker.value; });
    text.addEventListener('change', function () {
      if (/^#[0-9a-fA-F]{6}$/.test(text.value)) {
        picker.value = text.value;
      }
    });
  }
  syncColor('primary_color', 'primary_color_text');
  syncColor('sidebar_color', 'sidebar_color_text');
  syncColor('login_overlay_color', 'login_overlay_color_text');

  // Confirmar antes de restablecer
  document.querySelectorAll('.btn-reset-confirm').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var formId = btn.dataset.form;
      var msg    = btn.dataset.msg || '¿Restablecer este elemento?';
      Swal.fire({
        icon: 'warning',
        title: msg,
        showCancelButton: true,
        confirmButtonText: 'Sí, restablecer',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e58a00',
        reverseButtons: true
      }).then(function (r) {
        if (r.isConfirmed) {
          var form = document.getElementById(formId);
          if (form) form.submit();
        }
      });
    });
  });

});
</script>
JS;
?>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
