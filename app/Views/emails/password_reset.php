<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars(__('mail.password_reset_subject'), ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f4f6fb; margin: 0; padding: 0; color: #333; }
    .wrapper { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .header { background-color: #4680ff; padding: 28px 32px; text-align: center; }
    .header h1 { color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; }
    .body { padding: 32px; }
    .body p { margin: 0 0 16px; line-height: 1.6; font-size: 15px; }
    .btn-wrapper { text-align: center; margin: 28px 0; }
    .btn { display: inline-block; background-color: #4680ff; color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: 700; font-size: 16px; }
    .url-fallback { word-break: break-all; font-size: 13px; color: #666; margin-top: 8px; }
    .footer { background-color: #f4f6fb; padding: 20px 32px; text-align: center; font-size: 13px; color: #888; }
    .expiry { background-color: #fff8e1; border-left: 4px solid #f6a609; padding: 12px 16px; border-radius: 4px; font-size: 14px; margin: 20px 0; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>🔐 <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></h1>
  </div>
  <div class="body">
    <p><?= __('mail.password_reset_greeting') ?>, <strong><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></strong>.</p>
    <p><?= __('mail.password_reset_line_1') ?></p>

    <div class="btn-wrapper">
      <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn">
        🔑 <?= __('mail.password_reset_button') ?>
      </a>
    </div>

    <div class="expiry">
      ⏱ <?= str_replace(':minutes', $expiresInMinutes, __('mail.password_reset_expiration')) ?>
    </div>

    <p class="url-fallback">
      <?= __('mail.password_reset_plain_url') ?>:<br>
      <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?></a>
    </p>

    <p style="color:#888;font-size:13px;margin-top:24px;">
      <?= __('mail.password_reset_ignore') ?>
    </p>
  </div>
  <div class="footer">
    <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> &mdash; <?= __('app.footer') ?>
  </div>
</div>
</body>
</html>
