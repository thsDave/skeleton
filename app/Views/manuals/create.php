<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = __('manuals.upload');
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
          <h5 class="mb-0"><?= __('manuals.upload') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/system-information"><?= __('system.title') ?></a></li>
            <li class="breadcrumb-item active"><?= __('manuals.upload') ?></li>
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
        <h5 class="mb-0"><i class="ph-duotone ph-upload-simple me-2 text-primary"></i><?= __('manuals.upload') ?></h5>
      </div>
      <div class="card-body">
        <form action="<?= BASE_URL ?>/manuals/store" method="POST" enctype="multipart/form-data" novalidate>
          <?= CSRF::field() ?>

          <div class="mb-3">
            <label for="title" class="form-label fw-semibold">
              <?= __('manuals.title_field') ?> <span class="text-danger">*</span>
            </label>
            <input type="text" name="title" id="title"
                   class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($old['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="150" required />
            <?php if (isset($errors['title'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['title'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label fw-semibold">
              <?= __('manuals.description_field') ?>
            </label>
            <textarea name="description" id="description"
                      class="form-control"
                      rows="3"
                      maxlength="500"><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="mb-4">
            <label for="manual_file" class="form-label fw-semibold">
              <?= __('manuals.file_field') ?> <span class="text-danger">*</span>
            </label>
            <input type="file" name="manual_file" id="manual_file"
                   class="form-control <?= isset($errors['manual_file']) ? 'is-invalid' : '' ?>"
                   accept=".pdf,.doc,.docx" />
            <div class="form-text"><?= __('manuals.file_hint') ?></div>
            <?php if (isset($errors['manual_file'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['manual_file'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-upload-simple me-1"></i> <?= __('manuals.upload') ?>
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
