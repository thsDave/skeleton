<?php
$pageTitle  = __('errors.403_title');
$activeMenu = '';
require dirname(__DIR__) . '/layouts/main.php';
?>

<!-- [ 403 Content ] -->
<div class="row justify-content-center">
  <div class="col-lg-6 col-md-8">
    <div class="card text-center">
      <div class="card-body py-5">

        <div class="mb-3">
          <i class="ph-duotone ph-lock-key text-danger" style="font-size: 4.5rem;"></i>
        </div>

        <h1 class="display-2 fw-bold text-danger mb-2">403</h1>
        <h4 class="mb-3"><?= __('errors.403_title') ?></h4>
        <p class="text-muted mb-4"><?= __('errors.403_message') ?></p>

        <a href="<?= BASE_URL ?>/dashboard" class="btn btn-primary">
          <i class="ph-duotone ph-house me-1"></i>
          <?= __('errors.go_dashboard') ?>
        </a>

      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
