<?php
require dirname(__DIR__) . '/layouts/main.php';

// ─── Derived calculations ────────────────────────────────────────────────────

$smtpStatus = 'not_configured';
if (!empty($smtpData['host'] ?? '')) {
    $smtpStatus = !empty($smtpData['is_verified']) ? 'verified' : 'unverified';
}
$smtpBadgeClass = match($smtpStatus) {
    'verified'       => 'success',
    'unverified'     => 'warning',
    default          => 'secondary',
};

$sessionEnabled = (bool)($sessionSettings['session_lock_enabled'] ?? true);
$sessionMins    = (int)(($sessionSettings['session_inactivity_seconds'] ?? 900) / 60);

$total      = (int)($userStats['total']        ?? 0);
$active     = (int)($userStats['active']       ?? 0);
$inactive   = (int)($userStats['inactive']     ?? 0);
$blocked    = (int)($userStats['blocked']      ?? 0);
$statusActive = (int)($userStats['status_active'] ?? $active);
$statusInactive = (int)($userStats['status_inactive'] ?? $inactive);
$withMfa    = (int)($userStats['with_mfa']     ?? 0);
$withoutMfa = (int)($userStats['without_mfa']  ?? 0);
$withImage  = (int)($userStats['with_image']   ?? 0);
$withoutImg = (int)($userStats['without_image']?? 0);
$incomplete = (int)($userStats['incomplete']   ?? 0);
$activePct  = $total > 0 ? (int) round(($active / $total) * 100) : 0;
$inactivePct = $total > 0 ? (int) round(($inactive / $total) * 100) : 0;
$mfaPct     = $total > 0 ? (int) round(($withMfa / $total) * 100) : 0;
$profilePct = $total > 0 ? (int) round(($withImage / $total) * 100) : 0;
$lastUpdated = date('d/m/Y H:i');

// Chart data arrays (no SMS)
$chartRoleLabels       = array_values(array_column($roleStats, 'role_name'));
$chartRoleCounts       = array_values(array_map('intval', array_column($roleStats, 'user_count')));
$chartMfaMethodLabels  = [];
$chartMfaMethodCounts  = [];
foreach ($mfaMethodStats as $m) {
    $label = $m['two_factor_method'] === 'email'
        ? __('dashboard.email_mfa')
        : __('dashboard.authenticator_mfa');
    $chartMfaMethodLabels[] = $label;
    $chartMfaMethodCounts[] = (int)$m['count'];
}

// Avatar
$_avatar = current_user_avatar_url($freshUser ?: $authUser);
$_name   = htmlspecialchars(
    trim(($freshUser['nombres'] ?? $authUser['nombres'] ?? '') . ' ' . ($freshUser['apellidos'] ?? $authUser['apellidos'] ?? '')),
    ENT_QUOTES, 'UTF-8'
);
$_email  = htmlspecialchars($authUser['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<!-- [ Page header ] -->
<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('dashboard.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active"><?= __('dashboard.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="dashboard-shell">

<!-- [ Welcome card ] -->
<div class="row mb-3">
  <div class="col-12">
    <div class="card border-0 shadow-sm dashboard-hero">
      <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
          <div class="flex-shrink-0">
              <img src="<?= $_avatar ?>" alt="avatar"
                   class="rounded-circle dashboard-avatar"
                   onerror="this.src='<?= BASE_URL ?>/assets/images/user/avatar-1.jpg';">
          </div>
          <div class="flex-grow-1">
            <h5 class="mb-1 fw-bold">
              <?= __('dashboard.welcome') ?>, <?= $_name ?>!
            </h5>
            <p class="text-muted mb-0 small">
              <?= __('dashboard.session_as') ?>
              <strong><?= $_email ?></strong>
              <?php if (!empty($freshUser['role_name'])): ?>
                &mdash; <span class="badge bg-primary text-white"><?= htmlspecialchars($freshUser['role_name'], ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </p>
          </div>
          <div class="dashboard-updated d-none d-lg-flex align-items-center gap-2">
            <i class="ph-duotone ph-clock-clockwise"></i>
            <div>
              <span><?= __('dashboard.last_updated') ?></span>
              <strong><?= htmlspecialchars($lastUpdated, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
          </div>
          <?php if ($showAdminDash && $total > 0): ?>
          <div class="d-none d-md-flex gap-4 text-center">
            <div>
              <div class="fw-bold h5 mb-0 text-primary"><?= $total ?></div>
              <div class="text-muted" style="font-size:.75rem;"><?= __('dashboard.total_users') ?></div>
            </div>
            <div>
              <div class="fw-bold h5 mb-0 text-success"><?= $active ?></div>
              <div class="text-muted" style="font-size:.75rem;"><?= __('dashboard.active_users') ?></div>
            </div>
            <div>
              <div class="fw-bold h5 mb-0 text-info"><?= $withMfa ?></div>
              <div class="text-muted" style="font-size:.75rem;"><?= __('dashboard.users_with_mfa') ?></div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- [ Quick action cards ] -->
<div class="dashboard-section-heading">
  <div>
    <span><?= __('dashboard.quick_actions') ?></span>
    <h6><?= __('dashboard.quick_actions_desc') ?></h6>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card prod-p-card border-0 shadow-sm dashboard-action-card">
      <div class="card-body">
        <div class="row align-items-center m-b-0">
          <div class="col">
            <p class="mb-1 text-muted small"><?= __('dashboard.my_profile') ?></p>
            <h6 class="mb-1 fw-semibold"><?= __('dashboard.my_profile_sub') ?></h6>
            <p class="text-muted small mt-1 mb-2"><?= __('dashboard.my_profile_desc') ?></p>
            <a href="<?= BASE_URL ?>/profile" class="btn btn-sm btn-outline-primary">
              <i class="ph-duotone ph-user-circle me-1"></i><?= __('dashboard.my_profile_btn') ?>
            </a>
          </div>
          <div class="col-auto">
            <i class="ph-duotone ph-user-circle text-primary" style="font-size:2.5rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card prod-p-card border-0 shadow-sm dashboard-action-card">
      <div class="card-body">
        <div class="row align-items-center m-b-0">
          <div class="col">
            <p class="mb-1 text-muted small"><?= __('dashboard.my_account') ?></p>
            <h6 class="mb-1 fw-semibold"><?= __('dashboard.my_account_sub') ?></h6>
            <p class="text-muted small mt-1 mb-2"><?= __('dashboard.my_account_desc') ?></p>
            <a href="<?= BASE_URL ?>/account" class="btn btn-sm btn-outline-warning">
              <i class="ph-duotone ph-gear me-1"></i><?= __('dashboard.my_account_btn') ?>
            </a>
          </div>
          <div class="col-auto">
            <i class="ph-duotone ph-gear text-warning" style="font-size:2.5rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card prod-p-card border-0 shadow-sm dashboard-action-card">
      <div class="card-body">
        <div class="row align-items-center m-b-0">
          <div class="col">
            <p class="mb-1 text-muted small"><?= __('dashboard.security') ?></p>
            <h6 class="mb-1 fw-semibold"><?= __('dashboard.active_session') ?></h6>
            <p class="text-muted small mt-1 mb-2">
              <?php if ($sessionEnabled): ?>
                <?= $sessionMins ?> <?= __('dashboard.min_lock') ?>
              <?php else: ?>
                <?= __('dashboard.lock_disabled') ?>
              <?php endif; ?>
            </p>
            <form action="<?= BASE_URL ?>/logout" method="POST" class="d-inline">
              <?= \Core\CSRF::field() ?>
              <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="ph-duotone ph-sign-out me-1"></i><?= __('dashboard.logout_btn') ?>
              </button>
            </form>
          </div>
          <div class="col-auto">
            <i class="ph-duotone ph-shield-check text-success" style="font-size:2.5rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($showAdminDash): ?>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- ADMIN DASHBOARD                                                            -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->

<!-- [ KPI Row 1 ] -->
<div class="dashboard-section-heading">
  <div>
    <span><?= __('dashboard.summary') ?></span>
    <h6><?= __('dashboard.summary_desc') ?></h6>
  </div>
</div>
<div class="row g-3 mb-3">
  <!-- Total usuarios -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="text-muted small mb-1"><?= __('dashboard.total_users') ?></p>
            <h3 class="mb-0 fw-bold"><?= $total ?></h3>
            <span class="dashboard-stat-note"><?= __('dashboard.users_total_hint') ?></span>
          </div>
          <div class="avtar avtar-s bg-light-primary rounded-2 flex-shrink-0">
            <i class="ph-duotone ph-users text-primary" style="font-size:1.3rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Activos -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="text-muted small mb-1"><?= __('dashboard.active_users') ?></p>
            <h3 class="mb-0 fw-bold text-success"><?= $active ?></h3>
            <span class="dashboard-stat-note"><?= __('dashboard.active_ratio', ['percent' => $activePct]) ?></span>
          </div>
          <div class="avtar avtar-s bg-light-success rounded-2 flex-shrink-0">
            <i class="ph-duotone ph-user-check text-success" style="font-size:1.3rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Inactivos -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="text-muted small mb-1"><?= __('dashboard.inactive_users') ?></p>
            <h3 class="mb-0 fw-bold text-danger"><?= $inactive ?></h3>
            <span class="dashboard-stat-note"><?= __('dashboard.inactive_ratio', ['percent' => $inactivePct]) ?></span>
          </div>
          <div class="avtar avtar-s bg-light-danger rounded-2 flex-shrink-0">
            <i class="ph-duotone ph-user-minus text-danger" style="font-size:1.3rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Con MFA -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="text-muted small mb-1"><?= __('dashboard.users_with_mfa') ?></p>
            <h3 class="mb-0 fw-bold text-info"><?= $withMfa ?></h3>
            <span class="dashboard-stat-note"><?= $mfaPct ?>% <?= __('dashboard.protected_users') ?></span>
          </div>
          <div class="avtar avtar-s bg-light-info rounded-2 flex-shrink-0">
            <i class="ph-duotone ph-shield-check text-info" style="font-size:1.3rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- [ KPI Row 2 ] -->
<div class="row g-3 mb-4">
  <!-- Sin MFA -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="text-muted small mb-1"><?= __('dashboard.users_without_mfa') ?></p>
            <h3 class="mb-0 fw-bold text-warning"><?= $withoutMfa ?></h3>
            <span class="dashboard-stat-note"><?= __('dashboard.pending_mfa') ?></span>
          </div>
          <div class="avtar avtar-s bg-light-warning rounded-2 flex-shrink-0">
            <i class="ph-duotone ph-shield-slash text-warning" style="font-size:1.3rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Perfil incompleto -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="text-muted small mb-1"><?= __('dashboard.incomplete_profiles') ?></p>
            <h3 class="mb-0 fw-bold"><?= $incomplete ?></h3>
            <span class="dashboard-stat-note"><?= __('dashboard.profile_completion', ['percent' => $profilePct]) ?></span>
          </div>
          <div class="avtar avtar-s bg-light-secondary rounded-2 flex-shrink-0">
            <i class="ph-duotone ph-warning text-secondary" style="font-size:1.3rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Idiomas activos -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="text-muted small mb-1"><?= __('dashboard.active_languages') ?></p>
            <h3 class="mb-0 fw-bold"><?= $activeLanguages ?></h3>
            <span class="dashboard-stat-note"><?= __('dashboard.languages_ready') ?></span>
          </div>
          <div class="avtar avtar-s bg-light-primary rounded-2 flex-shrink-0">
            <i class="ph-duotone ph-translate text-primary" style="font-size:1.3rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Estado SMTP -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 dashboard-stat-card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div>
            <p class="text-muted small mb-1"><?= __('dashboard.smtp_status') ?></p>
            <h5 class="mb-0 mt-1">
              <span class="badge bg-<?= $smtpBadgeClass ?> px-2 py-1">
                <?= __('dashboard.' . $smtpStatus) ?>
              </span>
            </h5>
            <span class="dashboard-stat-note"><?= __('dashboard.smtp_ready_short') ?></span>
          </div>
          <div class="avtar avtar-s bg-light-<?= $smtpBadgeClass ?> rounded-2 flex-shrink-0">
            <i class="ph-duotone ph-envelope text-<?= $smtpBadgeClass ?>" style="font-size:1.3rem;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- [ Charts Row ] -->
<div class="dashboard-section-heading">
  <div>
    <span><?= __('dashboard.security_overview') ?></span>
    <h6><?= __('dashboard.security_overview_desc') ?></h6>
  </div>
</div>
<div class="row g-3 mb-4">
  <!-- Usuarios por rol -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100 dashboard-chart-card">
      <div class="card-header border-0 pb-0">
        <h6 class="mb-0 fw-semibold"><?= __('dashboard.users_by_role') ?></h6>
      </div>
      <div class="card-body pt-2">
        <div class="dashboard-chart-frame">
          <canvas id="chartRoles"></canvas>
        </div>
        <div id="chartRolesEmpty" class="d-none text-center text-muted py-4 small dashboard-empty-state">
          <i class="ph-duotone ph-chart-pie-slice text-muted mb-2" style="font-size:2rem;display:block;"></i>
          <?= __('dashboard.no_data') ?>
        </div>
      </div>
    </div>
  </div>
  <!-- Estado MFA -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100 dashboard-chart-card">
      <div class="card-header border-0 pb-0">
        <h6 class="mb-0 fw-semibold"><?= __('dashboard.mfa_status_chart') ?></h6>
      </div>
      <div class="card-body pt-2">
        <div class="dashboard-chart-frame">
          <canvas id="chartMfaStatus"></canvas>
        </div>
        <div id="chartMfaStatusEmpty" class="d-none text-center text-muted py-4 small dashboard-empty-state">
          <i class="ph-duotone ph-shield-warning text-muted mb-2" style="font-size:2rem;display:block;"></i>
          <?= __('dashboard.no_data') ?>
        </div>
      </div>
    </div>
  </div>
  <!-- Métodos MFA -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100 dashboard-chart-card">
      <div class="card-header border-0 pb-0">
        <h6 class="mb-0 fw-semibold"><?= __('dashboard.mfa_methods_chart') ?></h6>
      </div>
      <div class="card-body pt-2">
        <div class="dashboard-chart-frame">
          <canvas id="chartMfaMethods"></canvas>
        </div>
        <div id="chartMfaMethodsEmpty" class="d-none text-center text-muted py-4 small dashboard-empty-state dashboard-empty-state-center">
          <i class="ph-duotone ph-shield-slash text-muted mb-2" style="font-size:2rem;display:block;"></i>
          <?= __('dashboard.no_mfa_users') ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- [ Estado usuarios + Actividad reciente ] -->
<div class="dashboard-section-heading">
  <div>
    <span><?= __('dashboard.activity_overview') ?></span>
    <h6><?= __('dashboard.activity_overview_desc') ?></h6>
  </div>
</div>
<div class="row g-3 mb-4">
  <!-- Bar chart: Estado de usuarios -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100 dashboard-chart-card">
      <div class="card-header border-0 pb-0">
        <h6 class="mb-0 fw-semibold"><?= __('dashboard.user_status_chart') ?></h6>
      </div>
      <div class="card-body pt-2">
        <div class="dashboard-chart-frame dashboard-chart-frame-bars">
          <canvas id="chartUserStatus"></canvas>
        </div>
        <div id="chartUserStatusEmpty" class="d-none text-center text-muted py-4 small dashboard-empty-state dashboard-empty-state-center">
          <i class="ph-duotone ph-users-three text-muted mb-2" style="font-size:2rem;display:block;"></i>
          <?= __('dashboard.no_users_status_data') ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Actividad reciente -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm h-100 dashboard-panel-card">
      <div class="card-header border-0 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold"><?= __('dashboard.recent_activity') ?></h6>
        <?php if ($canViewAudit): ?>
        <a href="<?= BASE_URL ?>/audit-logs" class="btn btn-sm btn-outline-primary">
          <i class="ph-duotone ph-arrow-square-out me-1"></i><?= __('dashboard.view_audit') ?>
        </a>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentActivity)): ?>
          <div class="text-center text-muted py-5 small dashboard-empty-state m-3">
            <i class="ph-duotone ph-clock-clockwise mb-2" style="font-size:2.5rem;display:block;"></i>
            <?= __('dashboard.no_recent_activity') ?>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
              <thead>
                <tr class="text-muted" style="font-size:.75rem;">
                  <th class="ps-3 fw-semibold"><?= __('dashboard.audit_date') ?></th>
                  <th class="fw-semibold"><?= __('dashboard.audit_user') ?></th>
                  <th class="fw-semibold"><?= __('dashboard.audit_module_action') ?></th>
                  <th class="pe-3 fw-semibold"><?= __('dashboard.audit_status') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentActivity as $log): ?>
                <tr>
                  <td class="ps-3 text-muted text-nowrap" style="font-size:.78rem;">
                    <?= htmlspecialchars(date('d/m/y H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td style="font-size:.78rem;">
                    <?php
                    $auditUser = trim($log['user_name'] ?? '');
                    if ($auditUser === '' || $auditUser === ' ') $auditUser = $log['user_email'] ?? '—';
                    ?>
                    <?= htmlspecialchars($auditUser, ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td style="font-size:.78rem;">
                    <?php if (!empty($log['module'])): ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1">
                      <?= htmlspecialchars($log['module'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php endif; ?>
                    <?= htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td class="pe-3">
                    <?php $st = $log['status'] ?? 'unknown'; ?>
                    <span class="badge bg-<?= $st === 'success' ? 'success' : ($st === 'warning' ? 'warning' : 'danger') ?> bg-opacity-75" style="font-size:.72rem;">
                      <?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>
                    </span>
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

<!-- [ Seguridad + Estado del sistema + Usuarios y perfiles ] -->
<div class="dashboard-section-heading">
  <div>
    <span><?= __('dashboard.system_status') ?></span>
    <h6><?= __('dashboard.system_status_desc') ?></h6>
  </div>
</div>
<div class="row g-3 mb-4">

  <!-- Seguridad del sistema -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100 dashboard-panel-card">
      <div class="card-header border-0">
        <h6 class="mb-0 fw-semibold">
          <i class="ph-duotone ph-shield-check me-2 text-success"></i><?= __('dashboard.system_security') ?>
        </h6>
      </div>
      <div class="card-body pt-0">
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.smtp_label') ?></span>
          <span class="badge bg-<?= $smtpBadgeClass ?>"><?= __('dashboard.' . $smtpStatus) ?></span>
        </div>
        <?php if ($canViewMfa): ?>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.mfa_email_label') ?></span>
          <span class="badge bg-<?= !empty($mfaSettings['email_enabled']) ? 'success' : 'secondary' ?>">
            <?= !empty($mfaSettings['email_enabled']) ? __('dashboard.enabled_badge') : __('dashboard.disabled_badge') ?>
          </span>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.mfa_auth_label') ?></span>
          <span class="badge bg-<?= !empty($mfaSettings['authenticator_enabled']) ? 'success' : 'secondary' ?>">
            <?= !empty($mfaSettings['authenticator_enabled']) ? __('dashboard.enabled_badge') : __('dashboard.disabled_badge') ?>
          </span>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.users_with_mfa_label') ?></span>
          <strong class="small"><?= $withMfa ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.users_without_mfa_label') ?></span>
          <strong class="small"><?= $withoutMfa ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 <?= !empty($smtpData['last_tested_at']) ? 'border-bottom' : '' ?>">
          <span class="small text-muted"><?= __('dashboard.session_lock_label') ?></span>
          <?php if ($sessionEnabled): ?>
            <span class="badge bg-info bg-opacity-75"><?= $sessionMins ?> min</span>
          <?php else: ?>
            <span class="badge bg-secondary"><?= __('dashboard.disabled_badge') ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($smtpData['last_tested_at'])): ?>
        <div class="pt-2">
          <p class="small text-muted mb-0">
            <i class="ph-duotone ph-clock me-1"></i>
            <?= __('dashboard.last_smtp_test') ?>:
            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($smtpData['last_tested_at'])), ENT_QUOTES, 'UTF-8') ?>
          </p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Estado del sistema -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100 dashboard-panel-card">
      <div class="card-header border-0">
        <h6 class="mb-0 fw-semibold">
          <i class="ph-duotone ph-info me-2 text-info"></i><?= __('dashboard.system_status') ?>
        </h6>
      </div>
      <div class="card-body pt-0">
        <?php if (!empty($systemSettings)): ?>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.system_version') ?></span>
          <span class="badge bg-primary text-white fw-semibold">
            <?= htmlspecialchars($systemSettings['system_version'] ?? __('dashboard.na'), ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.release_year') ?></span>
          <strong class="small"><?= htmlspecialchars($systemSettings['release_year'] ?? __('dashboard.na'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.project_leader') ?></span>
          <strong class="small"><?= htmlspecialchars($systemSettings['project_leader'] ?? __('dashboard.na'), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <?php else: ?>
        <p class="text-muted small py-2"><?= __('dashboard.no_system_info') ?></p>
        <?php endif; ?>

        <?php if ($defaultLanguage): ?>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.base_language') ?></span>
          <strong class="small">
            <?= htmlspecialchars($defaultLanguage['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            <span class="text-muted">(<?= htmlspecialchars($defaultLanguage['code'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</span>
          </strong>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.active_languages') ?></span>
          <strong class="small"><?= $activeLanguages ?></strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.active_modules') ?></span>
          <strong class="small"><?= $activeModules ?></strong>
        </div>
        <div class="d-flex justify-content-between py-2">
          <span class="small text-muted"><?= __('dashboard.registered_permissions') ?></span>
          <strong class="small"><?= $permissionsCount ?></strong>
        </div>
      </div>
    </div>
  </div>

  <!-- Usuarios y perfiles -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100 dashboard-panel-card">
      <div class="card-header border-0">
        <h6 class="mb-0 fw-semibold">
          <i class="ph-duotone ph-users me-2 text-primary"></i><?= __('dashboard.users_profiles') ?>
        </h6>
      </div>
      <div class="card-body pt-0">
        <?php if ($lastUser): ?>
        <p class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;"><?= __('dashboard.last_registered_user') ?></p>
        <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
          <div class="avtar avtar-s bg-light-primary flex-shrink-0">
            <i class="ph-duotone ph-user text-primary"></i>
          </div>
          <div class="flex-grow-1 overflow-hidden">
            <p class="mb-0 fw-semibold small text-truncate"><?= htmlspecialchars($lastUser['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mb-0 text-muted text-truncate" style="font-size:.78rem;"><?= htmlspecialchars($lastUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($lastUser['created_at'])): ?>
            <p class="mb-0 text-muted" style="font-size:.72rem;"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($lastUser['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
          </div>
          <?php if (!empty($lastUser['role_name'])): ?>
          <span class="badge bg-primary text-white flex-shrink-0" style="font-size:.7rem;"><?= htmlspecialchars($lastUser['role_name'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="row g-2">
          <div class="col-6">
            <div class="bg-body-secondary rounded-2 p-2 text-center">
              <div class="fw-bold h5 mb-0 text-success"><?= $withImage ?></div>
              <div class="text-muted" style="font-size:.72rem;"><?= __('dashboard.with_profile_image') ?></div>
            </div>
          </div>
          <div class="col-6">
            <div class="bg-body-secondary rounded-2 p-2 text-center">
              <div class="fw-bold h5 mb-0 text-warning"><?= $withoutImg ?></div>
              <div class="text-muted" style="font-size:.72rem;"><?= __('dashboard.without_profile_image') ?></div>
            </div>
          </div>
          <div class="col-6">
            <div class="bg-body-secondary rounded-2 p-2 text-center">
              <div class="fw-bold h5 mb-0 text-danger"><?= $incomplete ?></div>
              <div class="text-muted" style="font-size:.72rem;"><?= __('dashboard.incomplete_profile_short') ?></div>
            </div>
          </div>
          <div class="col-6">
            <div class="bg-body-secondary rounded-2 p-2 text-center">
              <div class="fw-bold h5 mb-0 text-primary"><?= $total ?></div>
              <div class="text-muted" style="font-size:.72rem;"><?= __('dashboard.total_label') ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php else: ?>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- PERSONAL DASHBOARD (non-admin)                                             -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->

<div class="row g-3 mb-4">
  <!-- Mi seguridad personal -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100 dashboard-panel-card">
      <div class="card-header border-0">
        <h6 class="mb-0 fw-semibold">
          <i class="ph-duotone ph-shield-check me-2 text-success"></i><?= __('dashboard.personal_security') ?>
        </h6>
      </div>
      <div class="card-body pt-0">
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.two_fa_status') ?></span>
          <?php if (!empty($freshUser['two_factor_enabled'])): ?>
            <span class="badge bg-success">
              <?= __('dashboard.enabled_badge') ?>
              &mdash; <?= htmlspecialchars($freshUser['two_factor_method'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </span>
          <?php else: ?>
            <span class="badge bg-warning"><?= __('dashboard.no_mfa_configured') ?></span>
          <?php endif; ?>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.theme_pref') ?></span>
          <span class="small text-capitalize">
            <?= htmlspecialchars($freshUser['theme_preference'] ?? 'light', ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2">
          <span class="small text-muted"><?= __('dashboard.language_pref') ?></span>
          <span class="small">
            <?= htmlspecialchars(strtoupper($freshUser['lang_code'] ?? 'ES'), ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
        <div class="mt-3">
          <a href="<?= BASE_URL ?>/profile/two-factor" class="btn btn-sm btn-outline-primary w-100">
            <i class="ph-duotone ph-shield-check me-1"></i><?= __('dashboard.configure_2fa') ?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Información de la cuenta -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100 dashboard-panel-card">
      <div class="card-header border-0">
        <h6 class="mb-0 fw-semibold">
          <i class="ph-duotone ph-info me-2 text-info"></i><?= __('dashboard.general_info') ?>
        </h6>
      </div>
      <div class="card-body pt-0">
        <p class="text-muted small pb-2 border-bottom"><?= __('dashboard.welcome_message') ?></p>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="small text-muted"><?= __('dashboard.your_role') ?></span>
          <span class="badge bg-primary text-white fw-semibold">
            <?= htmlspecialchars($freshUser['role_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2">
          <span class="small text-muted"><?= __('dashboard.account_status') ?></span>
          <span class="badge bg-success bg-opacity-75">
            <?= htmlspecialchars($freshUser['status_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

</div>

<?php
// Chart.js — only injected for the admin dashboard
if ($showAdminDash):
    $jsRoleLabels      = json_encode($chartRoleLabels, JSON_UNESCAPED_UNICODE);
    $jsRoleCounts      = json_encode($chartRoleCounts);
    $jsMethodLabels    = json_encode($chartMfaMethodLabels, JSON_UNESCAPED_UNICODE);
    $jsMethodCounts    = json_encode($chartMfaMethodCounts);
    $jsWithMfa         = $withMfa;
    $jsWithoutMfa      = $withoutMfa;
    $jsActive          = $statusActive;
    $jsInactive        = $statusInactive;
    $jsBlocked         = $blocked;
    $jsMfaWithLabel    = json_encode(__('dashboard.with_mfa_label'),    JSON_UNESCAPED_UNICODE);
    $jsMfaWithoutLabel = json_encode(__('dashboard.without_mfa_label'), JSON_UNESCAPED_UNICODE);
    $jsActiveLabel     = json_encode(__('dashboard.users_active'),      JSON_UNESCAPED_UNICODE);
    $jsInactiveLabel   = json_encode(__('dashboard.users_inactive'),    JSON_UNESCAPED_UNICODE);
    $jsBlockedLabel    = json_encode(__('dashboard.users_blocked'),     JSON_UNESCAPED_UNICODE);
    $jsUserSingular    = json_encode(__('dashboard.user_singular'),     JSON_UNESCAPED_UNICODE);
    $jsUserPlural      = json_encode(__('dashboard.user_plural'),       JSON_UNESCAPED_UNICODE);
    $jsNoData          = json_encode(__('dashboard.no_data'),           JSON_UNESCAPED_UNICODE);
    $jsNoMfa           = json_encode(__('dashboard.no_mfa_users'),      JSON_UNESCAPED_UNICODE);

    $extraScript = <<<JS
<script src="<?= BASE_URL ?>/assets/js/plugins/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var isDark = document.body.getAttribute('data-pc-theme') === 'dark'
            || document.body.getAttribute('data-bs-theme') === 'dark';
  var textColor = isDark ? 'rgba(255,255,255,0.65)' : '#5b6b79';
  var gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.07)';
  var bgCard    = isDark ? '#1d2630' : '#ffffff';
  Chart.defaults.color       = textColor;
  Chart.defaults.borderColor = gridColor;
  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.font.size   = 12;

  var COLORS = ['#4680ff','#2ed8b6','#ffb64d','#ff5370','#a389f4','#6fd36e','#3ec9d6','#f4a261'];

  function doughnut(id, labels, data) {
    var el = document.getElementById(id);
    if (!el) return;
    if (!data || data.length === 0 || data.every(function(v){ return v === 0; })) {
      el.style.display = 'none';
      if (el.parentElement && el.parentElement.classList.contains('dashboard-chart-frame')) {
        el.parentElement.style.display = 'none';
      }
      var empty = document.getElementById(id + 'Empty');
      if (empty) empty.classList.remove('d-none');
      return;
    }
    new Chart(el, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: COLORS.slice(0, data.length),
          borderWidth: 3,
          borderColor: bgCard,
          hoverBorderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { padding: 10, usePointStyle: true, pointStyleWidth: 10 }
          },
          tooltip: { padding: 8 }
        }
      }
    });
  }

  function bar(id, labels, data, bgColors) {
    var el = document.getElementById(id);
    if (!el) return;
    if (!data || data.length === 0 || data.every(function(v){ return v === 0; })) {
      el.style.display = 'none';
      if (el.parentElement && el.parentElement.classList.contains('dashboard-chart-frame')) {
        el.parentElement.style.display = 'none';
      }
      var empty = document.getElementById(id + 'Empty');
      if (empty) empty.classList.remove('d-none');
      return;
    }
    new Chart(el, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: bgColors || COLORS.slice(0, data.length),
          borderRadius: 6,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 4, right: 4, bottom: 0, left: 0 } },
        plugins: {
          legend: { display: false },
          tooltip: {
            padding: 10,
            callbacks: {
              label: function (context) {
                var value = Number(context.parsed.y || 0);
                return context.label + ': ' + value + ' ' + (value === 1 ? {$jsUserSingular} : {$jsUserPlural});
              }
            }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { padding: 8 } },
          y: {
            beginAtZero: true,
            grid: { color: gridColor },
            ticks: { stepSize: 1, precision: 0, padding: 6 }
          }
        }
      }
    });
  }

  // Chart 1: Usuarios por rol
  doughnut('chartRoles', {$jsRoleLabels}, {$jsRoleCounts});

  // Chart 2: Estado MFA
  doughnut('chartMfaStatus',
    [{$jsMfaWithLabel}, {$jsMfaWithoutLabel}],
    [{$jsWithMfa}, {$jsWithoutMfa}]
  );

  // Chart 3: Métodos MFA
  doughnut('chartMfaMethods', {$jsMethodLabels}, {$jsMethodCounts});

  // Chart 4: Estado de usuarios (bar)
  bar('chartUserStatus',
    [{$jsActiveLabel}, {$jsInactiveLabel}, {$jsBlockedLabel}],
    [{$jsActive}, {$jsInactive}, {$jsBlocked}],
    ['#2ed8b6', '#ff5370', '#ffb64d']
  );
});
</script>
JS;
endif;

require dirname(__DIR__) . '/layouts/footer.php';
?>
