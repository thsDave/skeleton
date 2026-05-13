<?php
$pageTitle = __('notifications.title');
$activeMenu = '';
require dirname(__DIR__) . '/layouts/main.php';

$statusMeta = [
    'info' => ['class' => 'info', 'icon' => 'ph-info'],
    'success' => ['class' => 'success', 'icon' => 'ph-check-circle'],
    'warning' => ['class' => 'warning', 'icon' => 'ph-warning-circle'],
    'danger' => ['class' => 'danger', 'icon' => 'ph-x-circle'],
];
$readCount = (int)($readCount ?? 0);
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('notifications.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('notifications.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <h5 class="mb-1"><i class="ph-duotone ph-bell-ringing me-2 text-primary"></i><?= __('notifications.notifications') ?></h5>
          <p class="text-muted mb-0"><?= __('notifications.recent') ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= BASE_URL ?>/notifications?status=all" class="btn btn-sm <?= $status === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= __('notifications.all') ?>
          </a>
          <a href="<?= BASE_URL ?>/notifications?status=unread" class="btn btn-sm <?= $status === 'unread' ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= __('notifications.unread') ?>
          </a>
          <a href="<?= BASE_URL ?>/notifications?status=read" class="btn btn-sm <?= $status === 'read' ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= __('notifications.read') ?>
          </a>
          <?php if ((int)$unreadCount > 0 && can('notifications.mark_read')): ?>
          <form action="<?= BASE_URL ?>/notifications/mark-all-read" method="POST" class="d-inline">
            <?= \Core\CSRF::field() ?>
            <button type="submit" class="btn btn-sm btn-outline-success">
              <i class="ph-duotone ph-checks me-1"></i><?= __('notifications.mark_all_as_read') ?>
            </button>
          </form>
          <?php endif; ?>
          <?php if ($readCount > 0): ?>
          <form action="<?= BASE_URL ?>/notifications/delete-read" method="POST" class="d-inline js-confirm-notification-delete">
            <?= \Core\CSRF::field() ?>
            <input type="hidden" name="confirm_message" value="<?= htmlspecialchars(__('notifications.confirm_delete_read'), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="ph-duotone ph-trash me-1"></i><?= __('notifications.delete_read') ?>
            </button>
          </form>
          <?php endif; ?>
          <?php if (!empty($notifications)): ?>
          <form action="<?= BASE_URL ?>/notifications/delete-all" method="POST" class="d-inline js-confirm-notification-delete">
            <?= \Core\CSRF::field() ?>
            <input type="hidden" name="confirm_message" value="<?= htmlspecialchars(__('notifications.confirm_delete_all'), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-sm btn-danger text-white">
              <i class="ph-duotone ph-trash-simple me-1"></i><?= __('notifications.delete_all') ?>
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <?php if (empty($notifications)): ?>
          <div class="text-center py-5 text-muted">
            <i class="ph-duotone ph-bell-slash d-block mb-2" style="font-size:2.5rem;"></i>
            <?= __('notifications.no_notifications') ?>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th><?= __('notifications.notifications') ?></th>
                  <th><?= __('notifications.type') ?></th>
                  <th><?= __('notifications.date') ?></th>
                  <th><?= __('notifications.status') ?></th>
                  <th class="text-end"><?= __('common.actions') ?></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($notifications as $notification): ?>
                <?php
                  $severity = $notification['severity'] ?? 'info';
                  $meta = $statusMeta[$severity] ?? $statusMeta['info'];
                  $isUnread = empty($notification['read_at']);
                ?>
                <tr class="<?= $isUnread ? 'table-active' : '' ?>">
                  <td>
                    <div class="d-flex align-items-start gap-3">
                      <span class="avatar avatar-sm rounded-circle bg-<?= $meta['class'] ?>-subtle text-<?= $meta['class'] ?> d-inline-flex align-items-center justify-content-center">
                        <i class="ph-duotone <?= htmlspecialchars($notification['icon'] ?: $meta['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                      </span>
                      <div>
                        <div class="fw-semibold"><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8') ?></div>
                      </div>
                    </div>
                  </td>
                  <td><code class="small"><?= htmlspecialchars($notification['type'], ENT_QUOTES, 'UTF-8') ?></code></td>
                  <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($notification['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <span class="badge bg-<?= $isUnread ? 'warning' : 'success' ?>-subtle text-<?= $isUnread ? 'warning' : 'success' ?> border border-<?= $isUnread ? 'warning' : 'success' ?>-subtle">
                      <?= $isUnread ? __('notifications.unread') : __('notifications.read') ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                      <a href="<?= BASE_URL ?>/notifications/read/<?= (int)$notification['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="ph-duotone ph-arrow-square-out me-1"></i><?= __('notifications.open') ?>
                      </a>
                      <?php if ($isUnread && can('notifications.mark_read')): ?>
                      <form action="<?= BASE_URL ?>/notifications/mark-read/<?= (int)$notification['id'] ?>" method="POST">
                        <?= \Core\CSRF::field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-success">
                          <i class="ph-duotone ph-check me-1"></i><?= __('notifications.mark_as_read') ?>
                        </button>
                      </form>
                      <?php endif; ?>
                      <form action="<?= BASE_URL ?>/notifications/delete/<?= (int)$notification['id'] ?>" method="POST" class="js-confirm-notification-delete">
                        <?= \Core\CSRF::field() ?>
                        <input type="hidden" name="confirm_message" value="<?= htmlspecialchars(__('notifications.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= htmlspecialchars(__('notifications.delete_notification'), ENT_QUOTES, 'UTF-8') ?>">
                          <i class="ph-duotone ph-trash me-1"></i><?= __('notifications.delete') ?>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$confirmTitle = json_encode(__('notifications.delete_notification'), JSON_UNESCAPED_UNICODE);
$confirmButton = json_encode(__('alerts.confirm_yes'), JSON_UNESCAPED_UNICODE);
$cancelButton = json_encode(__('alerts.cancel'), JSON_UNESCAPED_UNICODE);
$extraScript = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-confirm-notification-delete').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var input = form.querySelector('[name="confirm_message"]');
      Swal.fire({
        icon: 'warning',
        title: {$confirmTitle},
        text: input ? input.value : '',
        showCancelButton: true,
        confirmButtonText: {$confirmButton},
        cancelButtonText: {$cancelButton},
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
