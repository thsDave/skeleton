<?php
$pageTitle  = __('profile.title');
$activeMenu = 'profile';
require dirname(__DIR__) . '/layouts/main.php';
?>

<div class="page-header">
  <div class="page-block">
    <div class="row align-items-center g-0">
      <div class="col-sm-auto">
        <div class="page-header-title">
          <h5 class="mb-0"><?= __('profile.title') ?></h5>
        </div>
      </div>
      <div class="col-sm-auto ms-auto">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard"><?= __('common.home') ?></a></li>
            <li class="breadcrumb-item active"><?= __('profile.title') ?></li>
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
$_prfStatus = $user['status_slug'] ?? '';
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
          <?= $_prfStatus === 'active' ? __('common.active') : ucfirst($user['status_name'] ?? $_prfStatus) ?>
        </span>
        <div class="d-grid">
          <a href="<?= BASE_URL ?>/profile/edit" class="btn btn-primary btn-sm">
            <i class="ph-duotone ph-pencil me-1"></i> <?= __('profile.edit_profile') ?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Información personal -->
  <div class="col-lg-8 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><?= __('profile.personal_info') ?></h5>
      </div>
      <div class="card-body">
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted"><?= __('profile.first_names') ?></p></div>
          <div class="col-sm-8"><p class="mb-0 fw-semibold"><?= htmlspecialchars($user['nombres'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p></div>
        </div>
        <hr class="my-2" />
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted"><?= __('profile.last_names') ?></p></div>
          <div class="col-sm-8"><p class="mb-0 fw-semibold"><?= htmlspecialchars($user['apellidos'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p></div>
        </div>
        <hr class="my-2" />
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted"><?= __('profile.phone') ?></p></div>
          <div class="col-sm-8">
            <p class="mb-0 fw-semibold">
              <?= $user['telefono'] ? htmlspecialchars($user['telefono'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fst-italic">' . __('profile.not_specified') . '</span>' ?>
            </p>
          </div>
        </div>
        <hr class="my-2" />
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted"><?= __('profile.address') ?></p></div>
          <div class="col-sm-8">
            <p class="mb-0 fw-semibold">
              <?= $user['direccion'] ? htmlspecialchars($user['direccion'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fst-italic">' . __('profile.not_specified_f') . '</span>' ?>
            </p>
          </div>
        </div>
        <hr class="my-2" />
        <div class="row py-2">
          <div class="col-sm-4"><p class="mb-0 text-muted"><?= __('profile.member_since') ?></p></div>
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

<!-- Preferencias -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="ph-duotone ph-sliders me-2 text-primary"></i><?= __('profile.preferences') ?></h5>
      </div>
      <div class="card-body">
        <form action="<?= BASE_URL ?>/profile/preferences" method="POST" novalidate>
          <?= \Core\CSRF::field() ?>
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <label class="form-label fw-semibold"><?= __('profile.theme') ?></label>
              <select name="theme_preference" class="form-select">
                <option value="light" <?= ($user['theme_preference'] ?? 'light') === 'light' ? 'selected' : '' ?>>
                  ☀️ <?= __('theme.light') ?>
                </option>
                <option value="dark" <?= ($user['theme_preference'] ?? 'light') === 'dark' ? 'selected' : '' ?>>
                  🌙 <?= __('theme.dark') ?>
                </option>
                <option value="default" <?= ($user['theme_preference'] ?? 'light') === 'default' ? 'selected' : '' ?>>
                  ◑ <?= __('theme.default') ?>
                </option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold"><?= __('profile.language') ?></label>
              <select name="language_id" class="form-select select2">
                <option value=""><?= __('profile.language_default') ?></option>
                <?php foreach ($languages as $lang): ?>
                <option value="<?= $lang['id'] ?>" <?= ($user['language_id'] ?? null) == $lang['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8') ?>
                  <?php if ($lang['native_name']): ?>(<?= htmlspecialchars($lang['native_name'], ENT_QUOTES, 'UTF-8') ?>)<?php endif; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <button type="submit" class="btn btn-primary w-100">
                <i class="ph-duotone ph-floppy-disk me-1"></i> <?= __('buttons.save_preferences') ?>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Verificación en 2 pasos -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex align-items-center gap-3">
        <?php $tf_on = !empty($user['two_factor_enabled']); ?>
        <div class="avtar avtar-s bg-light-<?= $tf_on ? 'success' : 'secondary' ?>">
          <i class="ph-duotone ph-shield-<?= $tf_on ? 'check' : 'warning' ?> text-<?= $tf_on ? 'success' : 'secondary' ?>" style="font-size:1.4rem;"></i>
        </div>
        <div>
          <h6 class="mb-0"><?= __('2fa.title') ?></h6>
          <small class="text-muted">
            <?php if ($tf_on): ?>
              <span class="text-success"><?= __('2fa.status_enabled') ?></span>
              &mdash; <?= __("2fa.method_{$user['two_factor_method']}") ?>
            <?php else: ?>
              <?= __('2fa.status_disabled') ?>
            <?php endif; ?>
          </small>
        </div>
        <a href="<?= BASE_URL ?>/profile/two-factor" class="btn btn-sm btn-outline-primary ms-auto">
          <i class="ph-duotone ph-shield-check me-1"></i><?= $tf_on ? __('2fa.disable') : __('2fa.title') ?>
        </a>
      </div>
    </div>
  </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
