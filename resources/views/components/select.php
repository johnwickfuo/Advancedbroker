<?php /** @var string $label @var string $name @var array $options */ ?>
<label class="field"><span><?= e($label) ?></span><select name="<?= e($name) ?>"><?php foreach ($options as $value => $labelText): ?><option value="<?= e($value) ?>"><?= e($labelText) ?></option><?php endforeach; ?></select></label>
