<?php declare(strict_types=1); ?>
<div style="max-width:620px;margin:0 auto;padding:28px 22px">
  <p style="margin:0 0 18px">Hello <?= e($name ?? 'there') ?>,</p>
  <h2 style="margin:0 0 14px;font-size:24px"><?= e($heading ?? 'Account update') ?></h2>
  <?php if(!empty($message)): ?><p style="margin:0 0 22px"><?= nl2br(e($message)) ?></p><?php endif; ?>
  <?php if(!empty($url)): ?>
    <p style="margin:24px 0"><a href="<?= e($url) ?>" style="display:inline-block;padding:12px 20px;border-radius:999px;background:#1258D5;color:#fff;text-decoration:none;font-weight:700">View in ApexTrades</a></p>
  <?php endif; ?>
  <p style="margin-top:28px;color:#64748B;font-size:13px">This is an automated account notification from ApexTrades.</p>
</div>
