<?php
$pageTitle  = __('audit_logs.title');
$activeMenu = 'audit_logs';
require dirname(__DIR__) . '/layouts/main.php';

$statusColors = [
    'success' => 'success',
    'failed'  => 'danger',
    'denied'  => 'warning',
    'warning' => 'warning',
];
?>

<!-- [ breadcrumb ] start -->
<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('audit_logs.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('audit_logs.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>
<!-- [ breadcrumb ] end -->

<div class="row">

  <!-- ─── Filtros ─────────────────────────────────────────────────────────── -->
  <div class="col-12">
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-funnel me-2"></i><?= __('audit_logs.filters') ?>
        </h6>
      </div>
      <div class="card-body">
        <form method="GET" action="<?= BASE_URL ?>/audit-logs" id="filterForm">
          <div class="row g-2 align-items-end">

            <div class="col-md-3 col-sm-6">
              <label class="form-label"><?= __('audit_logs.user') ?></label>
              <select name="user_id" class="form-select select2" id="filterUser">
                <option value=""><?= __('audit_logs.filter_all') ?></option>
                <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>"
                  <?= ((string)($filters['user_id'] ?? '')) === ((string)$u['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos'], ENT_QUOTES, 'UTF-8') ?>
                  (<?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-2 col-sm-6">
              <label class="form-label"><?= __('audit_logs.module') ?></label>
              <select name="module" class="form-select select2" id="filterModule">
                <option value=""><?= __('audit_logs.filter_all') ?></option>
                <?php foreach ($modules as $mod): ?>
                <option value="<?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?>"
                  <?= ($filters['module'] ?? '') === $mod ? 'selected' : '' ?>>
                  <?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-2 col-sm-6">
              <label class="form-label"><?= __('audit_logs.action') ?></label>
              <select name="action" class="form-select select2" id="filterAction">
                <option value=""><?= __('audit_logs.filter_all') ?></option>
                <?php foreach ($actions as $act): ?>
                <option value="<?= htmlspecialchars($act, ENT_QUOTES, 'UTF-8') ?>"
                  <?= ($filters['action'] ?? '') === $act ? 'selected' : '' ?>>
                  <?= htmlspecialchars(__('actions.' . $act) ?: $act, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-2 col-sm-6">
              <label class="form-label"><?= __('audit_logs.status') ?></label>
              <select name="status" class="form-select select2" id="filterStatus">
                <option value=""><?= __('audit_logs.filter_all') ?></option>
                <?php foreach (['success', 'failed', 'denied', 'warning'] as $st): ?>
                <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>>
                  <?= __('statuses.' . $st) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-1 col-sm-6">
              <label class="form-label"><?= __('audit_logs.date_from') ?></label>
              <input type="date" name="date_from" class="form-control"
                     value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-1 col-sm-6">
              <label class="form-label"><?= __('audit_logs.date_to') ?></label>
              <input type="date" name="date_to" class="form-control"
                     value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-1 col-sm-6 d-flex gap-1">
              <button type="submit" class="btn btn-primary w-100" title="<?= __('buttons.filter') ?>">
                <i class="ph-duotone ph-magnifying-glass"></i>
              </button>
              <a href="<?= BASE_URL ?>/audit-logs" class="btn btn-outline-secondary w-100"
                 title="<?= __('audit_logs.clear_filters') ?>">
                <i class="ph-duotone ph-x"></i>
              </a>
            </div>

          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ─── Tabla de registros ───────────────────────────────────────────────── -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0">
          <i class="ph-duotone ph-clipboard-text me-2"></i><?= __('audit_logs.list') ?>
        </h5>
        <small class="text-muted">
          <?= __('audit_logs.showing', ['n' => count($logs), 'total' => $total]) ?>
        </small>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="auditLogsTable" class="table table-hover align-middle" style="width:100%">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th><?= __('audit_logs.created_at') ?></th>
                <th><?= __('audit_logs.user') ?></th>
                <th><?= __('audit_logs.module') ?></th>
                <th><?= __('audit_logs.action') ?></th>
                <th><?= __('audit_logs.entity') ?></th>
                <th><?= __('audit_logs.status') ?></th>
                <th><?= __('audit_logs.ip_address') ?></th>
                <th class="text-center"><?= __('common.actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
              <tr>
                <td><?= (int)$log['id'] ?></td>
                <td data-order="<?= htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8') ?>">
                  <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                </td>
                <td>
                  <?php if (!empty($log['user_name'])): ?>
                    <div class="fw-semibold lh-sm"><?= htmlspecialchars($log['user_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-muted"><?= htmlspecialchars($log['user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                  <?php else: ?>
                    <span class="text-muted">— <?= __('audit_logs.system_user') ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($log['module'])): ?>
                    <code class="small"><?= htmlspecialchars($log['module'], ENT_QUOTES, 'UTF-8') ?></code>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php $actionLabel = __('actions.' . ($log['action'] ?? '')); ?>
                  <span><?= htmlspecialchars($actionLabel !== ('actions.' . ($log['action'] ?? '')) ? $actionLabel : ($log['action'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td>
                  <?php if (!empty($log['entity'])): ?>
                    <span><?= htmlspecialchars($log['entity'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($log['entity_id'])): ?>
                      <span class="badge bg-light text-dark ms-1">#<?= (int)$log['entity_id'] ?></span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php $color = $statusColors[$log['status'] ?? ''] ?? 'secondary'; ?>
                  <span class="badge bg-<?= $color ?>">
                    <?php
                    $stKey = __('statuses.' . ($log['status'] ?? ''));
                    echo htmlspecialchars(
                        ($stKey !== ('statuses.' . ($log['status'] ?? ''))) ? $stKey : ($log['status'] ?? '—'),
                        ENT_QUOTES, 'UTF-8'
                    ); ?>
                  </span>
                </td>
                <td>
                  <code class="small"><?= htmlspecialchars($log['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></code>
                </td>
                <td class="text-center">
                  <a href="<?= BASE_URL ?>/audit-logs/show/<?= (int)$log['id'] ?>"
                     class="btn btn-sm btn-outline-primary"
                     title="<?= __('audit_logs.view_detail') ?>">
                    <i class="ph-duotone ph-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($logs)): ?>
              <tr>
                <td colspan="9" class="text-center text-muted py-5">
                  <i class="ph-duotone ph-clipboard-text" style="font-size:2.5rem;"></i>
                  <p class="mb-0 mt-2"><?= __('audit_logs.no_records') ?></p>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div><!-- /row -->

<?php
$extraScript = <<<'JS'
<script>
$(document).ready(function () {
  $('#auditLogsTable').DataTable({
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
    },
    pageLength: 25,
    order: [[0, 'desc']],
    columnDefs: [
      { orderable: false, targets: [-1] }
    ]
  });
});
</script>
JS;
?>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
