<?php
$pageTitle = __('system_health.title');
$activeMenu = 'system_health';
require dirname(__DIR__) . '/layouts/main.php';

$statusMeta = [
    'success' => ['class' => 'success', 'icon' => 'ph-check-circle', 'label' => __('system_health.status_success')],
    'warning' => ['class' => 'warning', 'icon' => 'ph-warning-circle', 'label' => __('system_health.status_warning')],
    'danger' => ['class' => 'danger', 'icon' => 'ph-x-circle', 'label' => __('system_health.status_danger')],
    'info' => ['class' => 'info', 'icon' => 'ph-info', 'label' => __('system_health.status_info')],
];
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('system_health.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('system_health.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
      <a href="<?= BASE_URL ?>/system-health" class="btn btn-outline-primary btn-sm">
        <i class="ph-duotone ph-arrows-clockwise me-1"></i><?= __('system_health.refresh') ?>
      </a>
      <?php if (can('security_smtp.view')): ?>
      <a href="<?= BASE_URL ?>/security/smtp" class="btn btn-outline-secondary btn-sm"><?= __('system_health.go_to_smtp') ?></a>
      <?php endif; ?>
      <?php if (can('security_authentication.view')): ?>
      <a href="<?= BASE_URL ?>/security/authentication" class="btn btn-outline-secondary btn-sm"><?= __('system_health.go_to_authentication') ?></a>
      <?php endif; ?>
      <?php if (can('security_password_policy.view')): ?>
      <a href="<?= BASE_URL ?>/security/password-policy" class="btn btn-outline-secondary btn-sm"><?= __('system_health.go_to_password_policy') ?></a>
      <?php endif; ?>
      <?php if (can('security_sessions.view')): ?>
      <a href="<?= BASE_URL ?>/security/sessions" class="btn btn-outline-secondary btn-sm"><?= __('system_health.go_to_sessions') ?></a>
      <?php endif; ?>
      <?php if (can('audit_logs.view')): ?>
      <a href="<?= BASE_URL ?>/audit-logs" class="btn btn-outline-secondary btn-sm"><?= __('system_health.go_to_audit') ?></a>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <p class="text-muted mb-1"><?= __('system_health.success_checks') ?></p>
            <h3 class="mb-0 text-success"><?= (int)($summary['success'] ?? 0) ?></h3>
          </div>
          <i class="ph-duotone ph-check-circle text-success" style="font-size:2rem;"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <p class="text-muted mb-1"><?= __('system_health.warning_checks') ?></p>
            <h3 class="mb-0 text-warning"><?= (int)($summary['warning'] ?? 0) ?></h3>
          </div>
          <i class="ph-duotone ph-warning-circle text-warning" style="font-size:2rem;"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <p class="text-muted mb-1"><?= __('system_health.error_checks') ?></p>
            <h3 class="mb-0 text-danger"><?= (int)($summary['danger'] ?? 0) ?></h3>
          </div>
          <i class="ph-duotone ph-x-circle text-danger" style="font-size:2rem;"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <p class="text-muted mb-1"><?= __('system_health.total_checks') ?></p>
            <h3 class="mb-0"><?= (int)($summary['total'] ?? 0) ?></h3>
          </div>
          <i class="ph-duotone ph-list-checks text-info" style="font-size:2rem;"></i>
        </div>
      </div>
    </div>
  </div>

  <?php foreach ($sections as $section): ?>
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="min-width:220px;"><?= __('system_health.check') ?></th>
                <th style="width:150px;"><?= __('system_health.status') ?></th>
                <th style="min-width:180px;"><?= __('system_health.value') ?></th>
                <th><?= __('system_health.message') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($section['checks'] as $check): ?>
              <?php $meta = $statusMeta[$check['status']] ?? $statusMeta['info']; ?>
              <tr>
                <td class="fw-semibold"><?= htmlspecialchars($check['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <span class="badge bg-<?= $meta['class'] ?>-subtle text-<?= $meta['class'] ?> border border-<?= $meta['class'] ?>-subtle">
                    <i class="ph-duotone <?= $meta['icon'] ?> me-1"></i><?= $meta['label'] ?>
                  </span>
                </td>
                <td><code class="small text-wrap"><?= htmlspecialchars((string)$check['value'], ENT_QUOTES, 'UTF-8') ?></code></td>
                <td class="text-muted"><?= htmlspecialchars((string)$check['message'], ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
