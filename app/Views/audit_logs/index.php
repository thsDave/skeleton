<?php
$pageTitle  = __('audit_logs.title');
$activeMenu = 'audit_logs';
require dirname(__DIR__) . '/layouts/main.php';

$activeTab = $activeTab ?? 'records';
$statusColors = [
    'success' => 'success',
    'failed'  => 'danger',
    'denied'  => 'warning',
    'warning' => 'warning',
];
$severityColors = [
    'info' => 'info',
    'success' => 'success',
    'warning' => 'warning',
    'critical' => 'danger',
];
$exportQuery = http_build_query(array_filter($filters ?? [], static fn($value) => $value !== '' && $value !== null));
$exportUrl   = BASE_URL . '/audit-logs/export/excel' . ($exportQuery ? '?' . $exportQuery : '');
$recordsQuery = http_build_query(array_filter(array_merge($filters ?? [], ['tab' => 'records']), static fn($value) => $value !== '' && $value !== null));
$glossaryQuery = http_build_query(array_filter(array_merge($glossaryFilters ?? [], ['tab' => 'glossary']), static fn($value) => $value !== '' && $value !== null));
$recordsUrl = BASE_URL . '/audit-logs' . ($recordsQuery ? '?' . $recordsQuery : '');
$glossaryUrl = BASE_URL . '/audit-logs' . ($glossaryQuery ? '?' . $glossaryQuery : '?tab=glossary');
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
  <div class="col-12">
    <div class="card mb-3">
      <div class="card-body py-2">
        <ul class="nav nav-pills flex-column flex-sm-row gap-2" role="tablist">
          <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'records' ? 'active' : '' ?>"
               href="<?= htmlspecialchars($recordsUrl, ENT_QUOTES, 'UTF-8') ?>">
              <i class="ph-duotone ph-clipboard-text me-1"></i><?= __('audit.tabs.records') ?>
            </a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'glossary' ? 'active' : '' ?>"
               href="<?= htmlspecialchars($glossaryUrl, ENT_QUOTES, 'UTF-8') ?>">
              <i class="ph-duotone ph-book-open-text me-1"></i><?= __('audit.tabs.glossary') ?>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <?php if ($activeTab === 'records'): ?>
  <div class="col-12">
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-funnel me-2"></i><?= __('audit_logs.filters') ?>
        </h6>
      </div>
      <div class="card-body">
        <form method="GET" action="<?= BASE_URL ?>/audit-logs" id="filterForm">
          <input type="hidden" name="tab" value="records">
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
                <?php $actionInfo = $auditGlossary[$act] ?? null; ?>
                <option value="<?= htmlspecialchars($act, ENT_QUOTES, 'UTF-8') ?>"
                  <?= ($filters['action'] ?? '') === $act ? 'selected' : '' ?>>
                  <?= htmlspecialchars($actionInfo['title'] ?? $act, ENT_QUOTES, 'UTF-8') ?>
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
              <a href="<?= BASE_URL ?>/audit-logs?tab=records" class="btn btn-outline-secondary w-100"
                 title="<?= __('audit_logs.clear_filters') ?>">
                <i class="ph-duotone ph-x"></i>
              </a>
            </div>

          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0">
          <i class="ph-duotone ph-clipboard-text me-2"></i><?= __('audit_logs.list') ?>
        </h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <small class="text-muted">
            <?= __('audit_logs.showing', ['n' => count($logs), 'total' => $total]) ?>
          </small>
          <?php if (can('audit_logs.export')): ?>
          <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-success btn-sm">
            <i class="ph-duotone ph-file-xls me-1"></i><?= __('audit.export_excel') ?>
          </a>
          <?php endif; ?>
        </div>
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
              <?php
                $actionKey = (string)($log['action'] ?? '');
                $actionInfo = $auditGlossary[$actionKey] ?? null;
                $severity = $actionInfo['severity'] ?? null;
                $severityColor = $severityColors[$severity] ?? 'secondary';
              ?>
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
                    <span class="text-muted">&mdash; <?= __('audit_logs.system_user') ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($log['module'])): ?>
                    <code class="small"><?= htmlspecialchars($log['module'], ENT_QUOTES, 'UTF-8') ?></code>
                  <?php else: ?>
                    <span class="text-muted">&mdash;</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($actionInfo): ?>
                    <div class="d-flex align-items-start gap-2">
                      <div>
                        <div class="fw-semibold lh-sm"><?= htmlspecialchars($actionInfo['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <code class="small text-muted"><?= htmlspecialchars($actionKey, ENT_QUOTES, 'UTF-8') ?></code>
                      </div>
                      <span class="badge bg-<?= $severityColor ?>-subtle text-<?= $severityColor ?> border border-<?= $severityColor ?>-subtle">
                        <?= htmlspecialchars(__('audit.severity_' . $severity), ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </div>
                    <small class="text-muted d-block mt-1">
                      <?= htmlspecialchars($actionInfo['description'], ENT_QUOTES, 'UTF-8') ?>
                    </small>
                  <?php else: ?>
                    <code class="small"><?= htmlspecialchars($actionKey ?: '-', ENT_QUOTES, 'UTF-8') ?></code>
                    <small class="text-muted d-block"><?= __('audit.no_glossary_description') ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($log['entity'])): ?>
                    <span><?= htmlspecialchars($log['entity'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($log['entity_id'])): ?>
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1">#<?= (int)$log['entity_id'] ?></span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">&mdash;</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php $color = $statusColors[$log['status'] ?? ''] ?? 'secondary'; ?>
                  <span class="badge bg-<?= $color ?>">
                    <?php
                    $stKey = __('statuses.' . ($log['status'] ?? ''));
                    echo htmlspecialchars(
                        ($stKey !== ('statuses.' . ($log['status'] ?? ''))) ? $stKey : ($log['status'] ?? '-'),
                        ENT_QUOTES, 'UTF-8'
                    ); ?>
                  </span>
                </td>
                <td>
                  <code class="small"><?= htmlspecialchars($log['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8') ?></code>
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
  <?php else: ?>
  <div class="col-12">
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="ph-duotone ph-book-open-text me-2"></i><?= __('audit.action_glossary') ?>
        </h5>
      </div>
      <div class="card-body">
        <form method="GET" action="<?= BASE_URL ?>/audit-logs">
          <input type="hidden" name="tab" value="glossary">
          <div class="row g-2 align-items-end">
            <div class="col-md-5">
              <label class="form-label"><?= __('audit.search_event') ?></label>
              <input type="search" name="glossary_q" class="form-control"
                     value="<?= htmlspecialchars($glossaryFilters['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     placeholder="<?= __('audit.search_event') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label"><?= __('audit.filter_by_module') ?></label>
              <select name="glossary_module" class="form-select">
                <option value=""><?= __('audit_logs.filter_all') ?></option>
                <?php foreach ($glossaryModules as $module): ?>
                <option value="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>"
                  <?= ($glossaryFilters['module'] ?? '') === $module ? 'selected' : '' ?>>
                  <?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label"><?= __('audit.filter_by_severity') ?></label>
              <select name="glossary_severity" class="form-select">
                <option value=""><?= __('audit_logs.filter_all') ?></option>
                <?php foreach ($glossarySeverities as $severity): ?>
                <option value="<?= htmlspecialchars($severity, ENT_QUOTES, 'UTF-8') ?>"
                  <?= ($glossaryFilters['severity'] ?? '') === $severity ? 'selected' : '' ?>>
                  <?= htmlspecialchars(__('audit.severity_' . $severity), ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
              <button type="submit" class="btn btn-primary w-100" title="<?= __('buttons.filter') ?>">
                <i class="ph-duotone ph-magnifying-glass"></i>
              </button>
              <a href="<?= BASE_URL ?>/audit-logs?tab=glossary" class="btn btn-outline-secondary w-100"
                 title="<?= __('audit_logs.clear_filters') ?>">
                <i class="ph-duotone ph-x"></i>
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table id="auditGlossaryTable" class="table table-hover align-middle" style="width:100%">
            <thead class="table-light">
              <tr>
                <th><?= __('audit.event_key') ?></th>
                <th><?= __('audit.event_title') ?></th>
                <th><?= __('audit.event_module') ?></th>
                <th><?= __('audit.event_severity') ?></th>
                <th><?= __('audit.event_description') ?></th>
                <th><?= __('audit.analysis_hint') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filteredGlossary as $eventKey => $event): ?>
              <?php
                $severity = $event['severity'] ?? 'info';
                $severityColor = $severityColors[$severity] ?? 'secondary';
              ?>
              <tr>
                <td><code class="small"><?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?></code></td>
                <td class="fw-semibold"><?= htmlspecialchars($event['title'] ?? $eventKey, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($event['module'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <span class="badge bg-<?= $severityColor ?>-subtle text-<?= $severityColor ?> border border-<?= $severityColor ?>-subtle">
                    <?= htmlspecialchars(__('audit.severity_' . $severity), ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($event['description'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($event['analysis_hint'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (empty($filteredGlossary)): ?>
        <div class="text-center text-muted py-4">
          <i class="ph-duotone ph-book-open-text" style="font-size:2.5rem;"></i>
          <p class="mb-0 mt-2"><?= __('audit.no_glossary_description') ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!empty($unknownActions)): ?>
  <div class="col-12">
    <div class="card mt-3 border-warning">
      <div class="card-header">
        <h6 class="mb-0 text-warning">
          <i class="ph-duotone ph-warning-circle me-2"></i><?= __('audit.detected_without_glossary') ?>
        </h6>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($unknownActions as $unknownAction): ?>
          <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
            <?= htmlspecialchars($unknownAction, ENT_QUOTES, 'UTF-8') ?>
          </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php
$dataTableSelector = $activeTab === 'glossary' ? '#auditGlossaryTable' : '#auditLogsTable';
$extraScript = <<<JS
<script>
$(document).ready(function () {
  if ($.fn.DataTable.isDataTable('{$dataTableSelector}')) {
    $('{$dataTableSelector}').DataTable().destroy();
  }

  $('{$dataTableSelector}').DataTable({
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
