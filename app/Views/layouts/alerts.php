<?php
use Core\Session;

$success     = Session::getFlash('success');
$error       = Session::getFlash('error');
$flashErrors = Session::getFlash('errors', []);
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <i class="ph-duotone ph-check-circle me-2"></i>
  <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <i class="ph-duotone ph-warning-circle me-2"></i>
  <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<?php if (!empty($flashErrors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <i class="ph-duotone ph-warning-circle me-2"></i>
  <?php if (count($flashErrors) === 1): ?>
    <?= htmlspecialchars(array_values($flashErrors)[0], ENT_QUOTES, 'UTF-8') ?>
  <?php else: ?>
    <ul class="mb-0 mt-1">
      <?php foreach ($flashErrors as $msg): ?>
        <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>
