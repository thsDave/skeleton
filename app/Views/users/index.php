<?php
$pageTitle  = 'Usuarios';
$activeMenu = 'users';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">Gestión de Usuarios</h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Inicio</a></li>
            <li class="breadcrumb-item active">Usuarios</li>
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
        <h5 class="mb-0"><i class="ph-duotone ph-users-three me-2 text-primary"></i>Usuarios del Sistema</h5>
        <a href="<?= BASE_URL ?>/users/create" class="btn btn-primary btn-sm">
          <i class="ph-duotone ph-user-plus me-1"></i> Nuevo Usuario
        </a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="usersTable" class="table table-hover align-middle" style="width:100%">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Foto</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Registro</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <?php
                $avatar = $u['profile_image']
                    ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($u['profile_image'], ENT_QUOTES, 'UTF-8')
                    : BASE_URL . '/assets/images/user/avatar-1.jpg';
                $statusSlug = $u['status_slug'] ?? '';
                $statusBadge = match($statusSlug) {
                    'active'   => 'success',
                    'inactive' => 'secondary',
                    'blocked'  => 'danger',
                    default    => 'secondary',
                };
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
                <td><?= htmlspecialchars(trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u['telefono'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <span class="badge bg-light-primary text-primary">
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
                  <a href="<?= BASE_URL ?>/users/edit/<?= (int)$u['id'] ?>"
                     class="btn btn-sm btn-outline-primary me-1"
                     title="Editar">
                    <i class="ph-duotone ph-pencil"></i>
                  </a>
                  <?php if ($statusSlug !== 'inactive'): ?>
                  <form action="<?= BASE_URL ?>/users/delete/<?= (int)$u['id'] ?>"
                        method="POST"
                        class="d-inline form-inactivate">
                    <?= \Core\CSRF::field() ?>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger btn-inactivate"
                            title="Inactivar"
                            data-name="<?= htmlspecialchars(trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                      <i class="ph-duotone ph-user-minus"></i>
                    </button>
                  </form>
                  <?php else: ?>
                  <button class="btn btn-sm btn-outline-secondary" disabled title="Ya inactivo">
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
});
</script>
JS;
?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
