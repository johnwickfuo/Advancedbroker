<?php declare(strict_types=1); ?>
<p>Hello <?= e($name ?? 'there') ?>,</p>
<div style="white-space:normal"><?= nl2br(e($body ?? '')) ?></div>
<p style="margin-top:24px">Regards,<br><strong><?= e(config('app.name')) ?></strong></p>
