<?php
$pageTitle  = __('audit_logs.detail') . ' #' . (int)$log['id'];
$activeMenu = 'audit_logs';
require dirname(__DIR__) . '/layouts/main.php';

$statusColors = [
    'success' => 'success',
    'failed'  => 'danger',
    'denied'  => 'warning',
    'warning' => 'warning',
];

/**
 * Pretty-print a JSON string stored in the DB.
 * Returns escaped HTML or a fallback message.
 */
function prettyJson(?string $raw): string
{
    if ($raw === null || $raw === '') {
        return '<em class="text-muted">' . __('audit_logs.no_values') . '</em>';
    }
    $decoded = json_decode($raw, true);
    if ($decoded === null) {
        return '<em class="text-muted">' . __('audit_logs.no_values') . '</em>';
    }
    $pretty  = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return '<pre class="mb-0 small text-start" style="white-space:pre-wrap;word-break:break-all;">'
         . htmlspecialchars($pretty, ENT_QUOTES, 'UTF-8')
         . '</pre>';
}
?>

<!-- [ breadcrumb ] start -->
<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('audit_logs.detail') ?> #<?= (int)$log['id'] ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/audit-logs"><?= __('audit_logs.title') ?></a></li>
            <li class="breadcrumb-item active">#<?= (int)$log['id'] ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>
<!-- [ breadcrumb ] end -->

<div class="row">
  <!-- Información principal -->
  <div class="col-lg-7 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-info me-2"></i>
          <?= __('audit_logs.detail') ?>
        </h6>
      </div>
      <div class="card-body">

        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.created_at') ?></div>
          <div class="col-7"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></div>
        </div>

        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.user') ?></div>
          <div class="col-7">
            <?php if ($log['user_name']): ?>
              <div><?= htmlspecialchars($log['user_name'], ENT_QUOTES, 'UTF-8') ?></div>
              <small class="text-muted"><?= htmlspecialchars($log['user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
              <small class="text-muted ms-1">(ID: <?= (int)$log['user_id'] ?>)</small>
            <?php else: ?>
              <span class="text-muted">— <?= __('audit_logs.system_user') ?></span>
            <?php endif; ?>
          </div>
        </div>

        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.module') ?></div>
          <div class="col-7">
            <?= $log['module'] ? '<code>' . htmlspecialchars($log['module'], ENT_QUOTES, 'UTF-8') . '</code>' : '<span class="text-muted">—</span>' ?>
          </div>
        </div>

        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.action') ?></div>
          <div class="col-7">
            <?php $actionLabel = __('actions.' . ($log['action'] ?? '')); ?>
            <strong><?= htmlspecialchars($actionLabel ?: ($log['action'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if ($actionLabel): ?>
            <br><small class="text-muted"><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
          </div>
        </div>

        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.entity') ?></div>
          <div class="col-7">
            <?php if ($log['entity']): ?>
              <?= htmlspecialchars($log['entity'], ENT_QUOTES, 'UTF-8') ?>
              <?php if ($log['entity_id']): ?>
              <span class="badge bg-light text-dark ms-1">#<?= (int)$log['entity_id'] ?></span>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.status') ?></div>
          <div class="col-7">
            <?php $color = $statusColors[$log['status']] ?? 'secondary'; ?>
            <span class="badge bg-<?= $color ?> fs-6">
              <?= __('statuses.' . ($log['status'] ?? '')) ?: htmlspecialchars($log['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </span>
          </div>
        </div>

        <?php if (!empty($log['description'])): ?>
        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.description_text') ?></div>
          <div class="col-7"><?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- Información técnica -->
  <div class="col-lg-5 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-terminal-window me-2"></i>
          <?= __('audit_logs.technical') ?>
        </h6>
      </div>
      <div class="card-body">

        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.ip_address') ?></div>
          <div class="col-7"><code><?= htmlspecialchars($log['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></code></div>
        </div>

        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.method') ?></div>
          <div class="col-7">
            <?php if ($log['method']): ?>
            <span class="badge bg-secondary"><?= htmlspecialchars($log['method'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="row py-2 border-bottom">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.route') ?></div>
          <div class="col-7">
            <code class="small" style="word-break:break-all;">
              <?= htmlspecialchars($log['route'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </code>
          </div>
        </div>

        <div class="row py-2">
          <div class="col-5 text-muted fw-semibold"><?= __('audit_logs.user_agent') ?></div>
          <div class="col-7">
            <small style="word-break:break-all;">
              <?= htmlspecialchars($log['user_agent'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </small>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Valores anteriores y nuevos -->
<div class="row">
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-arrow-counter-clockwise me-2 text-warning"></i>
          <?= __('audit_logs.old_values') ?>
        </h6>
      </div>
      <div class="card-body">
        <?= prettyJson($log['old_values'] ?? null) ?>
      </div>
    </div>
  </div>

  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-arrow-clockwise me-2 text-success"></i>
          <?= __('audit_logs.new_values') ?>
        </h6>
      </div>
      <div class="card-body">
        <?= prettyJson($log['new_values'] ?? null) ?>
      </div>
    </div>
  </div>
</div>

<div class="mb-4">
  <a href="<?= BASE_URL ?>/audit-logs" class="btn btn-outline-secondary">
    <i class="ph-duotone ph-arrow-left me-1"></i>
    <?= __('audit_logs.back') ?>
  </a>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
