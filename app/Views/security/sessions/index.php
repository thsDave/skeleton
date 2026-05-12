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

<?php if (can('security_sessions.view_active')): ?>
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="ph-duotone ph-devices me-2 text-primary"></i>
      <?= __('sessions.active_sessions') ?>
    </h5>
  </div>
  <div class="card-body">
    <?php if (empty($activeSessions)): ?>
      <div class="text-center text-muted py-4">
        <i class="ph-duotone ph-devices d-block mb-2" style="font-size:2rem;"></i>
        <?= __('sessions.no_active_sessions') ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th><?= __('sessions.user') ?></th>
              <th><?= __('sessions.device') ?></th>
              <th><?= __('sessions.ip_address') ?></th>
              <th><?= __('sessions.started_at') ?></th>
              <th><?= __('sessions.last_activity') ?></th>
              <th><?= __('sessions.status') ?></th>
              <th class="text-end"><?= __('sessions.actions') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activeSessions as $session): ?>
              <?php
                $userName = trim((string)($session['user_name'] ?? ''));
                $userName = $userName !== '' ? $userName : ($session['email'] ?? ('ID ' . (int)$session['user_id']));
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="small text-muted"><?= htmlspecialchars($session['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($session['browser'] ?? __('sessions.unknown_browser'), ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="small text-muted">
                    <?= htmlspecialchars(($session['platform'] ?? __('sessions.unknown_platform')) . ' / ' . ($session['device_type'] ?? __('sessions.unknown_device')), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($session['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= !empty($session['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($session['created_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                <td><?= !empty($session['last_activity_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($session['last_activity_at'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                <td>
                  <?php if (!empty($session['is_current'])): ?>
                    <span class="badge bg-primary"><?= __('sessions.current_session') ?></span>
                  <?php else: ?>
                    <span class="badge bg-success"><?= __('sessions.active') ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                    <?php if (can('security_sessions.revoke') && empty($session['is_current'])): ?>
                      <form action="<?= BASE_URL ?>/security/sessions/revoke/<?= (int)$session['id'] ?>" method="POST" class="js-confirm-session-action">
                        <?= \Core\CSRF::field() ?>
                        <input type="hidden" name="confirm_message" value="<?= htmlspecialchars(__('sessions.confirm_close_session'), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                          <i class="ph-duotone ph-x-circle me-1"></i>
                          <?= __('sessions.close_session') ?>
                        </button>
                      </form>
                    <?php endif; ?>
                    <?php if (can('security_sessions.revoke_user_all')): ?>
                      <form action="<?= BASE_URL ?>/security/sessions/revoke-user/<?= (int)$session['user_id'] ?>" method="POST" class="js-confirm-session-action">
                        <?= \Core\CSRF::field() ?>
                        <input type="hidden" name="confirm_message" value="<?= htmlspecialchars(__('sessions.confirm_close_all_user_sessions'), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                          <i class="ph-duotone ph-sign-out me-1"></i>
                          <?= __('sessions.close_all_user_sessions') ?>
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
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php
$extraScript = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-confirm-session-action').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var input = form.querySelector('[name="confirm_message"]');
      Swal.fire({
        icon: 'warning',
        title: input ? input.value : 'Confirmar acción',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d63031',
        cancelButtonColor: '#6c757d'
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
?>

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

<?php require dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
