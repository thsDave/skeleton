<?php
$pageTitle  = __('roles_permissions.title');
$activeMenu = 'roles_permissions';
require dirname(__DIR__) . '/layouts/main.php';
?>

<!-- [ breadcrumb ] start -->
<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('roles_permissions.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('roles_permissions.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>
<!-- [ breadcrumb ] end -->

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
          <i class="ph-duotone ph-shield-check me-2"></i>
          <?= __('roles_permissions.description') ?>
        </h5>
      </div>
      <div class="card-body">
        <p class="text-muted mb-4"><?= __('roles_permissions.subtitle') ?></p>
        <div class="row g-3">
          <?php foreach ($roles as $role): ?>
          <div class="col-md-4">
            <div class="card border h-100">
              <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center mb-3">
                  <div class="avtar avtar-s bg-light-primary me-2">
                    <i class="ph-duotone ph-users-three text-primary"></i>
                  </div>
                  <div>
                    <h6 class="mb-0"><?= htmlspecialchars($role['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($role['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                  </div>
                </div>
                <div class="mt-auto">
                  <?php if (can('roles_permissions.edit')): ?>
                  <a href="<?= BASE_URL ?>/roles-permissions/edit/<?= (int)$role['id'] ?>"
                     class="btn btn-outline-primary btn-sm w-100">
                    <i class="ph-duotone ph-pencil me-1"></i>
                    <?= __('roles_permissions.edit_role') ?>
                  </a>
                  <?php else: ?>
                  <a href="<?= BASE_URL ?>/roles-permissions/edit/<?= (int)$role['id'] ?>"
                     class="btn btn-outline-secondary btn-sm w-100">
                    <i class="ph-duotone ph-eye me-1"></i>
                    <?= __('roles_permissions.view_role') ?>
                  </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
