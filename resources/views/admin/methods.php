<p class="eyebrow">Payment configuration</p>
<h1><?= e(ucfirst($kind)) ?> methods</h1>

<?php if($kind==='deposit'): ?>
<section class="card">
  <p class="section-label">Simple setup</p>
  <h2>Create deposit method</h2>
  <p class="muted">Create the method first. ApexTrades will automatically create its form; then you can add the payment details and the fields users must complete.</p>
  <form method="post" action="<?= e(route('admin.methods.save',['kind'=>'deposit'])) ?>" class="form-grid">
    <?= app('csrf')->input() ?>
    <label>Name<input name="name" required placeholder="e.g. Bank Transfer"></label>
    <label>Method type
      <select name="method_type">
        <option value="BANK">Bank transfer</option>
        <option value="CRYPTO">Cryptocurrency</option>
        <option value="CUSTOM">Other / custom</option>
      </select>
    </label>
    <label style="grid-column:1/-1"><input type="checkbox" name="all_countries" value="1" checked> Available in all enabled Country Packs</label>
    <button class="button" type="submit">Create and continue</button>
  </form>
</section>

<section class="card">
  <h2>Deposit methods</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Processing</th><th></th></tr></thead>
      <tbody>
      <?php foreach($methods as $method): ?>
        <tr>
          <td><strong><?= e($method['name']) ?></strong></td>
          <td><?= e(ucfirst(strtolower($method['method_type']??'custom'))) ?></td>
          <td><?= $method['enabled']?'Enabled':'Disabled' ?></td>
          <td><?= e($method['estimated_processing_time']??'—') ?></td>
          <td><a class="button button-small button-secondary" href="<?= e(route('admin.methods.edit',['kind'=>'deposit','method'=>$method['id']])) ?>">Edit method</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php else: ?>
<section class="card">
  <h2>Create withdrawal method</h2>
  <form method="post" action="<?= e(route('admin.methods.save',['kind'=>$kind])) ?>" class="form-grid">
    <?= app('csrf')->input() ?>
    <label>Name<input name="name" required></label>
    <label>Slug<input name="slug" required></label>
    <label>Minimum minor units<input name="minimum_amount_minor" value="0" required></label>
    <label>Maximum minor units<input name="maximum_amount_minor"></label>
    <label>Fee type<select name="fee_type"><option>NONE</option><option>FIXED</option><option>PERCENTAGE</option></select></label>
    <label>Fee value<input name="fee_value" value="0"></label>
    <label>Processing time<input name="processing_time"></label>
    <label>Description<textarea name="description"></textarea></label>
    <label>Instructions<textarea name="instructions"></textarea></label>
    <fieldset><legend>Country Packs</legend><?php foreach($countries as $country): ?><label><input type="checkbox" name="countries[]" value="<?= e($country['id']) ?>"> <?= e($country['name']) ?></label><?php endforeach; ?></fieldset>
    <label><input type="checkbox" name="enabled" value="1"> Enabled</label>
    <button class="button">Create method</button>
  </form>
</section>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Limits</th><th>Fee</th><th>Enabled</th><th></th></tr></thead><tbody><?php foreach($methods as $method): ?><tr><td><?= e($method['name']) ?></td><td><?= e($method['minimum_amount_minor']) ?>–<?= e($method['maximum_amount_minor']??'—') ?></td><td><?= e($method['fee_type']) ?> <?= e($method['fee_value']) ?></td><td><?= $method['enabled']?'Yes':'No' ?></td><td><a href="<?= e(route('admin.methods.edit',['kind'=>$kind,'method'=>$method['id']])) ?>">Edit</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?>
