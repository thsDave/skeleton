<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = __('system.edit');
$activeMenu = 'system_information';
$errors     = Session::getFlash('errors', []);
$old        = Session::getFlash('old', []);

require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('system.edit') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/system-information"><?= __('system.title') ?></a></li>
            <li class="breadcrumb-item active"><?= __('buttons.edit') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ph-duotone ph-pencil me-2 text-primary"></i><?= __('system.edit') ?></h5>
      </div>
      <div class="card-body">
        <form action="<?= BASE_URL ?>/system-information/update" method="POST" novalidate>
          <?= CSRF::field() ?>

          <div class="mb-3">
            <label for="release_year" class="form-label fw-semibold">
              <?= __('system.release_year') ?> <span class="text-danger">*</span>
            </label>
            <input type="number" name="release_year" id="release_year"
                   class="form-control <?= isset($errors['release_year']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($old['year'] ?? $setting['release_year'] ?? date('Y'), ENT_QUOTES, 'UTF-8') ?>"
                   min="2000" max="2100" required />
            <?php if (isset($errors['release_year'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['release_year'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="project_leader" class="form-label fw-semibold">
              <?= __('system.project_leader') ?> <span class="text-danger">*</span>
            </label>
            <input type="text" name="project_leader" id="project_leader"
                   class="form-control <?= isset($errors['project_leader']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($old['leader'] ?? $setting['project_leader'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="150" required />
            <?php if (isset($errors['project_leader'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['project_leader'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-4">
            <label for="system_version" class="form-label fw-semibold">
              <?= __('system.version') ?> <span class="text-danger">*</span>
            </label>
            <input type="text" name="system_version" id="system_version"
                   class="form-control <?= isset($errors['system_version']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($old['version'] ?? $setting['system_version'] ?? '1.0.0', ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="20" required placeholder="1.0.0" />
            <div class="form-text"><?= __('system.version_hint') ?></div>
            <?php if (isset($errors['system_version'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['system_version'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-floppy-disk me-1"></i> <?= __('buttons.save') ?>
            </button>
            <a href="<?= BASE_URL ?>/system-information" class="btn btn-outline-secondary">
              <i class="ph-duotone ph-x me-1"></i> <?= __('buttons.cancel') ?>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
