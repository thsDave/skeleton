<?php
use Core\Session;
use Core\CSRF;

$pageTitle  = __('languages.new');
$activeMenu = 'languages';
$errors     = Session::getFlash('errors', []);
$old        = Session::getFlash('old', []);

require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('languages.new') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/languages"><?= __('languages.title') ?></a></li>
            <li class="breadcrumb-item active"><?= __('languages.new') ?></li>
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
        <h5 class="mb-0"><i class="ph-duotone ph-plus-circle me-2 text-primary"></i><?= __('languages.new') ?></h5>
      </div>
      <div class="card-body">
        <form action="<?= BASE_URL ?>/languages/store" method="POST" novalidate>
          <?= CSRF::field() ?>

          <div class="mb-3">
            <label for="name" class="form-label fw-semibold">
              <?= __('languages.name_field') ?> <span class="text-danger">*</span>
            </label>
            <input type="text" name="name" id="name"
                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="100" required
                   placeholder="<?= __('languages.name_placeholder') ?>" />
            <?php if (isset($errors['name'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="native_name" class="form-label fw-semibold">
              <?= __('languages.native_name_field') ?>
            </label>
            <input type="text" name="native_name" id="native_name"
                   class="form-control"
                   value="<?= htmlspecialchars($old['nativeName'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="100"
                   placeholder="<?= __('languages.native_name_placeholder') ?>" />
          </div>

          <div class="mb-3">
            <label for="code" class="form-label fw-semibold">
              <?= __('languages.code_field') ?> <span class="text-danger">*</span>
            </label>
            <input type="text" name="code" id="code"
                   class="form-control <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                   value="<?= htmlspecialchars($old['code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="10" required
                   placeholder="es, en, fr..." />
            <div class="form-text"><?= __('languages.code_hint') ?></div>
            <?php if (isset($errors['code'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['code'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-4">
            <label for="status_id" class="form-label fw-semibold">
              <?= __('common.status') ?> <span class="text-danger">*</span>
            </label>
            <select name="status_id" id="status_id"
                    class="form-select select2 <?= isset($errors['status_id']) ? 'is-invalid' : '' ?>">
              <?php foreach ($statuses as $status): ?>
              <option value="<?= $status['id'] ?>"
                      <?= ($old['statusId'] ?? 1) == $status['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($status['name'], ENT_QUOTES, 'UTF-8') ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['status_id'])): ?>
              <div class="invalid-feedback"><?= htmlspecialchars($errors['status_id'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-floppy-disk me-1"></i> <?= __('buttons.save') ?>
            </button>
            <a href="<?= BASE_URL ?>/languages" class="btn btn-outline-secondary">
              <i class="ph-duotone ph-x me-1"></i> <?= __('buttons.cancel') ?>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
