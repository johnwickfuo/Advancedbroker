<p class="eyebrow">Wallet</p>
<h1>Deposit funds</h1>
<p>Use a method available to your investment region. Deposits are reviewed manually before your wallet is credited.</p>

<div class="metric-grid">
  <article class="metric-card"><span>Available cash</span><strong><?= money((int)$wallet['available_balance_minor']) ?></strong></article>
</div>

<div class="card-grid">
<?php foreach($methods as $item): ?>
  <a class="card" href="<?= e(route('dashboard.deposit')) ?>?method=<?= e($item['id']) ?>">
    <h2><?= e($item['name']) ?></h2>
    <p><?= e($item['description']??'Manual deposit') ?></p>
    <small><?= e($item['estimated_processing_time']??'Review time shown after submission') ?></small>
  </a>
<?php endforeach; ?>
</div>

<?php if($method): ?>
<section class="card">
  <h2><?= e($method['name']) ?></h2>
  <p><?= nl2br(e($method['instructions']??'')) ?></p>

  <?php if(($method['method_type']??'')==='BANK'): ?>
    <div class="card" style="margin:18px 0">
      <p class="section-label">International beneficiary details</p>
      <dl class="detail-list">
        <?php foreach([
          'beneficiary_name'=>'Beneficiary name',
          'bank_name'=>'Bank name',
          'account_iban'=>'Account number / IBAN',
          'swift_bic'=>'SWIFT / BIC',
          'routing_aba'=>'Routing / ABA',
          'bank_address'=>'Bank address',
          'beneficiary_address'=>'Beneficiary address',
        ] as $key=>$label): ?>
          <?php if(!empty($paymentDetails[$key])): ?>
            <dt><?= e($label) ?></dt>
            <dd>
              <span><?= e($paymentDetails[$key]) ?></span>
              <button type="button" class="text-button" data-copy-value="<?= e($paymentDetails[$key]) ?>">Copy</button>
            </dd>
          <?php endif; ?>
        <?php endforeach; ?>
      </dl>
      <p class="muted">Your bank may charge international or intermediary fees. Make sure the beneficiary receives the intended amount.</p>
    </div>
  <?php elseif(($method['method_type']??'')==='CRYPTO'): ?>
    <div class="card" style="margin:18px 0">
      <p class="section-label">Crypto destination</p>
      <h3><?= e(($paymentDetails['asset']??'Crypto').' · '.($paymentDetails['network']??'Network')) ?></h3>
      <?php if($methodQr): ?>
        <img class="qr-image" src="<?= e($methodQr) ?>" alt="<?= e(($paymentDetails['asset']??'Crypto').' deposit wallet QR code') ?>">
      <?php endif; ?>
      <?php if(!empty($paymentDetails['wallet_address'])): ?>
        <p><strong>Wallet address</strong></p>
        <code class="secret"><?= e($paymentDetails['wallet_address']) ?></code>
        <p><button type="button" class="button button-secondary button-small" data-copy-value="<?= e($paymentDetails['wallet_address']) ?>">Copy wallet address</button></p>
      <?php endif; ?>
      <p class="alert alert-warning">Send only <?= e($paymentDetails['asset']??'the selected asset') ?> on <?= e($paymentDetails['network']??'the displayed network') ?>. Using a different asset or network can cause permanent loss.</p>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= e(route('dashboard.deposit.submit')) ?>" class="form-stack" enctype="multipart/form-data">
    <?= app('csrf')->input() ?>
    <input type="hidden" name="method_id" value="<?= e($method['id']) ?>">
    <label class="field">
      <span>Amount (<?= e(country()->currencyCode()) ?>)</span>
      <input name="amount" inputmode="decimal" required>
    </label>
    <?php require __DIR__.'/../components/dynamic-form.php'; ?>
    <button class="button">Review and submit deposit</button>
  </form>
</section>
<?php elseif(!$methods): ?>
<section class="empty-state"><h2>No deposit methods available</h2><p>Please contact support for assistance.</p></section>
<?php endif; ?>
