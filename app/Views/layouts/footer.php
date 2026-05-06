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

<script>
  layout_change('light');
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
    title: '¡Listo!',
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
    title: 'Error',
    text: <?= json_encode($_swError, JSON_UNESCAPED_UNICODE) ?>,
    confirmButtonColor: '#4680ff'
  });
  <?php endif; ?>
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
</body>
</html>
