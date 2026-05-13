<?php
$pageTitle = __('maintenance.cleanup');
$activeMenu = 'maintenance_cleanup';
require dirname(__DIR__) . '/layouts/main.php';

$statusMeta = [
    'success' => ['class' => 'success', 'icon' => 'ph-check-circle'],
    'warning' => ['class' => 'warning', 'icon' => 'ph-warning-circle'],
    'info' => ['class' => 'info', 'icon' => 'ph-info'],
    'failed' => ['class' => 'danger', 'icon' => 'ph-x-circle'],
    'skipped' => ['class' => 'secondary', 'icon' => 'ph-minus-circle'],
];
$totalCleanable = array_sum(array_map(static fn(array $item): int => (int)($item['count'] ?? 0), $items));
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('maintenance.cleanup') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('maintenance.cleanup') ?></li>
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
          <h5 class="mb-1"><?= __('maintenance.temporary_data') ?></h5>
          <p class="text-muted mb-0"><?= __('maintenance.cleanup_description') ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
            <?= __('maintenance.cleanup_summary') ?>: <?= (int)$totalCleanable ?>
          </span>
          <span class="badge bg-info-subtle text-info border border-info-subtle">
            <?= __('maintenance.last_cleanup') ?>:
            <?= $lastCleanup ? htmlspecialchars(date('d/m/Y H:i', strtotime($lastCleanup)), ENT_QUOTES, 'UTF-8') : __('system_health.not_available') ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($result)): ?>
  <div class="col-12">
    <div class="card border-info">
      <div class="card-header">
        <h5 class="mb-0"><i class="ph-duotone ph-list-checks me-2"></i><?= __('maintenance.cleanup_completed') ?></h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php foreach ($result as $key => $item): ?>
          <?php $meta = $statusMeta[$item['status'] ?? 'info'] ?? $statusMeta['info']; ?>
          <div class="col-md-6 col-xl-4">
            <div class="border rounded p-3 h-100">
              <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <strong><?= __('maintenance.' . $key) ?></strong>
                <span class="badge bg-<?= $meta['class'] ?>-subtle text-<?= $meta['class'] ?> border border-<?= $meta['class'] ?>-subtle">
                  <i class="ph-duotone <?= $meta['icon'] ?> me-1"></i><?= htmlspecialchars((string)($item['status'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>
                </span>
              </div>
              <div class="text-muted small"><?= __('maintenance.records_deleted') ?>: <?= (int)($item['deleted'] ?? 0) ?></div>
              <div class="text-muted small"><?= htmlspecialchars((string)($item['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="col-12">
    <form action="<?= BASE_URL ?>/maintenance/cleanup/run" method="POST" id="cleanupForm">
      <?= \Core\CSRF::field() ?>
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0"><i class="ph-duotone ph-broom me-2 text-primary"></i><?= __('maintenance.cleanup_selected') ?></h5>
          <?php if (can('maintenance.cleanup.run')): ?>
          <button type="submit" class="btn btn-danger btn-sm">
            <i class="ph-duotone ph-trash me-1"></i><?= __('maintenance.run_cleanup') ?>
          </button>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="alert alert-warning" role="alert">
            <strong><?= __('maintenance.no_critical_data_deleted') ?></strong>
            <div class="small mt-1"><?= __('maintenance.no_active_sessions_deleted') ?></div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:54px;"></th>
                  <th><?= __('maintenance.temporary_data') ?></th>
                  <th><?= __('maintenance.records_found') ?></th>
                  <th><?= __('maintenance.retention_days') ?></th>
                  <th><?= __('system_health.status') ?></th>
                  <th><?= __('system_health.message') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $key => $item): ?>
                <?php
                  $status = $item['status'] ?? 'info';
                  $meta = $statusMeta[$status] ?? $statusMeta['info'];
                  $available = !empty($item['available']);
                  $count = (int)($item['count'] ?? 0);
                  $canSelect = $available && $count > 0 && can('maintenance.cleanup.run');
                ?>
                <tr>
                  <td>
                    <input class="form-check-input cleanup-checkbox" type="checkbox" name="items[]"
                           value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                           <?= $canSelect ? '' : 'disabled' ?>>
                  </td>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-muted"><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></small>
                  </td>
                  <td>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                      <?= $count ?>
                      <?= ($item['unit'] ?? 'records') === 'files' ? __('maintenance.files_found') : __('maintenance.records_found') ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($key === 'password_histories'): ?>
                      <span class="text-muted">-</span>
                    <?php else: ?>
                      <input type="number" class="form-control form-control-sm" style="max-width:120px;"
                             name="retention[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]"
                             min="1" max="3650"
                             value="<?= (int)($item['retention_days'] ?? $retention[$key] ?? 1) ?>">
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-<?= $meta['class'] ?>-subtle text-<?= $meta['class'] ?> border border-<?= $meta['class'] ?>-subtle">
                      <i class="ph-duotone <?= $meta['icon'] ?> me-1"></i><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="text-muted"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<?php
$confirmTitle = json_encode(__('maintenance.confirm_cleanup'), JSON_UNESCAPED_UNICODE);
$noItems = json_encode(__('maintenance.cleanup_no_items'), JSON_UNESCAPED_UNICODE);
$extraScript = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('cleanupForm');
  if (!form) return;

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var checked = form.querySelectorAll('.cleanup-checkbox:checked');
    if (!checked.length) {
      Swal.fire({ icon: 'info', text: {$noItems} });
      return;
    }

    Swal.fire({
      icon: 'warning',
      title: {$confirmTitle},
      text: 'No se eliminaran usuarios, roles, permisos ni sesiones activas.',
      showCancelButton: true,
      confirmButtonText: 'Ejecutar limpieza',
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
</script>
JS;
?>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
