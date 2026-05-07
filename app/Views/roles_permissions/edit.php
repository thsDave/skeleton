<?php
$pageTitle  = __('roles_permissions.edit_role') . ': ' . htmlspecialchars($role['name'] ?? '', ENT_QUOTES, 'UTF-8');
$activeMenu = 'roles_permissions';
require dirname(__DIR__) . '/layouts/main.php';

// Mapa de slug de módulo → clave de traducción de menú existente
$moduleMenuKeys = [
    'dashboard'          => 'menu.dashboard',
    'profile'            => 'menu.my_profile',
    'account'            => 'menu.my_account_link',
    'users'              => 'menu.users',
    'languages'          => 'menu.languages',
    'system_information' => 'menu.system_information',
    'manuals'            => 'menu.manuals',
    'security_sessions'  => 'menu.security_sessions_full',
    'roles_permissions'  => 'menu.roles_permissions',
];

$canEdit = can('roles_permissions.edit');
?>

<!-- [ breadcrumb ] start -->
<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('roles_permissions.permissions') ?>: <?= htmlspecialchars($role['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/roles-permissions"><?= __('roles_permissions.title') ?></a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($role['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>
<!-- [ breadcrumb ] end -->

<?php if (!$canEdit): ?>
<div class="alert alert-info mb-4">
  <i class="ph-duotone ph-eye me-2"></i>
  <?= __('roles_permissions.view_only_notice') ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/roles-permissions/update/<?= (int)$role['id'] ?>">
  <?= \Core\CSRF::field() ?>

  <?php foreach ($modules as $moduleId => $moduleData): ?>
  <?php
    $moduleSlug = $moduleData['module_slug'] ?? '';
    $moduleLabel = __($moduleMenuKeys[$moduleSlug] ?? '') ?: htmlspecialchars($moduleData['module_name'], ENT_QUOTES, 'UTF-8');
  ?>
  <div class="card mb-3">
    <div class="card-header py-2">
      <div class="d-flex align-items-center">
        <div class="form-check mb-0 me-2">
          <input class="form-check-input module-check-all"
                 type="checkbox"
                 data-module="<?= (int)$moduleId ?>"
                 title="<?= __('roles_permissions.toggle_all') ?>"
                 <?= $canEdit ? '' : 'disabled' ?>>
        </div>
        <h6 class="mb-0 fw-semibold"><?= $moduleLabel ?></h6>
      </div>
    </div>
    <div class="card-body py-2">
      <div class="row g-2">
        <?php foreach ($moduleData['permissions'] as $perm): ?>
        <div class="col-md-4 col-sm-6">
          <div class="form-check">
            <input class="form-check-input perm-check"
                   type="checkbox"
                   name="permissions[]"
                   value="<?= (int)$perm['id'] ?>"
                   data-module="<?= (int)$moduleId ?>"
                   id="perm_<?= (int)$perm['id'] ?>"
                   <?= in_array((int)$perm['id'], (array)$assigned, true) ? 'checked' : '' ?>
                   <?= $canEdit ? '' : 'disabled' ?>>
            <label class="form-check-label" for="perm_<?= (int)$perm['id'] ?>">
              <?= htmlspecialchars(__('permissions.' . $perm['slug']), ENT_QUOTES, 'UTF-8') ?>
              <br>
              <small class="text-muted"><?= htmlspecialchars($perm['slug'], ENT_QUOTES, 'UTF-8') ?></small>
            </label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if ($canEdit): ?>
  <div class="d-flex gap-2 mt-2 mb-4">
    <button type="submit" class="btn btn-primary">
      <i class="ph-duotone ph-floppy-disk me-1"></i>
      <?= __('roles_permissions.save') ?>
    </button>
    <a href="<?= BASE_URL ?>/roles-permissions" class="btn btn-outline-secondary">
      <i class="ph-duotone ph-arrow-left me-1"></i>
      <?= __('buttons.back') ?>
    </a>
  </div>
  <?php else: ?>
  <div class="mb-4">
    <a href="<?= BASE_URL ?>/roles-permissions" class="btn btn-outline-secondary">
      <i class="ph-duotone ph-arrow-left me-1"></i>
      <?= __('buttons.back') ?>
    </a>
  </div>
  <?php endif; ?>
</form>

<?php
$extraScript = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {

  // "Select all" checkbox per module
  document.querySelectorAll('.module-check-all').forEach(function (masterChk) {
    var moduleId = masterChk.dataset.module;
    var children = document.querySelectorAll('.perm-check[data-module="' + moduleId + '"]');

    // Initialise master state
    var allChecked = Array.from(children).every(function (c) { return c.checked; });
    masterChk.checked       = allChecked;
    masterChk.indeterminate = !allChecked && Array.from(children).some(function (c) { return c.checked; });

    masterChk.addEventListener('change', function () {
      children.forEach(function (c) { c.checked = masterChk.checked; });
    });

    children.forEach(function (c) {
      c.addEventListener('change', function () {
        var all  = Array.from(children).every(function (x) { return x.checked; });
        var none = Array.from(children).every(function (x) { return !x.checked; });
        masterChk.checked       = all;
        masterChk.indeterminate = !all && !none;
      });
    });
  });

});
</script>
JS;
?>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
