<?php
// Variables expected in scope: $userName, $code, $expiresInMinutes, $appName
?>
<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:24px;background:#ffffff;border-radius:8px;color:#333;">
  <h2 style="color:#1a1a2e;font-size:20px;margin:0 0 16px;">
    <?= htmlspecialchars(__('mail.email_change_subject'), ENT_QUOTES, 'UTF-8') ?>
  </h2>

  <p style="margin:0 0 12px;line-height:1.6;">
    <?= __('mail.email_change_greeting') ?>,
    <strong><?= htmlspecialchars($userName ?? '', ENT_QUOTES, 'UTF-8') ?></strong>.
  </p>

  <p style="margin:0 0 18px;line-height:1.6;">
    <?= __('mail.email_change_line_1') ?>
  </p>

  <div style="text-align:center;margin:24px 0;padding:20px;background:#f0f4ff;border-radius:8px;border:2px dashed #4680ff;">
    <span style="font-size:34px;font-weight:800;letter-spacing:10px;color:#4680ff;">
      <?= htmlspecialchars($code ?? '', ENT_QUOTES, 'UTF-8') ?>
    </span>
  </div>

  <p style="margin:0 0 12px;line-height:1.6;">
    <?= __('mail.email_change_expiration', ['minutes' => (string) ($expiresInMinutes ?? 10)]) ?>
  </p>

  <p style="color:#777;font-size:13px;margin:18px 0 0;line-height:1.5;">
    <?= __('mail.email_change_ignore') ?>
  </p>

  <hr style="border:none;border-top:1px solid #eee;margin:22px 0;" />
  <p style="color:#999;font-size:12px;margin:0;">
    <?= htmlspecialchars($appName ?? 'Skeleton', ENT_QUOTES, 'UTF-8') ?>
  </p>
</div>
