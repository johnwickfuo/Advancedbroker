<?php /** @var string $label @var string $name @var string $type @var string|null $value */ ?>
<label class="field"><span><?= e($label) ?></span><input type="<?= e($type ?? 'text') ?>" name="<?= e($name) ?>" value="<?= e($value ?? '') ?>"<?= !empty($required) ? ' required' : '' ?>></label>
