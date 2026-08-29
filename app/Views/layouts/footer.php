<?php
// Leer mensajes flash ANTES de renderizar el script — getFlash los consume al leer
$_swSuccess = \Core\Session::getFlash('success');
$_swError   = \Core\Session::getFlash('error');
?>
  </div><!-- /pc-content -->
</div><!-- /pc-container -->

<footer class="pc-footer">
  <div class="footer-wrapper container-fluid">
    <div class="row">
      <div class="col my-1">
        <p class="m-0">Skeleton MVC &mdash; PHP 8.3 + DashboardKit</p>
      </div>
    </div>
  </div>
</footer>

<!-- Required JS -->
<script src="<?= BASE_URL ?>/assets/js/plugins/popper.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/plugins/simplebar.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/plugins/bootstrap.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/fonts/custom-font.js"></script>
<script src="<?= BASE_URL ?>/assets/js/pcoded.js"></script>
<script src="<?= BASE_URL ?>/assets/js/theme.js"></script>
<script src="<?= BASE_URL ?>/assets/js/plugins/feather.min.js"></script>

<!-- jQuery (requerido por DataTables y Select2) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php $__ft = \Core\Auth::check() ? \Core\Auth::theme() : 'light'; ?>
<script>
/**
 * syncBsTheme — sincroniza data-bs-theme y data-pc-header-theme con el valor
 * de DashboardKit (data-pc-theme). El CSS de la versión Free usa [data-bs-theme=dark]
 * (Bootstrap 5.3) y [data-pc-header-theme=dark] para aplicar el modo oscuro visual.
 */
function syncBsTheme(pcTheme) {
  var dark = (pcTheme === 'dark');
  document.body.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
  layout_header_change(dark ? 'dark' : 'light');
}

<?php if ($__ft === 'default'): ?>
// Modo "Predeterminado": detectar preferencia del SO
(function () {
  var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  var t = prefersDark ? 'dark' : 'light';
  layout_change(t);
  syncBsTheme(t);
  // Escuchar cambios del SO en tiempo real
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
      var nt = e.matches ? 'dark' : 'light';
      layout_change(nt);
      syncBsTheme(nt);
    });
  }
})();
<?php else: ?>
layout_change('<?= htmlspecialchars($__ft, ENT_QUOTES, 'UTF-8') ?>');
syncBsTheme('<?= htmlspecialchars($__ft, ENT_QUOTES, 'UTF-8') ?>');
<?php endif; ?>
  layout_sidebar_change('dark');
  layout_caption_change('true');
  layout_rtl_change('false');
  preset_change('preset-1');
</script>

<?php if ($_swSuccess || $_swError): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  <?php if ($_swSuccess): ?>
  Swal.fire({
    icon: 'success',
    title: <?= json_encode(__('alerts.success'), JSON_UNESCAPED_UNICODE) ?>,
    text: <?= json_encode($_swSuccess, JSON_UNESCAPED_UNICODE) ?>,
    timer: 3500,
    timerProgressBar: true,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
  });
  <?php endif; ?>
  <?php if ($_swError): ?>
  Swal.fire({
    icon: 'error',
    title: <?= json_encode(__('alerts.error'), JSON_UNESCAPED_UNICODE) ?>,
    text: <?= json_encode($_swError, JSON_UNESCAPED_UNICODE) ?>,
    confirmButtonColor: '#4680ff'
  });
  <?php endif; ?>
});
</script>
<?php endif; ?>

<?php
$_swValidationErrors = isset($errors) && is_array($errors) && !empty($errors)
    ? array_values(array_filter($errors))
    : [];
?>
<?php if (!empty($_swValidationErrors)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var msgs = <?= json_encode($_swValidationErrors, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  var html = '<ul class="text-start ps-3 mb-0 mt-1">';
  msgs.forEach(function(m) { html += '<li>' + m + '</li>'; });
  html += '</ul>';
  Swal.fire({
    icon: 'error',
    title: <?= json_encode(__('alerts.validation_title'), JSON_UNESCAPED_UNICODE) ?>,
    html: html,
    confirmButtonColor: '#4680ff',
    customClass: { htmlContainer: 'text-start' }
  });
});
</script>
<?php endif; ?>

<!-- Inicializar Select2 global -->
<script>
$(document).ready(function () {
  $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
});
</script>

<?php if (isset($extraScript)) echo $extraScript; ?>

<?php
$_secIsAuth   = \Core\Auth::check();
$_secLocked   = \Core\Session::get('is_locked', false);
$_secSettings = \Core\Session::get('_sec_settings');
if ($_secIsAuth && !$_secLocked && !empty($_secSettings) && $_secSettings['session_lock_enabled']):
    $_secMs   = (int)$_secSettings['session_inactivity_seconds'] * 1000;
    // Warning fires at 90% of the timeout (at least 10 s before, at most 30 s before)
    $_warnMs  = min(30000, max(10000, (int)($_secMs * 0.1)));
    $_lockTitle = json_encode(__('lock.session_locked'),  JSON_UNESCAPED_UNICODE);
    $_lockHtml  = json_encode(__('lock.warning_html'),    JSON_UNESCAPED_UNICODE);
    $_lockBtn   = json_encode(__('lock.warning_confirm'), JSON_UNESCAPED_UNICODE);
    $_csrfToken = json_encode(\Core\CSRF::token(),        JSON_UNESCAPED_UNICODE);
?>
<script>
(function () {
  var TIMEOUT_MS  = <?= $_secMs ?>;
  var WARN_MS     = <?= $_warnMs ?>;
  var BASE        = '<?= rtrim(BASE_URL, '/') ?>';
  var csrfToken   = <?= $_csrfToken ?>;
  var last        = Date.now();
  var warned      = false;
  var locking     = false; // guard against double-call

  function resetTimer() {
    var wasWarned = warned;
    last    = Date.now();
    warned  = false;
    locking = false;
    if (wasWarned && typeof Swal !== 'undefined') Swal.close();
  }

  ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(function (ev) {
    document.addEventListener(ev, resetTimer, { passive: true });
  });

  /**
   * Tell the server to mark the session as locked, then redirect to /lock.
   * This must happen BEFORE the redirect so LockController::show() finds
   * is_locked=true in the session and renders the lock screen instead of
   * bouncing back to /dashboard.
   */
  function lockAndRedirect() {
    if (locking) return;
    locking = true;

    var body = '_csrf_token=' + encodeURIComponent(csrfToken) +
               '&intended_url=' + encodeURIComponent(window.location.pathname);

    fetch(BASE + '/lock/session', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body,
      credentials: 'same-origin'
    }).then(function () {
      window.location.replace(BASE + '/lock');
    }).catch(function () {
      // Network error — redirect anyway; server-side check will handle it
      window.location.replace(BASE + '/lock');
    });
  }

  setInterval(function () {
    if (locking) return;
    var idle = Date.now() - last;

    if (idle >= TIMEOUT_MS) {
      lockAndRedirect();
      return;
    }

    if (!warned && idle >= TIMEOUT_MS - WARN_MS) {
      warned = true;
      var secs = Math.ceil((TIMEOUT_MS - idle) / 1000);
      var html = <?= $_lockHtml ?>.replace(':secs', secs);
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: <?= $_lockTitle ?>,
          html: html,
          timer: TIMEOUT_MS - idle,
          timerProgressBar: true,
          showConfirmButton: true,
          confirmButtonText: <?= $_lockBtn ?>,
          confirmButtonColor: '#4680ff'
        }).then(function (result) {
          // Only reset the timer if the user explicitly clicked the button.
          // If the Swal timer expired naturally, the setInterval will call
          // lockAndRedirect() on its next tick.
          if (result.isConfirmed) {
            resetTimer();
          }
        });
      }
    }
  }, 2000);
})();
</script>
<?php endif; ?>
</body>
</html>
