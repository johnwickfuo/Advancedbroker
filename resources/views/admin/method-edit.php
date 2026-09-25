<p class="eyebrow">Payment configuration</p>
<div class="page-heading">
  <div><h1>Edit <?= e($method['name']) ?></h1><p class="muted">Manage the method, payment details and the fields users complete from one page.</p></div>
  <a class="button button-secondary" href="<?= e(route('admin.methods',['kind'=>$kind])) ?>">Back to methods</a>
</div>

<section class="card">
  <h2>Method details</h2>
  <form method="post" action="<?= e(route('admin.methods.update',['kind'=>$kind,'method'=>$method['id']])) ?>" class="form-grid">
    <?= app('csrf')->input() ?>
    <label>Name<input name="name" value="<?= e($method['name']) ?>" required></label>

    <?php if($kind==='deposit'): ?>
    <label>Method type
      <select name="method_type">
        <?php foreach(['BANK'=>'Bank transfer','CRYPTO'=>'Cryptocurrency','CUSTOM'=>'Other / custom'] as $value=>$label): ?>
          <option value="<?= e($value) ?>"<?= ($method['method_type']??'CUSTOM')===$value?' selected':'' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php endif; ?>

    <label>Processing time<input name="processing_time" value="<?= e($method['estimated_processing_time']??'') ?>" placeholder="e.g. 1–3 business days"></label>
    <label style="grid-column:1/-1">Description<textarea name="description" rows="3"><?= e($method['description']??'') ?></textarea></label>
    <label style="grid-column:1/-1">User instructions<textarea name="instructions" rows="5"><?= e($method['instructions']??'') ?></textarea></label>

    <?php if($kind==='deposit' && ($method['method_type']??'CUSTOM')==='BANK'): ?>
    <div style="grid-column:1/-1"><p class="section-label">International bank details</p><p class="muted">These details are shown to users exactly as the destination for their transfer.</p></div>
    <label>Beneficiary name<input name="beneficiary_name" value="<?= e($details['beneficiary_name']??'') ?>"></label>
    <label>Bank name<input name="bank_name" value="<?= e($details['bank_name']??'') ?>"></label>
    <label>Account number / IBAN<input name="account_iban" value="<?= e($details['account_iban']??'') ?>"></label>
    <label>SWIFT / BIC<input name="swift_bic" value="<?= e($details['swift_bic']??'') ?>"></label>
    <label>Routing / ABA <span class="muted">(optional)</span><input name="routing_aba" value="<?= e($details['routing_aba']??'') ?>"></label>
    <label>Bank address<input name="bank_address" value="<?= e($details['bank_address']??'') ?>"></label>
    <label style="grid-column:1/-1">Beneficiary address <span class="muted">(optional)</span><input name="beneficiary_address" value="<?= e($details['beneficiary_address']??'') ?>"></label>
    <?php elseif($kind==='deposit' && ($method['method_type']??'CUSTOM')==='CRYPTO'): ?>
    <div style="grid-column:1/-1"><p class="section-label">Crypto destination</p><p class="muted">Save the wallet address and ApexTrades automatically generates its QR code.</p></div>
    <label>Asset<input name="asset" value="<?= e($details['asset']??'') ?>" placeholder="BTC or USDT"></label>
    <label>Network<input name="network" value="<?= e($details['network']??'') ?>" placeholder="Bitcoin Mainnet, TRC20, ERC20..."></label>
    <label style="grid-column:1/-1">Wallet address<input name="wallet_address" value="<?= e($details['wallet_address']??'') ?>" autocomplete="off"></label>
    <?php if(!empty($details['wallet_address'])): ?>
      <div class="card" style="grid-column:1/-1">
        <h3>Generated wallet QR code</h3>
        <?php if($qr): ?>
          <img class="qr-image" src="<?= e($qr) ?>" alt="<?= e(($details['asset']??'Crypto').' deposit wallet QR code') ?>">
          <p><code><?= e($details['wallet_address']) ?></code></p>
        <?php else: ?>
          <p class="alert alert-warning">The address is saved, but QR rendering is unavailable on this server. Install PHP GD and phpqrcode to enable local QR generation.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if($kind==='deposit'): ?>
    <fieldset style="grid-column:1/-1">
      <legend>Availability</legend>
      <?php $allAssigned=count($assignedCountries)>=count($countries); ?>
      <label><input type="checkbox" name="all_countries" value="1"<?= $allAssigned?' checked':'' ?>> Available in all enabled Country Packs</label>
      <details>
        <summary>Choose specific countries instead</summary>
        <div class="form-grid" style="margin-top:12px">
          <?php foreach($countries as $country): ?>
            <label><input type="checkbox" name="countries[]" value="<?= e($country['id']) ?>"<?= in_array((int)$country['id'],$assignedCountries,true)?' checked':'' ?>> <?= e($country['name']) ?></label>
          <?php endforeach; ?>
        </div>
      </details>
    </fieldset>

    <details style="grid-column:1/-1">
      <summary>Advanced limits and fees</summary>
      <div class="form-grid" style="margin-top:14px">
        <label>Minimum amount in minor units<input type="number" min="0" name="minimum_amount_minor" value="<?= e($method['minimum_amount_minor']??0) ?>"></label>
        <label>Maximum amount in minor units<input type="number" min="0" name="maximum_amount_minor" value="<?= e($method['maximum_amount_minor']??'') ?>"></label>
        <label>Fee type<select name="fee_type"><?php foreach(['NONE','FIXED','PERCENTAGE'] as $fee): ?><option value="<?= e($fee) ?>"<?= ($method['fee_type']??'NONE')===$fee?' selected':'' ?>><?= e($fee) ?></option><?php endforeach; ?></select></label>
        <label>Fee value<input name="fee_value" value="<?= e($method['fee_value']??'0') ?>"></label>
      </div>
    </details>
    <?php endif; ?>

    <label style="grid-column:1/-1"><input type="checkbox" name="enabled" value="1"<?= !empty($method['enabled'])?' checked':'' ?>> Enabled and visible to users</label>
    <button class="button" type="submit">Save method</button>
  </form>
</section>

<?php if(!empty($method['form_id'])): ?>
<section class="card">
  <p class="section-label">User deposit form</p>
  <h2>Fields users must complete</h2>
  <p class="muted">No separate form builder is needed. Add the fields for this payment method here.</p>

  <?php if($fields): ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Field</th><th>Type</th><th>Required</th><th></th></tr></thead>
      <tbody>
      <?php foreach($fields as $field): ?>
        <tr>
          <td><strong><?= e($field['label']) ?></strong><?php if($field['help_text']): ?><br><small><?= e($field['help_text']) ?></small><?php endif; ?></td>
          <td><?= e($field['field_type']) ?></td>
          <td><?= $field['is_required']?'Yes':'No' ?></td>
          <td>
            <form method="post" action="<?= e(route('admin.methods.fields.delete',['kind'=>$kind,'method'=>$method['id'],'field'=>$field['id']])) ?>" data-confirm="Remove this field from the deposit form?">
              <?= app('csrf')->input() ?>
              <button class="button button-small button-danger" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <h3 style="margin-top:24px">Add field</h3>
  <form method="post" action="<?= e(route('admin.methods.fields.add',['kind'=>$kind,'method'=>$method['id']])) ?>" class="form-grid">
    <?= app('csrf')->input() ?>
    <label>Field label<input name="label" required placeholder="e.g. Transaction hash"></label>
    <label>Field type
      <select name="field_type">
        <option value="TEXT">Short text</option>
        <option value="TEXTAREA">Long text</option>
        <option value="FILE">File / receipt upload</option>
        <option value="NUMBER">Number</option>
        <option value="EMAIL">Email</option>
        <option value="PHONE">Phone</option>
        <option value="DATE">Date</option>
      </select>
    </label>
    <label>Placeholder <span class="muted">(optional)</span><input name="placeholder"></label>
    <label>Help text <span class="muted">(optional)</span><input name="help_text"></label>
    <label><input type="checkbox" name="required" value="1"> Required</label>
    <button class="button" type="submit">Add field</button>
  </form>
</section>
<?php endif; ?>
