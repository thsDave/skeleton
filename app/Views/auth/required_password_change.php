<?php
use Core\Auth;
use Core\Session;
use Core\CSRF;

$flashError   = Session::getFlash('error');
$flashSuccess = Session::getFlash('success');
$theme        = Auth::theme();
?>
<!doctype html>
<html lang="es">
<head>
  <title><?= __('password_policy.required_change_title') ?> | <?= htmlspecialchars((string) env('APP_NAME', 'Skeleton'), ENT_QUOTES, 'UTF-8') ?></title>
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
<body data-pc-preset="preset-1" data-pc-sidebar-theme="dark" data-pc-direction="ltr"
      data-pc-theme="<?= htmlspecialchars($theme, ENT_QUOTES, 'UTF-8') ?>">

<div class="loader-bg">
  <div class="pc-loader"><div class="loader-fill"></div></div>
</div>

<div class="auth-main v1">
  <div class="bg-overlay bg-primary"></div>
  <div class="auth-wrapper">
    <div class="auth-form">
      <div class="card my-5">
        <div class="card-body">

          <!-- Logo -->
          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center gap-2">
              <div class="bg-primary rounded-3 d-flex align-items-center justify-content-center"
                   style="width:44px;height:44px;">
                <i class="ph-duotone ph-lock-key text-white" style="font-size:1.5rem;"></i>
              </div>
              <div class="text-start">
                <h4 class="mb-0 fw-bold text-primary lh-1"><?= htmlspecialchars((string) env('APP_NAME', 'Skeleton'), ENT_QUOTES, 'UTF-8') ?></h4>
                <small class="text-muted"><?= __('app.tagline') ?></small>
              </div>
            </div>
          </div>

          <h5 class="mb-1 f-w-500 text-center"><?= __('password_policy.required_change_title') ?></h5>
          <p class="text-muted text-center mb-3 small"><?= __('password_policy.required_change_message') ?></p>

          <!-- Reason banner -->
          <?php
          $reasonKey = ($reason === 'forced')
              ? 'password_policy.required_change_reason_forced'
              : 'password_policy.required_change_reason_expired';
          $bannerClass = ($reason === 'forced') ? 'alert-warning' : 'alert-info';
          ?>
          <div class="alert <?= $bannerClass ?> py-2 small mb-4">
            <i class="ph-duotone ph-info me-2"></i>
            <?= __($reasonKey) ?>
          </div>

          <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $field => $msg): ?>
              <div class="alert alert-danger py-2 small">
                <i class="ph-duotone ph-warning-circle me-2"></i>
                <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <form action="<?= BASE_URL ?>/account/password/required-change" method="POST" novalidate>
            <?= CSRF::field() ?>

            <div class="mb-3">
              <label for="new_password" class="form-label fw-semibold">
                <?= __('password_policy.new_password') ?> <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text"><i data-feather="key"></i></span>
                <input
                  type="password"
                  name="new_password"
                  id="new_password"
                  class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                  placeholder="<?= __('account.pwd_placeholder') ?>"
                  required
                  autocomplete="new-password"
                />
                <?php if (isset($errors['new_password'])): ?>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['new_password'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
              </div>
<?php
$_rMinLen  = (int)(($policyReqs['is_enabled'] ?? 0) ? ($policyReqs['min_length'] ?? 10) : 6);
$_rUpper   = !empty($policyReqs['is_enabled']) && !empty($policyReqs['require_uppercase']);
$_rLower   = !empty($policyReqs['is_enabled']) && !empty($policyReqs['require_lowercase']);
$_rNumber  = !empty($policyReqs['is_enabled']) && !empty($policyReqs['require_number']);
$_rSpecial = !empty($policyReqs['is_enabled']) && !empty($policyReqs['require_special']);
?>
              <div id="pwd-requirements" class="mt-2 small lh-lg"
                   data-min="<?= $_rMinLen ?>"
                   data-upper="<?= $_rUpper ? '1' : '0' ?>"
                   data-lower="<?= $_rLower ? '1' : '0' ?>"
                   data-number="<?= $_rNumber ? '1' : '0' ?>"
                   data-special="<?= $_rSpecial ? '1' : '0' ?>">
                <div id="req-length" class="text-muted">
                  <i class="ph-duotone ph-circle me-1"></i>Mínimo <?= $_rMinLen ?> caracteres
                </div>
                <?php if ($_rUpper): ?><div id="req-upper" class="text-muted"><i class="ph-duotone ph-circle me-1"></i><?= __('password.uppercase') ?></div><?php endif; ?>
                <?php if ($_rLower): ?><div id="req-lower" class="text-muted"><i class="ph-duotone ph-circle me-1"></i><?= __('password.lowercase') ?></div><?php endif; ?>
                <?php if ($_rNumber): ?><div id="req-number" class="text-muted"><i class="ph-duotone ph-circle me-1"></i><?= __('password.number') ?></div><?php endif; ?>
                <?php if ($_rSpecial): ?><div id="req-special" class="text-muted"><i class="ph-duotone ph-circle me-1"></i><?= __('password.special') ?></div><?php endif; ?>
              </div>
            </div>

            <div class="mb-4">
              <label for="confirm_password" class="form-label fw-semibold">
                <?= __('password_policy.confirm_password') ?> <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text"><i data-feather="check-circle"></i></span>
                <input
                  type="password"
                  name="confirm_password"
                  id="confirm_password"
                  class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                  placeholder="<?= __('account.confirm_placeholder') ?>"
                  required
                  autocomplete="new-password"
                />
                <?php if (isset($errors['confirm_password'])): ?>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
              </div>
              <div id="pwd-match" class="mt-1 small"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
              <i class="ph-duotone ph-lock-key me-2"></i><?= __('password_policy.update_password') ?>
            </button>
          </form>

          <!-- Logout -->
          <div class="text-center">
            <form action="<?= BASE_URL ?>/logout" method="POST" class="d-inline">
              <?= CSRF::field() ?>
              <button type="submit" class="btn btn-link text-muted small p-0">
                <i class="ph-duotone ph-sign-out me-1"></i><?= __('auth.logout') ?>
              </button>
            </form>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
layout_change(<?= json_encode($theme) ?>);
layout_sidebar_change('dark');
preset_change('preset-1');

<?php if ($flashSuccess): ?>
document.addEventListener('DOMContentLoaded', function () {
  Swal.fire({
    icon: 'success',
    title: '¡Listo!',
    text: <?= json_encode($flashSuccess, JSON_UNESCAPED_UNICODE) ?>,
    timer: 3500,
    timerProgressBar: true,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
  });
});
<?php endif; ?>
<?php if ($flashError): ?>
document.addEventListener('DOMContentLoaded', function () {
  Swal.fire({
    icon: 'error',
    title: 'Error',
    text: <?= json_encode($flashError, JSON_UNESCAPED_UNICODE) ?>,
    confirmButtonColor: '#4680ff'
  });
});
<?php endif; ?>

(function () {
  var pwdEl = document.getElementById('new_password');
  var cfmEl = document.getElementById('confirm_password');
  var reqs  = document.getElementById('pwd-requirements');
  if (!pwdEl || !cfmEl || !reqs) return;
  var minLen = parseInt(reqs.getAttribute('data-min') || '6', 10);
  var rules = [{ id: 'req-length', fn: function(v){ return v.length >= minLen; } }];
  if (reqs.getAttribute('data-upper')   === '1') rules.push({ id: 'req-upper',   fn: function(v){ return /[A-Z]/.test(v); } });
  if (reqs.getAttribute('data-lower')   === '1') rules.push({ id: 'req-lower',   fn: function(v){ return /[a-z]/.test(v); } });
  if (reqs.getAttribute('data-number')  === '1') rules.push({ id: 'req-number',  fn: function(v){ return /[0-9]/.test(v); } });
  if (reqs.getAttribute('data-special') === '1') rules.push({ id: 'req-special', fn: function(v){ return /[\W_]/.test(v); } });
  pwdEl.addEventListener('input', function () {
    var val = this.value;
    rules.forEach(function (r) {
      var el = document.getElementById(r.id);
      if (!el) return;
      var ok = r.fn(val);
      el.className = val ? (ok ? 'text-success' : 'text-danger') : 'text-muted';
      var icon = el.querySelector('i');
      if (icon) icon.className = val ? (ok ? 'ph-fill ph-check-circle me-1' : 'ph-fill ph-x-circle me-1') : 'ph-duotone ph-circle me-1';
    });
    checkMatch();
  });
  cfmEl.addEventListener('input', checkMatch);
  function checkMatch() {
    var matchDiv = document.getElementById('pwd-match');
    if (!matchDiv) return;
    var p = pwdEl.value, c = cfmEl.value;
    if (!c) { matchDiv.innerHTML = ''; return; }
    var match = <?= json_encode(__('password.match'), JSON_UNESCAPED_UNICODE) ?>;
    var noMatch = <?= json_encode(__('password.no_match'), JSON_UNESCAPED_UNICODE) ?>;
    matchDiv.innerHTML = p === c
      ? '<span class="text-success"><i class="ph-fill ph-check-circle me-1"></i>' + match + '</span>'
      : '<span class="text-danger"><i class="ph-fill ph-x-circle me-1"></i>' + noMatch + '</span>';
  }
}());
</script>
</body>
</html>
