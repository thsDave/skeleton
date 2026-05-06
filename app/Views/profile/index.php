<?php
$pageTitle  = 'Mi Perfil';
$activeMenu = 'profile';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">Mi Perfil</h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Inicio</a></li>
            <li class="breadcrumb-item active">Mi Perfil</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<?php
$_prfImg = $user['profile_image'] ?? null;
$_prfAvatar = $_prfImg
    ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($_prfImg, ENT_QUOTES, 'UTF-8')
    : null;
$_prfStatus = $user['status_slug'] ?? $user['status'] ?? '';
?>

<div class="row">
  <!-- Tarjeta lateral de avatar -->
  <div class="col-lg-4 mb-4">
    <div class="card">
      <div class="card-body text-center py-4">
        <?php if ($_prfAvatar): ?>
          <img src="<?= $_prfAvatar ?>"
               alt="avatar"
               class="rounded-circle mx-auto d-block mb-3"
               style="width:80px;height:80px;object-fit:cover;"
               onerror="this.outerHTML='<div class=\'avtar avtar-xl bg-light-primary mx-auto mb-3\' style=\'width:80px;height:80px;display:flex;align-items:center;justify-content:center;border-radius:50%;\'><i class=\'ph-duotone ph-user-circle text-primary\' style=\'font-size:3.5rem;\'></i></div>'">
        <?php else: ?>
          <div class="avtar avtar-xl bg-light-primary mx-auto mb-3" style="width:80px;height:80px;display:flex;align-items:center;justify-content:center;border-radius:50%;">
            <i class="ph-duotone ph-user-circle text-primary" style="font-size:3.5rem;"></i>
          </div>
        <?php endif; ?>
        <h5 class="mb-1">
          <?= htmlspecialchars(trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
        </h5>
        <p class="text-muted mb-3"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <span class="badge bg-<?= $_prfStatus === 'active' ? 'success' : 'danger' ?> mb-3">
          <?= $_prfStatus === 'active' ? 'Activo' : ucfirst($user['status_name'] ?? $_prfStatus) ?>
        </span>
        <div class="d-grid">
          <a href="<?= BASE_URL ?>/profile/edit" class="btn btn-primary btn-sm">
            <i class="ph-duotone ph-pencil me-1"></i> Editar Perfil
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Información personal -->
  <div class="col-lg-8 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Información Personal</h5>
      </div>
      <div class="card-body">
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted">Nombres</p></div>
          <div class="col-sm-8"><p class="mb-0 fw-semibold"><?= htmlspecialchars($user['nombres'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p></div>
        </div>
        <hr class="my-2" />
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted">Apellidos</p></div>
          <div class="col-sm-8"><p class="mb-0 fw-semibold"><?= htmlspecialchars($user['apellidos'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p></div>
        </div>
        <hr class="my-2" />
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted">Teléfono</p></div>
          <div class="col-sm-8">
            <p class="mb-0 fw-semibold">
              <?= $user['telefono'] ? htmlspecialchars($user['telefono'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fst-italic">No especificado</span>' ?>
            </p>
          </div>
        </div>
        <hr class="my-2" />
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted">Dirección</p></div>
          <div class="col-sm-8">
            <p class="mb-0 fw-semibold">
              <?= $user['direccion'] ? htmlspecialchars($user['direccion'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fst-italic">No especificada</span>' ?>
            </p>
          </div>
        </div>
        <hr class="my-2" />
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted">Miembro desde</p></div>
          <div class="col-sm-8">
            <p class="mb-0 fw-semibold">
              <?= htmlspecialchars(date('d/m/Y', strtotime($user['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
