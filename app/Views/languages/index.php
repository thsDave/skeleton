<?php
use Core\CSRF;

$pageTitle  = __('languages.title');
$activeMenu = 'languages';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('languages.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('languages.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="ph-duotone ph-translate me-2 text-primary"></i><?= __('languages.title') ?></h5>
        <a href="<?= BASE_URL ?>/languages/create" class="btn btn-primary btn-sm">
          <i class="ph-duotone ph-plus me-1"></i> <?= __('languages.new') ?>
        </a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover datatable">
            <thead>
              <tr>
                <th>#</th>
                <th><?= __('languages.name_field') ?></th>
                <th><?= __('languages.native_name_field') ?></th>
                <th><?= __('languages.code_field') ?></th>
                <th><?= __('common.status') ?></th>
                <th><?= __('common.default') ?></th>
                <th><?= __('common.actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($languages as $lang): ?>
              <tr>
                <td><?= $lang['id'] ?></td>
                <td><?= htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($lang['native_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><code><?= htmlspecialchars($lang['code'], ENT_QUOTES, 'UTF-8') ?></code></td>
                <td>
                  <span class="badge bg-<?= ($lang['status_slug'] ?? '') === 'active' ? 'success' : 'danger' ?>">
                    <?= ($lang['status_slug'] ?? '') === 'active' ? __('common.active') : __('common.inactive') ?>
                  </span>
                </td>
                <td>
                  <?php if ($lang['is_default']): ?>
                    <span class="badge bg-primary"><?= __('common.yes') ?></span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>/languages/edit/<?= $lang['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                    <i class="ph-duotone ph-pencil"></i>
                  </a>
                  <?php if (!$lang['is_default']): ?>
                  <form action="<?= BASE_URL ?>/languages/toggle/<?= $lang['id'] ?>" method="POST" class="d-inline">
                    <?= CSRF::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-<?= ($lang['status_slug'] ?? '') === 'active' ? 'warning' : 'success' ?>"
                            title="<?= ($lang['status_slug'] ?? '') === 'active' ? __('buttons.deactivate') : __('buttons.activate') ?>">
                      <i class="ph-duotone ph-<?= ($lang['status_slug'] ?? '') === 'active' ? 'pause' : 'play' ?>"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
$(document).ready(function () {
  $('.datatable').DataTable({
    language: { url: '' },
    pageLength: 10,
    order: [[0, 'asc']]
  });
});
</script>
JS;
?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
