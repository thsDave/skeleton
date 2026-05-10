<?php
$pageTitle  = __('security_authentication.configure_provider') . ' — ' . htmlspecialchars($provider['name'] ?? '', ENT_QUOTES, 'UTF-8');
$activeMenu = 'security_authentication';
$errors     = $errors ?? [];
$old        = $old    ?? [];
$provider   = $provider ?? [];

require dirname(dirname(__DIR__)) . '/layouts/main.php';

$slug = $provider['slug'] ?? '';
$hasSecret = !empty($provider['client_secret']);
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0">
            <?= __('security_authentication.configure_provider') ?>:
            <?= htmlspecialchars($provider['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/security/authentication"><?= __('security_authentication.title') ?></a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($provider['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8">
    <form action="<?= BASE_URL ?>/security/authentication/providers/update/<?= (int)($provider['id'] ?? 0) ?>" method="POST" novalidate>
      <?= \Core\CSRF::field() ?>

      <!-- Redirect URI -->
      <div class="card mb-3">
        <div class="card-header bg-info-subtle">
          <h6 class="mb-0 text-info">
            <i class="ph-duotone ph-link me-2"></i>
            <?= __('security_authentication.redirect_uri_copy') ?>
          </h6>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-2"><?= __('security_authentication.redirect_uri_copy_desc') ?></p>
          <div class="input-group">
            <input type="text" class="form-control font-monospace small bg-light"
              id="callbackUriDisplay"
              value="<?= htmlspecialchars($suggestedRedirectUri ?? '', ENT_QUOTES, 'UTF-8') ?>"
              readonly>
            <button type="button" class="btn btn-outline-secondary" id="btnCopyUri"
              data-msg-copied="<?= htmlspecialchars(__('security_authentication.redirect_uri_copied'), ENT_QUOTES, 'UTF-8') ?>"
              data-msg-failed="<?= htmlspecialchars(__('security_authentication.redirect_uri_copy_failed'), ENT_QUOTES, 'UTF-8') ?>">
              <i class="ph-duotone ph-copy"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Credenciales OAuth -->
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="ph-duotone ph-key me-2 text-warning"></i>
            <?= __('security_authentication.credentials') ?>
          </h5>
        </div>
        <div class="card-body">

          <div class="mb-3">
            <label for="client_id" class="form-label fw-semibold small">
              <?= __('security_authentication.client_id') ?> <span class="text-danger">*</span>
            </label>
            <input type="text" name="client_id" id="client_id"
              class="form-control"
              value="<?= htmlspecialchars($old['client_id'] ?? $provider['client_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              placeholder="<?= __('security_authentication.client_id_placeholder') ?>">
          </div>

          <div class="mb-3">
            <label for="client_secret" class="form-label fw-semibold small">
              <?= __('security_authentication.client_secret') ?>
              <?php if ($hasSecret): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">
                  <i class="ph-duotone ph-lock me-1"></i><?= __('smtp.password_saved') ?>
                </span>
              <?php endif; ?>
            </label>
            <div class="input-group">
              <input type="password" name="client_secret" id="client_secret"
                class="form-control"
                placeholder="<?= $hasSecret ? __('security_authentication.secret_keep_blank') : __('security_authentication.client_secret_placeholder') ?>">
              <button type="button" class="btn btn-outline-secondary" id="btnToggleSecret">
                <i class="ph-duotone ph-eye" id="iconSecret"></i>
              </button>
            </div>
            <?php if ($hasSecret): ?>
              <div class="form-text text-muted">
                <i class="ph-duotone ph-info me-1"></i><?= __('security_authentication.secret_hint_stored') ?>
              </div>
            <?php endif; ?>
          </div>

          <?php if ($slug === 'microsoft'): ?>
          <div class="mb-3">
            <label for="tenant_id" class="form-label fw-semibold small">
              <?= __('security_authentication.tenant_id') ?>
            </label>
            <input type="text" name="tenant_id" id="tenant_id"
              class="form-control"
              value="<?= htmlspecialchars($old['tenant_id'] ?? $provider['tenant_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              placeholder="common — o tu Tenant ID de Azure">
            <div class="form-text text-muted"><?= __('security_authentication.tenant_id_desc') ?></div>
          </div>
          <?php else: ?>
            <input type="hidden" name="tenant_id" value="">
          <?php endif; ?>

          <div class="mb-3">
            <label for="redirect_uri" class="form-label fw-semibold small">
              <?= __('security_authentication.redirect_uri') ?>
            </label>
            <input type="url" name="redirect_uri" id="redirect_uri"
              class="form-control"
              value="<?= htmlspecialchars($old['redirect_uri'] ?? $provider['redirect_uri'] ?? $suggestedRedirectUri ?? '', ENT_QUOTES, 'UTF-8') ?>"
              placeholder="<?= htmlspecialchars($suggestedRedirectUri ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text text-muted"><?= __('security_authentication.redirect_uri_desc') ?></div>
          </div>

          <div class="mb-3">
            <label for="scopes" class="form-label fw-semibold small">
              <?= __('security_authentication.scopes') ?>
            </label>
            <input type="text" name="scopes" id="scopes"
              class="form-control"
              value="<?= htmlspecialchars($old['scopes'] ?? $provider['scopes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              placeholder="openid email profile">
            <div class="form-text text-muted"><?= __('security_authentication.scopes_desc') ?></div>
          </div>

        </div>
      </div>

      <!-- URLs OAuth (avanzado) -->
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#collapseUrls" aria-expanded="false">
          <h5 class="mb-0">
            <i class="ph-duotone ph-link-simple-horizontal me-2 text-secondary"></i>
            <?= __('security_authentication.oauth_urls') ?>
          </h5>
          <i class="ph-duotone ph-caret-down"></i>
        </div>
        <div class="collapse" id="collapseUrls">
          <div class="card-body">
            <p class="text-muted small mb-3"><?= __('security_authentication.oauth_urls_desc') ?></p>

            <div class="mb-3">
              <label for="authorization_url" class="form-label fw-semibold small">
                <?= __('security_authentication.authorization_url') ?>
              </label>
              <input type="url" name="authorization_url" id="authorization_url"
                class="form-control font-monospace small"
                value="<?= htmlspecialchars($old['authorization_url'] ?? $provider['authorization_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="mb-3">
              <label for="token_url" class="form-label fw-semibold small">
                <?= __('security_authentication.token_url') ?>
              </label>
              <input type="url" name="token_url" id="token_url"
                class="form-control font-monospace small"
                value="<?= htmlspecialchars($old['token_url'] ?? $provider['token_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="mb-0">
              <label for="userinfo_url" class="form-label fw-semibold small">
                <?= __('security_authentication.userinfo_url') ?>
              </label>
              <input type="url" name="userinfo_url" id="userinfo_url"
                class="form-control font-monospace small"
                value="<?= htmlspecialchars($old['userinfo_url'] ?? $provider['userinfo_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Estado -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch"
              id="isEnabled" name="is_enabled" value="1"
              <?= ($provider['is_enabled'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="isEnabled">
              <?= __('security_authentication.enable_provider') ?>
            </label>
          </div>
          <div class="form-text text-muted">
            <?= __('security_authentication.enable_provider_desc') ?>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="ph-duotone ph-floppy-disk me-1"></i>
          <?= __('security_authentication.save_provider') ?>
        </button>
        <a href="<?= BASE_URL ?>/security/authentication" class="btn btn-outline-secondary">
          <i class="ph-duotone ph-arrow-left me-1"></i>
          <?= __('security_authentication.back') ?>
        </a>
      </div>
    </form>
  </div>

  <!-- Panel lateral -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-shield-check me-2 text-success"></i>
          <?= __('security_authentication.security_notes') ?>
        </h6>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0 small text-muted">
          <li class="mb-2"><i class="ph-duotone ph-lock text-success me-1"></i><?= __('security_authentication.note_secret_encrypted') ?></li>
          <li class="mb-2"><i class="ph-duotone ph-eye-slash text-warning me-1"></i><?= __('security_authentication.note_secret_hidden') ?></li>
          <li class="mb-2"><i class="ph-duotone ph-check-circle text-info me-1"></i><?= __('security_authentication.note_redirect_uri') ?></li>
          <li class="mb-0"><i class="ph-duotone ph-info text-secondary me-1"></i><?= __('security_authentication.note_tenant_id') ?></li>
        </ul>
      </div>
    </div>

    <?php if (!empty($provider['last_tested_at'])): ?>
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-test-tube me-2 text-info"></i>
          <?= __('security_authentication.last_test') ?>
        </h6>
      </div>
      <div class="card-body">
        <p class="small mb-1">
          <strong><?= __('security_authentication.last_tested_at') ?>:</strong>
          <?= htmlspecialchars(date('d/m/Y H:i', strtotime($provider['last_tested_at'])), ENT_QUOTES, 'UTF-8') ?>
        </p>
        <p class="small mb-1">
          <strong><?= __('security_authentication.last_test_status') ?>:</strong>
          <span class="badge bg-<?= ($provider['last_test_status'] ?? '') === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($provider['last_test_status'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
          </span>
        </p>
        <?php if (!empty($provider['last_test_message'])): ?>
        <p class="small mb-0 text-muted">
          <?= htmlspecialchars($provider['last_test_message'], ENT_QUOTES, 'UTF-8') ?>
        </p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php
    $guideUrl = match ($slug) {
        'google'    => 'https://console.cloud.google.com/apis/credentials',
        'microsoft' => 'https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade',
        'github'    => 'https://github.com/settings/developers',
        default     => null,
    };
    $guideName = match ($slug) {
        'google'    => 'Google Cloud Console',
        'microsoft' => 'Azure App Registrations',
        'github'    => 'GitHub Developer Settings',
        default     => null,
    };
    ?>
    <?php if ($guideUrl): ?>
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="ph-duotone ph-arrow-square-out me-2 text-primary"></i>
          <?= __('security_authentication.provider_console') ?>
        </h6>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-2"><?= __('security_authentication.provider_console_desc') ?></p>
        <a href="<?= htmlspecialchars($guideUrl, ENT_QUOTES, 'UTF-8') ?>"
           target="_blank" rel="noopener noreferrer"
           class="btn btn-sm btn-outline-primary w-100">
          <i class="ph-duotone ph-arrow-square-out me-1"></i>
          <?= htmlspecialchars($guideName, ENT_QUOTES, 'UTF-8') ?>
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php $extraScript = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function () {
  var btnCopy  = document.getElementById('btnCopyUri');
  var uriInput = document.getElementById('callbackUriDisplay');

  var msgCopied = btnCopy ? (btnCopy.dataset.msgCopied || 'URI copiada correctamente.') : '';
  var msgFailed = btnCopy ? (btnCopy.dataset.msgFailed || 'No se pudo copiar la URI. Cópiala manualmente.') : '';

  function copyToClipboard(text, onSuccess, onError) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(onSuccess).catch(onError);
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0;';
      document.body.appendChild(ta);
      ta.focus();
      ta.select();
      var ok = false;
      try { ok = document.execCommand('copy'); } catch (err) {}
      document.body.removeChild(ta);
      if (ok) { onSuccess(); } else { onError(); }
    }
  }

  if (btnCopy && uriInput) {
    btnCopy.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var uri = uriInput.value.trim();
      if (!uri) { return; }
      copyToClipboard(uri,
        function () {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: msgCopied,
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
          });
        },
        function () {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: msgFailed,
            confirmButtonColor: '#4680ff'
          });
        }
      );
    });
  }

  var btnToggle   = document.getElementById('btnToggleSecret');
  var secretInput = document.getElementById('client_secret');
  var iconSecret  = document.getElementById('iconSecret');
  if (btnToggle && secretInput) {
    btnToggle.addEventListener('click', function () {
      var isPassword = secretInput.type === 'password';
      secretInput.type = isPassword ? 'text' : 'password';
      iconSecret.className = isPassword ? 'ph-duotone ph-eye-slash' : 'ph-duotone ph-eye';
    });
  }
});
</script>
JS;
?>

<?php require dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
