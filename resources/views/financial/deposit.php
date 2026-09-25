<p class="eyebrow">Wallet</p>
<h1>Deposit funds</h1>
<p>Choose how you want to fund your account, then enter the amount. Payment instructions are shown on the next page.</p>

<div class="metric-grid">
  <article class="metric-card"><span>Available cash</span><strong><?= money((int)$wallet['available_balance_minor']) ?></strong></article>
</div>

<div class="card-grid">
<?php foreach($methods as $item): ?>
  <a class="card<?= $method&&(int)$method['id']===(int)$item['id']?' selected-card':'' ?>" href="<?= e(route('dashboard.deposit')) ?>?method=<?= e($item['id']) ?>">
    <h2><?= e($item['name']) ?></h2>
    <p><?= e($item['description']??'Manual deposit') ?></p>
    <small><?= e($item['estimated_processing_time']??'Manual review') ?></small>
  </a>
<?php endforeach; ?>
</div>

<?php if($method): ?>
<section class="card form-card">
  <p class="section-label">Step 1 of 2</p>
  <h2><?= e($method['name']) ?></h2>
  <p class="muted">Enter the amount you want to deposit. You will see the destination payment details before anything is submitted to ApexTrades.</p>
  <form method="post" action="<?= e(route('dashboard.deposit.review')) ?>" class="form-stack">
    <?= app('csrf')->input() ?>
    <input type="hidden" name="method_id" value="<?= e($method['id']) ?>">
    <label class="field">
      <span>Deposit amount (<?= e(country()->currencyCode()) ?>)</span>
      <input name="amount" inputmode="decimal" required autofocus>
    </label>
    <button class="button" type="submit">Continue to payment details</button>
  </form>
</section>
<?php elseif(!$methods): ?>
<section class="empty-state"><h2>No deposit methods available</h2><p>Please contact support for assistance.</p></section>
<?php else: ?>
<section class="card"><p>Select one of the deposit methods above to continue.</p></section>
<?php endif; ?>
