<?php
use Core\CSRF;

$pageTitle  = __('system.title');
$activeMenu = 'system_information';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('system.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('system.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Info del sistema -->
  <div class="col-lg-5 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="ph-duotone ph-info me-2 text-primary"></i><?= __('system.info_card') ?></h5>
        <?php if (can('system_information.edit')): ?>
        <a href="<?= BASE_URL ?>/system-information/edit" class="btn btn-sm btn-outline-primary">
          <i class="ph-duotone ph-pencil me-1"></i><?= __('buttons.edit') ?>
        </a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($setting): ?>
        <div class="row py-2">
          <div class="col-6 text-muted"><?= __('system.release_year') ?></div>
          <div class="col-6 fw-semibold"><?= htmlspecialchars($setting['release_year'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <hr class="my-2">
        <div class="row py-2">
          <div class="col-6 text-muted"><?= __('system.project_leader') ?></div>
          <div class="col-6 fw-semibold"><?= htmlspecialchars($setting['project_leader'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <hr class="my-2">
        <div class="row py-2">
          <div class="col-6 text-muted"><?= __('system.version') ?></div>
          <div class="col-6 fw-semibold"><span class="badge bg-primary"><?= htmlspecialchars($setting['system_version'], ENT_QUOTES, 'UTF-8') ?></span></div>
        </div>
        <?php else: ?>
        <p class="text-muted"><?= __('system.no_info') ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Manuales de usuario -->
  <div class="col-lg-7 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="ph-duotone ph-files me-2 text-primary"></i><?= __('manuals.title') ?></h5>
        <?php if (can('manuals.upload')): ?>
        <a href="<?= BASE_URL ?>/manuals/create" class="btn btn-sm btn-primary">
          <i class="ph-duotone ph-upload me-1"></i><?= __('manuals.upload') ?>
        </a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (empty($manuals)): ?>
          <p class="text-muted"><?= __('manuals.no_manuals') ?></p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover datatable">
            <thead>
              <tr>
                <th><?= __('manuals.name_field') ?></th>
                <th><?= __('common.type') ?></th>
                <th><?= __('common.size') ?></th>
                <?php if (can('manuals.activate') || can('manuals.deactivate')): ?><th><?= __('common.status') ?></th><?php endif; ?>
                <th><?= __('common.actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($manuals as $manual): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($manual['title'], ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if ($manual['description']): ?>
                  <small class="text-muted"><?= htmlspecialchars($manual['description'], ENT_QUOTES, 'UTF-8') ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                  $ext = strtoupper(pathinfo($manual['file_name'], PATHINFO_EXTENSION));
                  $badgeClass = match($ext) { 'PDF' => 'danger', 'DOC', 'DOCX' => 'primary', default => 'secondary' };
                  ?>
                  <span class="badge bg-<?= $badgeClass ?>"><?= $ext ?></span>
                </td>
                <td><?= number_format($manual['file_size'] / 1024, 1) ?> KB</td>
                <?php if (can('manuals.activate') || can('manuals.deactivate')): ?>
                <td>
                  <span class="badge bg-<?= ($manual['status_slug'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                    <?= ($manual['status_slug'] ?? '') === 'active' ? __('common.active') : __('common.inactive') ?>
                  </span>
                </td>
                <?php endif; ?>
                <td>
                  <a href="<?= BASE_URL ?>/manuals/download/<?= $manual['id'] ?>" class="btn btn-sm btn-outline-success me-1">
                    <i class="ph-duotone ph-download-simple"></i>
                  </a>
                  <?php
                  $manIsActive = ($manual['status_slug'] ?? '') === 'active';
                  if (($manIsActive && can('manuals.deactivate')) || (!$manIsActive && can('manuals.activate'))):
                  ?>
                  <form action="<?= BASE_URL ?>/manuals/toggle/<?= $manual['id'] ?>" method="POST" class="d-inline">
                    <?= CSRF::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-<?= $manIsActive ? 'warning' : 'secondary' ?>">
                      <i class="ph-duotone ph-<?= $manIsActive ? 'eye-slash' : 'eye' ?>"></i>
                    </button>
                  </form>
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
  </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
$(document).ready(function () {
  $('.datatable').DataTable({
    pageLength: 10,
    order: [[0, 'asc']]
  });
});
</script>
JS;
?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
