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
        <?php
          $canToggleManuals  = can('manuals.activate') || can('manuals.deactivate');
          $canReplaceManuals = can('manuals.edit');
          $canDeleteManuals  = can('manuals.delete');
          $showAdminColumns  = $canToggleManuals || $canReplaceManuals || $canDeleteManuals;
        ?>
        <div class="table-responsive">
          <table class="table table-hover datatable">
            <thead>
              <tr>
                <th><?= __('manuals.name_field') ?></th>
                <th><?= __('manuals.col_file') ?></th>
                <th><?= __('common.type') ?></th>
                <th><?= __('common.size') ?></th>
                <?php if ($showAdminColumns): ?><th><?= __('common.status') ?></th><?php endif; ?>
                <th><?= __('manuals.uploaded_at') ?></th>
                <?php if ($showAdminColumns): ?><th><?= __('manuals.updated_at') ?></th><?php endif; ?>
                <th><?= __('common.actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($manuals as $manual): ?>
              <?php
                $manualId = (int)$manual['id'];
                $manIsActive = ($manual['status_slug'] ?? '') === 'active';
                $ext = strtoupper(pathinfo($manual['file_name'], PATHINFO_EXTENSION));
                $badgeClass = match($ext) { 'PDF' => 'danger', 'DOC', 'DOCX' => 'primary', default => 'secondary' };
                $createdAt = !empty($manual['created_at']) ? date('d/m/Y H:i', strtotime($manual['created_at'])) : __('common.not_available');
                $updatedAt = !empty($manual['updated_at']) ? date('d/m/Y H:i', strtotime($manual['updated_at'])) : __('common.not_available');
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($manual['title'], ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if ($manual['description']): ?>
                  <small class="text-muted"><?= htmlspecialchars($manual['description'], ENT_QUOTES, 'UTF-8') ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="small text-muted"><?= htmlspecialchars($manual['file_name'], ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td>
                  <span class="badge bg-<?= $badgeClass ?>"><?= $ext ?></span>
                </td>
                <td><?= number_format($manual['file_size'] / 1024, 1) ?> KB</td>
                <?php if ($showAdminColumns): ?>
                <td>
                  <span class="badge bg-<?= ($manual['status_slug'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                    <?= ($manual['status_slug'] ?? '') === 'active' ? __('common.active') : __('common.inactive') ?>
                  </span>
                </td>
                <?php endif; ?>
                <td><span class="small"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></span></td>
                <?php if ($showAdminColumns): ?>
                <td><span class="small"><?= htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8') ?></span></td>
                <?php endif; ?>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                  <a href="<?= BASE_URL ?>/manuals/download/<?= $manualId ?>" class="btn btn-sm btn-outline-success" title="<?= __('manuals.download') ?>">
                    <i class="ph-duotone ph-download-simple"></i>
                  </a>
                  <?php if ($canReplaceManuals): ?>
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#replaceManual<?= $manualId ?>" title="<?= __('manuals.replace') ?>">
                    <i class="ph-duotone ph-arrows-clockwise"></i>
                  </button>
                  <?php endif; ?>
                  <?php if (($manIsActive && can('manuals.deactivate')) || (!$manIsActive && can('manuals.activate'))): ?>
                  <form action="<?= BASE_URL ?>/manuals/toggle/<?= $manualId ?>" method="POST" class="d-inline form-manual-confirm"
                        data-confirm="<?= htmlspecialchars($manIsActive ? __('manuals.confirm_deactivate') : __('manuals.confirm_activate'), ENT_QUOTES, 'UTF-8') ?>"
                        data-confirm-button="<?= htmlspecialchars($manIsActive ? __('manuals.deactivate') : __('manuals.activate'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= CSRF::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-<?= $manIsActive ? 'warning' : 'secondary' ?>" title="<?= $manIsActive ? __('manuals.deactivate') : __('manuals.activate') ?>">
                      <i class="ph-duotone ph-<?= $manIsActive ? 'eye-slash' : 'eye' ?>"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                  <?php if ($canDeleteManuals): ?>
                  <form action="<?= BASE_URL ?>/manuals/delete/<?= $manualId ?>" method="POST" class="d-inline form-manual-confirm"
                        data-confirm="<?= htmlspecialchars(__('manuals.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>"
                        data-confirm-button="<?= htmlspecialchars(__('manuals.delete'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= CSRF::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= __('manuals.delete') ?>">
                      <i class="ph-duotone ph-trash"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($canReplaceManuals): ?>
          <?php foreach ($manuals as $manual): ?>
          <?php $manualId = (int)$manual['id']; ?>
          <div class="modal fade" id="replaceManual<?= $manualId ?>" tabindex="-1" aria-labelledby="replaceManualLabel<?= $manualId ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <form action="<?= BASE_URL ?>/manuals/replace/<?= $manualId ?>" method="POST" enctype="multipart/form-data">
                  <?= CSRF::field() ?>
                  <div class="modal-header">
                    <h5 class="modal-title" id="replaceManualLabel<?= $manualId ?>"><?= __('manuals.replace') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __('buttons.cancel') ?>"></button>
                  </div>
                  <div class="modal-body">
                    <p class="fw-semibold mb-2"><?= htmlspecialchars($manual['title'], ENT_QUOTES, 'UTF-8') ?></p>
                    <label for="manual_file_<?= $manualId ?>" class="form-label"><?= __('manuals.file_field') ?></label>
                    <input type="file" name="manual_file" id="manual_file_<?= $manualId ?>" class="form-control" accept=".pdf,.doc,.docx" required>
                    <div class="form-text"><?= __('manuals.file_hint') ?></div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('buttons.cancel') ?></button>
                    <button type="submit" class="btn btn-primary">
                      <i class="ph-duotone ph-arrows-clockwise me-1"></i><?= __('manuals.replace') ?>
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
// Textos traducidos, generados en PHP y sustituidos por marcador en el
// NOWDOC (no se puede evaluar __() dentro de un bloque <<<'JS', y el
// script usa jQuery con signos $ que no deben interpolarse).
$_siDataTableLangUrl = json_encode(
    \Core\Lang::getLocale() === 'es'
        ? 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        : 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/en-GB.json'
);
$_siConfirmActionFallback = json_encode(__('common.confirm_action'), JSON_UNESCAPED_UNICODE);
$_siConfirmFallback       = json_encode(__('common.confirm'), JSON_UNESCAPED_UNICODE);
$_siCancel                = json_encode(__('buttons.cancel'), JSON_UNESCAPED_UNICODE);

$extraScript = <<<'JS'
<script>
$(document).ready(function () {
  $('.datatable').DataTable({
    language: { url: __SI_DT_LANG_URL__ },
    pageLength: 10,
    order: [[0, 'asc']]
  });

  document.querySelectorAll('.form-manual-confirm').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: form.dataset.confirm || __SI_CONFIRM_ACTION__,
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton || __SI_CONFIRM__,
        cancelButtonText: __SI_CANCEL__,
        confirmButtonColor: '#4680ff'
      }).then(function (result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
});
</script>
JS;

$extraScript = strtr($extraScript, [
    '__SI_DT_LANG_URL__'   => $_siDataTableLangUrl,
    '__SI_CONFIRM_ACTION__'=> $_siConfirmActionFallback,
    '__SI_CONFIRM__'       => $_siConfirmFallback,
    '__SI_CANCEL__'        => $_siCancel,
]);
?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
