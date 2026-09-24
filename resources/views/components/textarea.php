<?php /** @var string $label @var string $name */ ?>
<label class="field"><span><?= e($label) ?></span><textarea name="<?= e($name) ?>" rows="<?= e($rows ?? 4) ?>"><?= e($value ?? '') ?></textarea></label>
