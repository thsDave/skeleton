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
                    <div class="d-inline-flex gap-1">
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

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
