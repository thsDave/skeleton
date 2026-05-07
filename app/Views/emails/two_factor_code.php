<?php
// Variables expected in scope: $nombre (string), $code (string), $expiry (int minutes)
?>
<div style="font-family:sans-serif;max-width:520px;margin:auto;padding:24px;background:#ffffff;border-radius:8px;">

  <div style="text-align:center;margin-bottom:24px;">
    <div style="display:inline-flex;align-items:center;gap:10px;">
      <div style="background:#0d6efd;border-radius:8px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
        <span style="color:#fff;font-size:20px;">🛡</span>
      </div>
      <strong style="font-size:20px;color:#0d6efd;">Skeleton</strong>
    </div>
  </div>

  <h2 style="color:#1a1a2e;font-size:18px;margin-bottom:6px;"><?= __('2fa.email_subject') ?></h2>
  <p style="color:#444;margin:0 0 12px;">Hola <strong><?= htmlspecialchars($nombre ?? '') ?></strong>,</p>
  <p style="color:#444;margin:0 0 20px;"><?= __('2fa.email_intro') ?></p>

  <div style="text-align:center;margin:24px 0;padding:20px;background:#f0f4ff;border-radius:10px;border:2px dashed #0d6efd;">
    <span style="font-size:40px;font-weight:800;letter-spacing:12px;color:#0d6efd;"><?= htmlspecialchars($code ?? '') ?></span>
  </div>

  <p style="color:#666;font-size:13px;margin:0 0 8px;"><?= __('2fa.email_expiry', ['minutes' => $expiry ?? 10]) ?></p>
  <p style="color:#999;font-size:12px;"><?= __('2fa.email_no_request') ?></p>

  <hr style="border:none;border-top:1px solid #eee;margin:20px 0;" />
  <p style="color:#bbb;font-size:11px;margin:0;"><?= __('2fa.email_footer') ?></p>
</div>
