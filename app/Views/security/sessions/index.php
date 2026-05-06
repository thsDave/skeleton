<?php
$pageTitle  = __('security.sessions.title');
$activeMenu = 'security_sessions';
require dirname(dirname(__DIR__)) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('security.sessions.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('security.sessions.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="ph-duotone ph-lock-key me-2 text-primary"></i>
          <?= __('security.sessions.card_title') ?>
        </h5>
      </div>
      <div class="card-body">
        <form action="<?= BASE_URL ?>/security/sessions/update" method="POST" novalidate>
          <?= \Core\CSRF::field() ?>

          <div class="mb-4">
            <div class="form-check form-switch">
              <input
                class="form-check-input"
                type="checkbox"
                name="session_lock_enabled"
                id="session_lock_enabled"
                value="1"
                <?= ($settings['session_lock_enabled'] ?? 1) ? 'checked' : '' ?>
              />
              <label class="form-check-label fw-semibold" for="session_lock_enabled">
                <?= __('security.sessions.enable_label') ?>
              </label>
            </div>
            <div class="form-text text-muted">
              <?php if ($settings['session_lock_enabled'] ?? 1): ?>
                <span class="badge bg-success"><?= __('security.sessions.enabled') ?></span>
              <?php else: ?>
                <span class="badge bg-secondary"><?= __('security.sessions.disabled') ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="mb-4">
            <label for="session_inactivity_seconds" class="form-label fw-semibold">
              <?= __('security.sessions.inactivity_time') ?>
            </label>
            <div class="input-group" style="max-width:280px;">
              <input
                type="number"
                name="session_inactivity_seconds"
                id="session_inactivity_seconds"
                class="form-control"
                value="<?= (int) ($settings['session_inactivity_seconds'] ?? 900) ?>"
                min="60"
                max="86400"
                required
              />
              <span class="input-group-text">seg</span>
            </div>
            <div class="form-text text-muted"><?= __('security.sessions.seconds_hint') ?></div>
            <div class="form-text text-muted mt-1" id="time-preview"></div>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="ph-duotone ph-floppy-disk me-1"></i> <?= __('buttons.save') ?>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Info card -->
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="ph-duotone ph-info me-2 text-info"></i>Información</h6>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0 small text-muted">
          <li class="mb-2"><i class="ph-duotone ph-check-circle text-success me-1"></i>
            El bloqueo se activa tras el tiempo de inactividad configurado.
          </li>
          <li class="mb-2"><i class="ph-duotone ph-check-circle text-success me-1"></i>
            El usuario puede desbloquear la sesión ingresando su contraseña.
          </li>
          <li class="mb-2"><i class="ph-duotone ph-check-circle text-success me-1"></i>
            Tras desbloquear, el usuario es redirigido a la página donde estaba.
          </li>
          <li class="mb-0"><i class="ph-duotone ph-warning text-warning me-1"></i>
            El timeout de sesión global (30 min) sigue activo independientemente.
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php require dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>

<script>
(function () {
  var input = document.getElementById('session_inactivity_seconds');
  var preview = document.getElementById('time-preview');

  function updatePreview() {
    var secs = parseInt(input.value, 10) || 0;
    if (secs <= 0) { preview.textContent = ''; return; }
    var h = Math.floor(secs / 3600);
    var m = Math.floor((secs % 3600) / 60);
    var s = secs % 60;
    var parts = [];
    if (h) parts.push(h + ' hora' + (h > 1 ? 's' : ''));
    if (m) parts.push(m + ' minuto' + (m > 1 ? 's' : ''));
    if (s) parts.push(s + ' segundo' + (s > 1 ? 's' : ''));
    preview.textContent = '= ' + parts.join(', ');
  }

  input.addEventListener('input', updatePreview);
  updatePreview();
})();
</script>
