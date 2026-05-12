<?php
$pageTitle = __('sessions.user_session_history');
$activeMenu = 'users';
require dirname(__DIR__) . '/layouts/main.php';
$userName = trim((string)($user['nombres'] ?? '') . ' ' . (string)($user['apellidos'] ?? ''));
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('sessions.user_session_history') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <a href="<?= BASE_URL ?>/users" class="btn btn-outline-secondary btn-sm">
          <i class="ph-duotone ph-arrow-left me-1"></i><?= __('sessions.back_to_users') ?>
        </a>
      </div>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body d-flex flex-wrap justify-content-between gap-3">
    <div>
      <div class="fw-semibold"><?= htmlspecialchars($userName !== '' ? $userName : ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
      <div class="text-muted small"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span class="badge bg-secondary"><?= htmlspecialchars($user['status_name'] ?? ($user['status_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
      <?php if (can('users.sessions.revoke_all')): ?>
        <form action="<?= BASE_URL ?>/users/<?= (int)$user['id'] ?>/sessions/revoke-all" method="POST" class="js-confirm-session-action">
          <?= \Core\CSRF::field() ?>
          <input type="hidden" name="confirm_message" value="<?= htmlspecialchars(__('sessions.confirm_revoke_all_user_sessions'), ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="ph-duotone ph-sign-out me-1"></i><?= __('sessions.close_all_sessions') ?>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="ph-duotone ph-clock-counter-clockwise me-2 text-primary"></i><?= __('users.session_history') ?></h5>
  </div>
  <div class="card-body">
    <form method="GET" action="<?= BASE_URL ?>/users/<?= (int)$user['id'] ?>/sessions" class="row g-2 align-items-end mb-3">
      <div class="col-sm-4 col-md-3">
        <label for="status" class="form-label"><?= __('sessions.status') ?></label>
        <select id="status" name="status" class="form-select">
          <option value=""><?= __('sessions.filter_all') ?></option>
          <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= __('sessions.active') ?></option>
          <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>><?= __('sessions.closed') ?></option>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary">
          <i class="ph-duotone ph-funnel me-1"></i><?= __('buttons.filter') ?>
        </button>
      </div>
    </form>

    <?php if (empty($sessions)): ?>
      <div class="text-center text-muted py-4"><?= __('sessions.no_history') ?></div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th><?= __('sessions.started_at') ?></th>
              <th><?= __('sessions.last_activity') ?></th>
              <th><?= __('sessions.ip_address') ?></th>
              <th><?= __('sessions.browser') ?></th>
              <th><?= __('sessions.platform') ?></th>
              <th><?= __('sessions.device_type') ?></th>
              <th><?= __('sessions.status') ?></th>
              <th><?= __('sessions.revoked_by') ?></th>
              <th><?= __('sessions.revoke_reason') ?></th>
              <th class="text-end"><?= __('sessions.actions') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sessions as $session): ?>
              <?php $revokedBy = trim((string)($session['revoked_by_name'] ?? '')) ?: ($session['revoked_by_email'] ?? '-'); ?>
              <tr>
                <td><?= !empty($session['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($session['created_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                <td><?= !empty($session['last_activity_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($session['last_activity_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                <td><?= htmlspecialchars($session['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($session['browser'] ?? __('sessions.unknown_browser'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($session['platform'] ?? __('sessions.unknown_platform'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($session['device_type'] ?? __('sessions.unknown_device'), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <span class="badge bg-<?= !empty($session['is_current']) ? 'primary' : (empty($session['revoked_at']) ? 'success' : 'secondary') ?>">
                    <?= htmlspecialchars($session['display_status'], ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($revokedBy, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($session['display_reason'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-end">
                  <?php if (can('users.sessions.revoke') && empty($session['revoked_at']) && empty($session['is_current'])): ?>
                    <form action="<?= BASE_URL ?>/users/sessions/revoke/<?= (int)$session['id'] ?>" method="POST" class="d-inline js-confirm-session-action">
                      <?= \Core\CSRF::field() ?>
                      <input type="hidden" name="confirm_message" value="<?= htmlspecialchars(__('sessions.confirm_revoke_session'), ENT_QUOTES, 'UTF-8') ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="ph-duotone ph-x-circle me-1"></i><?= __('sessions.close_session') ?>
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small">-</span>
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
        title: input ? input.value : 'Confirmar accion',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d63031',
        cancelButtonColor: '#6c757d'
      }).then(function (result) {
        if (result.isConfirmed) form.submit();
      });
    });
  });
});
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
