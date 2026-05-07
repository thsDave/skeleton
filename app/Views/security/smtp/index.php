<?php
$pageTitle  = __('smtp.title');
$activeMenu = 'security_smtp';
$errors     = \Core\Session::getFlash('errors', []);
$old        = \Core\Session::getFlash('old', []);

$v = fn(string $k, string $default = '') =>
    htmlspecialchars((string) ($old[$k] ?? $settings[$k] ?? $default));

require dirname(dirname(__DIR__)) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('smtp.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('smtp.title') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <i class="ph-duotone ph-warning-circle me-2"></i>
  <ul class="mb-0 ps-3">
    <?php foreach ($errors as $e): ?>
      <li><?= htmlspecialchars($e) ?></li>
    <?php endforeach; ?>
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">

  <!-- ── Formulario de configuración ─────────────────────────────────────── -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">
          <i class="ph-duotone ph-envelope me-2 text-primary"></i>
          <?= __('smtp.card_title') ?>
        </h5>
        <?php if ($settings['is_verified']): ?>
          <span class="badge bg-success">
            <i class="ph-duotone ph-check-circle me-1"></i><?= __('smtp.verified') ?>
          </span>
        <?php else: ?>
          <span class="badge bg-secondary"><?= __('smtp.not_verified') ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body">

        <?php if (can('security_smtp.edit')): ?>
        <form action="<?= BASE_URL ?>/security/smtp/update" method="POST" novalidate>
          <?= \Core\CSRF::field() ?>

          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label for="host" class="form-label fw-semibold">
                <?= __('smtp.host') ?> <span class="text-danger">*</span>
              </label>
              <input type="text" name="host" id="host"
                class="form-control"
                value="<?= $v('host') ?>"
                placeholder="smtp.gmail.com"
                required
              />
            </div>
            <div class="col-md-4">
              <label for="port" class="form-label fw-semibold">
                <?= __('smtp.port') ?> <span class="text-danger">*</span>
              </label>
              <input type="number" name="port" id="port"
                class="form-control"
                value="<?= $v('port', '587') ?>"
                min="1" max="65535"
                required
              />
            </div>
          </div>

          <div class="mb-3">
            <label for="encryption" class="form-label fw-semibold"><?= __('smtp.encryption') ?></label>
            <select name="encryption" id="encryption" class="form-select">
              <?php
              $enc = $old['encryption'] ?? $settings['encryption'] ?? 'tls';
              foreach (['tls' => 'TLS (STARTTLS — port 587)', 'ssl' => 'SSL (port 465)', 'none' => __('smtp.encryption_none')] as $val => $label):
              ?>
              <option value="<?= $val ?>" <?= $enc === $val ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="username" class="form-label fw-semibold">
                <?= __('smtp.username') ?> <span class="text-danger">*</span>
              </label>
              <input type="text" name="username" id="username"
                class="form-control"
                value="<?= $v('username') ?>"
                placeholder="user@example.com"
                autocomplete="username"
                required
              />
            </div>
            <div class="col-md-6">
              <label for="password" class="form-label fw-semibold"><?= __('smtp.password') ?></label>
              <div class="input-group">
                <input type="password" name="password" id="password"
                  class="form-control"
                  placeholder="<?= $settings['password_enc'] !== '' ? __('smtp.password_stored') : __('smtp.password_placeholder') ?>"
                  autocomplete="new-password"
                />
                <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                  <i class="ph-duotone ph-eye" id="eyeIcon"></i>
                </button>
              </div>
              <?php if ($settings['password_enc'] !== ''): ?>
              <div class="form-text text-muted">
                <i class="ph-duotone ph-lock-simple me-1"></i><?= __('smtp.password_hint_stored') ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="from_address" class="form-label fw-semibold">
                <?= __('smtp.from_address') ?> <span class="text-danger">*</span>
              </label>
              <input type="email" name="from_address" id="from_address"
                class="form-control"
                value="<?= $v('from_address') ?>"
                placeholder="no-reply@example.com"
                required
              />
            </div>
            <div class="col-md-6">
              <label for="from_name" class="form-label fw-semibold"><?= __('smtp.from_name') ?></label>
              <input type="text" name="from_name" id="from_name"
                class="form-control"
                value="<?= $v('from_name') ?>"
                placeholder="Skeleton"
              />
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ph-duotone ph-floppy-disk me-1"></i> <?= __('buttons.save') ?>
            </button>
          </div>
        </form>
        <?php else: ?>
          <div class="alert alert-info mb-0">
            <i class="ph-duotone ph-info me-2"></i><?= __('roles_permissions.view_only_notice') ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- ── Panel lateral ─────────────────────────────────────────────────── -->
  <div class="col-lg-4">

    <!-- Estado del último test -->
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0"><i class="ph-duotone ph-activity me-2 text-info"></i><?= __('smtp.test.status_card') ?></h6>
      </div>
      <div class="card-body small">
        <?php if ($settings['last_tested_at']): ?>
          <p class="mb-1">
            <strong><?= __('smtp.test.last_tested') ?>:</strong><br>
            <?= htmlspecialchars($settings['last_tested_at']) ?>
          </p>
          <p class="mb-0">
            <strong><?= __('smtp.test.result') ?>:</strong><br>
            <?php if ($settings['is_verified']): ?>
              <span class="text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= htmlspecialchars((string) $settings['last_test_status']) ?></span>
            <?php else: ?>
              <span class="text-danger"><i class="ph-duotone ph-x-circle me-1"></i><?= htmlspecialchars((string) $settings['last_test_status']) ?></span>
            <?php endif; ?>
          </p>
        <?php else: ?>
          <p class="text-muted mb-0"><?= __('smtp.test.never_tested') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Botón de prueba -->
    <?php if (can('security_smtp.test')): ?>
    <div class="card mb-3">
      <div class="card-body">
        <p class="small text-muted mb-3"><?= __('smtp.test.description') ?></p>
        <form action="<?= BASE_URL ?>/security/smtp/test" method="POST" id="testForm">
          <?= \Core\CSRF::field() ?>
          <button type="button" class="btn btn-outline-primary w-100" id="btnTest">
            <i class="ph-duotone ph-paper-plane-tilt me-1"></i> <?= __('smtp.test.button') ?>
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Información -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><i class="ph-duotone ph-info me-2 text-secondary"></i><?= __('smtp.info_card') ?></h6>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0 small text-muted">
          <li class="mb-2"><i class="ph-duotone ph-check-circle text-success me-1"></i><?= __('smtp.info.db_priority') ?></li>
          <li class="mb-2"><i class="ph-duotone ph-check-circle text-success me-1"></i><?= __('smtp.info.password_encrypted') ?></li>
          <li class="mb-2"><i class="ph-duotone ph-check-circle text-success me-1"></i><?= __('smtp.info.test_sends_to_you') ?></li>
          <li class="mb-0"><i class="ph-duotone ph-warning text-warning me-1"></i><?= __('smtp.info.env_fallback') ?></li>
        </ul>
      </div>
    </div>

  </div>
</div>

<?php require dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>

<script>
(function () {
  // Toggle password visibility
  var toggleBtn = document.getElementById('togglePwd');
  var pwdInput  = document.getElementById('password');
  var eyeIcon   = document.getElementById('eyeIcon');
  if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener('click', function () {
      var isText = pwdInput.type === 'text';
      pwdInput.type = isText ? 'password' : 'text';
      eyeIcon.className = isText ? 'ph-duotone ph-eye' : 'ph-duotone ph-eye-slash';
    });
  }

  // Test button with SweetAlert2 confirmation
  var btnTest  = document.getElementById('btnTest');
  var testForm = document.getElementById('testForm');
  if (btnTest && testForm) {
    btnTest.addEventListener('click', function () {
      Swal.fire({
        title: <?= json_encode(__('smtp.test.confirm_title')) ?>,
        text:  <?= json_encode(__('smtp.test.confirm_text')) ?>,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: <?= json_encode(__('smtp.test.confirm_yes')) ?>,
        cancelButtonText: <?= json_encode(__('alerts.cancel')) ?>,
        confirmButtonColor: '#0d6efd',
      }).then(function (result) {
        if (result.isConfirmed) {
          testForm.submit();
        }
      });
    });
  }
})();
</script>
