<?php
$pageTitle  = 'Mi Cuenta';
$activeMenu = 'account';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">Mi Cuenta</h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Inicio</a></li>
            <li class="breadcrumb-item active">Mi Cuenta</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<?php $_accStatus = $user['status_slug'] ?? ''; ?>

<div class="row">
  <!-- Correo electrónico -->
  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="ph-duotone ph-envelope me-2 text-primary"></i>Correo Electrónico
        </h5>
      </div>
      <div class="card-body d-flex flex-column">
        <p class="text-muted mb-1 small">Correo actual</p>
        <h6 class="mb-3"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></h6>
        <p class="text-muted small flex-grow-1">
          Tu correo electrónico se usa para iniciar sesión. Al cambiarlo, deberás usar el nuevo correo en tu próximo acceso.
        </p>
        <div>
          <a href="<?= BASE_URL ?>/account/edit-email" class="btn btn-primary btn-sm">
            <i class="ph-duotone ph-pencil me-1"></i> Cambiar Correo
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Contraseña -->
  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="ph-duotone ph-lock-key me-2 text-warning"></i>Contraseña
        </h5>
      </div>
      <div class="card-body d-flex flex-column">
        <p class="text-muted mb-1 small">Última actualización de contraseña</p>
        <h6 class="mb-3">
          <?php if (!empty($user['password_changed_at'])): ?>
            <?= htmlspecialchars(date('d/m/Y \a \l\a\s H:i', strtotime($user['password_changed_at'])), ENT_QUOTES, 'UTF-8') ?>
          <?php else: ?>
            <span class="text-muted fst-italic">Sin cambios registrados</span>
          <?php endif; ?>
        </h6>
        <p class="text-muted small flex-grow-1">
          Mantén tu contraseña segura. Usa al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.
        </p>
        <div>
          <a href="<?= BASE_URL ?>/account/edit-password" class="btn btn-warning btn-sm">
            <i class="ph-duotone ph-lock-key me-1"></i> Cambiar Contraseña
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Información de seguridad -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ph-duotone ph-shield-check me-2 text-success"></i>Información de Sesión</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-sm-6 col-md-3 mb-2">
            <p class="mb-0 text-muted small">Último acceso</p>
            <p class="mb-0 fw-semibold">
              <?= !empty($user['last_login_at'])
                ? htmlspecialchars(date('d/m/Y H:i', strtotime($user['last_login_at'])), ENT_QUOTES, 'UTF-8')
                : '<span class="text-muted">—</span>' ?>
            </p>
          </div>
          <div class="col-sm-6 col-md-3 mb-2">
            <p class="mb-0 text-muted small">IP del último acceso</p>
            <p class="mb-0 fw-semibold">
              <?= htmlspecialchars($user['last_login_ip'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </p>
          </div>
          <div class="col-sm-6 col-md-3 mb-2">
            <p class="mb-0 text-muted small">Estado de cuenta</p>
            <p class="mb-0">
              <span class="badge bg-<?= $_accStatus === 'active' ? 'success' : 'danger' ?>">
                <?= $_accStatus === 'active' ? 'Activa' : ucfirst($user['status_name'] ?? $_accStatus) ?>
              </span>
            </p>
          </div>
          <div class="col-sm-6 col-md-3 mb-2">
            <p class="mb-0 text-muted small">Timeout de sesión</p>
            <p class="mb-0 fw-semibold">30 minutos de inactividad</p>
          </div>
        </div>
        <?php if (can('account.sessions.view')): ?>
          <div class="mt-3">
            <a href="<?= BASE_URL ?>/account/sessions" class="btn btn-outline-primary btn-sm">
              <i class="ph-duotone ph-devices me-1"></i>
              <?= __('sessions.view_my_sessions') ?>
            </a>
            <?php if (can('account.sessions.history')): ?>
              <a href="<?= BASE_URL ?>/account/sessions/history" class="btn btn-outline-secondary btn-sm ms-1">
                <i class="ph-duotone ph-clock-counter-clockwise me-1"></i>
                <?= __('sessions.view_history') ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Cuentas externas vinculadas -->
<?php
$authSettings     = $authSettings     ?? ['external_login_enabled' => 0];
$enabledProviders = $enabledProviders ?? [];
$linkedAccounts   = $linkedAccounts   ?? [];

$linkedBySlug = [];
foreach ($linkedAccounts as $la) {
    $linkedBySlug[$la['provider_slug']] = $la;
}
?>
<?php if ($authSettings['external_login_enabled'] && !empty($enabledProviders)): ?>
<div class="row mt-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="ph-duotone ph-plugs-connected me-2 text-info"></i>
          <?= __('account.external_accounts') ?>
        </h5>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-3"><?= __('account.external_accounts_desc') ?></p>
        <div class="row g-3">
          <?php foreach ($enabledProviders as $prov): ?>
          <?php
            $pSlug   = $prov['slug'] ?? '';
            $pName   = htmlspecialchars($prov['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $linked  = $linkedBySlug[$pSlug] ?? null;
            $iconClass = match ($pSlug) {
                'google'    => 'ph-duotone ph-google-logo text-danger',
                'microsoft' => 'ph-duotone ph-windows-logo text-primary',
                'github'    => 'ph-duotone ph-github-logo',
                default     => 'ph-duotone ph-plugs-connected text-secondary',
            };
          ?>
          <div class="col-md-4">
            <div class="card border h-100">
              <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="<?= $iconClass ?>" style="font-size:1.5rem;"></i>
                  <span class="fw-semibold"><?= $pName ?></span>
                </div>

                <?php if ($linked): ?>
                  <div class="mb-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <i class="ph-duotone ph-check me-1"></i><?= __('account.linked') ?>
                    </span>
                  </div>
                  <?php if (!empty($linked['provider_email'])): ?>
                  <p class="small text-muted mb-1">
                    <i class="ph-duotone ph-envelope me-1"></i>
                    <?= htmlspecialchars($linked['provider_email'], ENT_QUOTES, 'UTF-8') ?>
                  </p>
                  <?php endif; ?>
                  <?php if (!empty($linked['last_login_at'])): ?>
                  <p class="small text-muted mb-0 flex-grow-1">
                    <i class="ph-duotone ph-clock me-1"></i>
                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($linked['last_login_at'])), ENT_QUOTES, 'UTF-8') ?>
                  </p>
                  <?php else: ?>
                  <div class="flex-grow-1"></div>
                  <?php endif; ?>
                  <div class="mt-3">
                    <form action="<?= BASE_URL ?>/account/external/unlink/<?= (int)$linked['id'] ?>" method="POST" class="form-unlink-account">
                      <?= \Core\CSRF::field() ?>
                      <input type="hidden" name="provider_name" value="<?= $pName ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                        <i class="ph-duotone ph-link-break me-1"></i>
                        <?= __('account.unlink_provider') ?>
                      </button>
                    </form>
                  </div>
                <?php else: ?>
                  <div class="mb-2">
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                      <?= __('account.not_linked') ?>
                    </span>
                  </div>
                  <div class="flex-grow-1"></div>
                  <div class="mt-3">
                    <a href="<?= BASE_URL ?>/account/external/link/<?= htmlspecialchars($pSlug, ENT_QUOTES, 'UTF-8') ?>"
                       class="btn btn-sm btn-outline-primary w-100">
                      <i class="ph-duotone ph-link me-1"></i>
                      <?= __('account.link_provider') ?>
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php $extraScript = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.form-unlink-account').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var pName = form.querySelector('[name="provider_name"]');
      var name  = pName ? pName.value : 'este proveedor';
      Swal.fire({
        icon: 'warning',
        title: '¿Desvincular cuenta?',
        html: '¿Deseas desvincular tu cuenta de <strong>' + name + '</strong>?<br><small class="text-muted">Ya no podrás iniciar sesión con este proveedor.</small>',
        showCancelButton: true,
        confirmButtonText: 'Sí, desvincular',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d63031',
        cancelButtonColor: '#6c757d'
      }).then(function (result) {
        if (result.isConfirmed) form.submit();
      });
    });
  });
});
</script>
JS;
?>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
