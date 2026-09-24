<?php declare(strict_types=1); ?>
<p><?= e($greeting ?? 'Hello') ?></p>
<p><?= e($message ?? '') ?></p>
<p><a href="<?= e($url ?? '') ?>"><?= e($action ?? 'Continue') ?></a></p>
<p>If you did not request this, you can safely ignore this message.</p>
