<?php
$pageTitle = __('sessions.my_sessions');
$activeMenu = 'account';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('sessions.my_sessions') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/account"><?= __('menu.account') ?></a></li>
            <li class="breadcrumb-item active"><?= __('sessions.active_sessions') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h5 class="mb-0">
      <i class="ph-duotone ph-devices me-2 text-primary"></i>
      <?= __('sessions.active_sessions') ?>
    </h5>
    <?php if (can('account.sessions.revoke')): ?>
      <form action="<?= BASE_URL ?>/account/sessions/revoke-others" method="POST" class="js-confirm-session-action">
        <?= \Core\CSRF::field() ?>
        <input type="hidden" name="confirm_message" value="<?= htmlspecialchars(__('sessions.confirm_close_other_sessions'), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn-outline-danger btn-sm">
          <i class="ph-duotone ph-sign-out me-1"></i>
          <?= __('sessions.close_other_sessions') ?>
        </button>
      </form>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if (empty($sessions)): ?>
      <div class="text-center text-muted py-4">
        <i class="ph-duotone ph-devices d-block mb-2" style="font-size:2rem;"></i>
        <?= __('sessions.no_active_sessions') ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th><?= __('sessions.device') ?></th>
              <th><?= __('sessions.ip_address') ?></th>
              <th><?= __('sessions.started_at') ?></th>
              <th><?= __('sessions.last_activity') ?></th>
              <th><?= __('sessions.status') ?></th>
              <th class="text-end"><?= __('sessions.actions') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sessions as $session): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($session['browser'] ?? __('sessions.unknown_browser'), ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="small text-muted">
                    <?= htmlspecialchars(($session['platform'] ?? __('sessions.unknown_platform')) . ' / ' . ($session['device_type'] ?? __('sessions.unknown_device')), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($session['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= !empty($session['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($session['created_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                <td><?= !empty($session['last_activity_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($session['last_activity_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                <td>
                  <?php if (!empty($session['is_current'])): ?>
                    <span class="badge bg-primary"><?= __('sessions.current_session') ?></span>
                  <?php else: ?>
                    <span class="badge bg-success"><?= __('sessions.active') ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <?php if (can('account.sessions.revoke') && empty($session['is_current'])): ?>
                    <form action="<?= BASE_URL ?>/account/sessions/revoke/<?= (int)$session['id'] ?>" method="POST" class="d-inline js-confirm-session-action">
                      <?= \Core\CSRF::field() ?>
                      <input type="hidden" name="confirm_message" value="<?= htmlspecialchars(__('sessions.confirm_close_session'), ENT_QUOTES, 'UTF-8') ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="ph-duotone ph-x-circle me-1"></i>
                        <?= __('sessions.close_session') ?>
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small"><?= __('sessions.current_session') ?></span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-confirm-session-action').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var input = form.querySelector('[name="confirm_message"]');
      Swal.fire({
        icon: 'warning',
        title: input ? input.value : 'Confirmar acción',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d63031',
        cancelButtonColor: '#6c757d'
      }).then(function (result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
});
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
