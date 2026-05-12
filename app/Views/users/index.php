<?php
$pageTitle  = __('users.title');
$activeMenu = 'users';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('users.management_title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('menu.users') ?></li>
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
        <h5 class="mb-0"><i class="ph-duotone ph-users-three me-2 text-primary"></i><?= __('users.system_users') ?></h5>
        <div class="d-flex gap-2 flex-wrap">
        <?php if (can('users.export')): ?>
        <a href="<?= BASE_URL ?>/users/export/excel" class="btn btn-outline-success btn-sm">
          <i class="ph-duotone ph-file-xls me-1"></i> <?= __('users.export_excel') ?>
        </a>
        <?php endif; ?>
        <?php if (can('users.create')): ?>
        <a href="<?= BASE_URL ?>/users/create" class="btn btn-primary btn-sm">
          <i class="ph-duotone ph-user-plus me-1"></i> <?= __('users.new_user') ?>
        </a>
        <?php endif; ?>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="usersTable" class="table table-hover align-middle" style="width:100%">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th><?= __('users.col_photo') ?></th>
                <th><?= __('users.col_name') ?></th>
                <th><?= __('users.col_email') ?></th>
                <th><?= __('users.col_phone') ?></th>
                <th><?= __('users.col_role') ?></th>
                <th><?= __('users.col_status') ?></th>
                <th><?= __('users.col_registered') ?></th>
                <th class="text-center"><?= __('users.col_actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <?php
                $avatar = $u['profile_image']
                    ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($u['profile_image'], ENT_QUOTES, 'UTF-8')
                    : BASE_URL . '/assets/images/user/avatar-1.jpg';
                $statusSlug  = $u['status_slug'] ?? '';
                $statusBadge = match($statusSlug) {
                    'active'   => 'success',
                    'inactive' => 'secondary',
                    'blocked'  => 'danger',
                    default    => 'secondary',
                };
                $isLocked = !empty($u['locked_until']) && strtotime($u['locked_until']) > time();
                $fullName = htmlspecialchars(trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')), ENT_QUOTES, 'UTF-8');
              ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td>
                  <img src="<?= $avatar ?>"
                       alt="avatar"
                       class="rounded-circle"
                       style="width:36px;height:36px;object-fit:cover;"
                       onerror="this.src='<?= BASE_URL ?>/assets/images/user/avatar-1.jpg'">
                </td>
                <td>
                  <?= $fullName ?>
                  <?php if ($isLocked): ?>
                    <span class="badge bg-danger ms-1" title="<?= __('users.locked_until') ?>: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($u['locked_until'])), ENT_QUOTES, 'UTF-8') ?>">
                      <i class="ph-duotone ph-lock me-1"></i><?= __('users.locked') ?>
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($u['force_password_change'])): ?>
                    <span class="badge bg-warning text-dark ms-1">
                      <i class="ph-duotone ph-warning me-1"></i><?= __('users.force_password_change_badge') ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u['telefono'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    <?= htmlspecialchars($u['role_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-<?= $statusBadge ?>">
                    <?= htmlspecialchars($u['status_name'] ?? ucfirst($statusSlug), ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($u['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-center">
                  <?php if (can('users.edit')): ?>
                  <a href="<?= BASE_URL ?>/users/edit/<?= (int)$u['id'] ?>"
                     class="btn btn-sm btn-outline-primary me-1"
                     title="<?= __('buttons.edit') ?>">
                    <i class="ph-duotone ph-pencil"></i>
                  </a>
                  <?php endif; ?>

                  <?php if ($isLocked && can('users.unlock')): ?>
                  <form action="<?= BASE_URL ?>/users/unlock/<?= (int)$u['id'] ?>"
                        method="POST"
                        class="d-inline form-unlock">
                    <?= \Core\CSRF::field() ?>
                    <button type="button"
                            class="btn btn-sm btn-outline-warning btn-unlock me-1"
                            title="<?= __('users.unlock') ?>"
                            data-name="<?= $fullName ?>">
                      <i class="ph-duotone ph-lock-open"></i>
                    </button>
                  </form>
                  <?php endif; ?>

                  <?php if (can('users.delete') && $statusSlug !== 'inactive'): ?>
                  <form action="<?= BASE_URL ?>/users/delete/<?= (int)$u['id'] ?>"
                        method="POST"
                        class="d-inline form-inactivate">
                    <?= \Core\CSRF::field() ?>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger btn-inactivate"
                            title="<?= __('users.inactivate') ?>"
                            data-name="<?= $fullName ?>">
                      <i class="ph-duotone ph-user-minus"></i>
                    </button>
                  </form>
                  <?php elseif (can('users.delete')): ?>
                  <button class="btn btn-sm btn-outline-secondary" disabled title="<?= __('users.already_inactive') ?>">
                    <i class="ph-duotone ph-user-minus"></i>
                  </button>
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
  $('#usersTable').DataTable({
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
    },
    columnDefs: [
      { orderable: false, targets: [1, 8] }
    ],
    order: [[0, 'desc']],
    pageLength: 10
  });

  $(document).on('click', '.btn-inactivate', function () {
    var form = $(this).closest('form');
    var name = $(this).data('name');
    Swal.fire({
      title: '¿Inactivar usuario?',
      html: 'El usuario <strong>' + name + '</strong> no podrá iniciar sesión.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, inactivar',
      cancelButtonText: 'Cancelar'
    }).then(function (result) {
      if (result.isConfirmed) form.submit();
    });
  });

  $(document).on('click', '.btn-unlock', function () {
    var form = $(this).closest('form');
    var name = $(this).data('name');
    Swal.fire({
      title: '¿Desbloquear usuario?',
      html: 'Se quitará el bloqueo de inicio de sesión a <strong>' + name + '</strong>.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#f39c12',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, desbloquear',
      cancelButtonText: 'Cancelar'
    }).then(function (result) {
      if (result.isConfirmed) form.submit();
    });
  });
});
</script>
JS;
?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
