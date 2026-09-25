<p class="eyebrow">Wallet</p>
<h1>Complete your deposit</h1>
<p class="muted">Pay using the details below, then submit the requested payment reference or proof. Your deposit is sent to admin only after this step.</p>

<section class="card">
  <p class="section-label">Step 2 of 2 · <?= e($method['name']) ?></p>
  <div class="metric-grid">
    <article class="metric-card"><span>Amount requested</span><strong><?= money((int)$quote['required_payment_minor']) ?></strong></article>
    <?php if((int)$quote['fee_minor']>0): ?><article class="metric-card"><span>Fee</span><strong><?= money((int)$quote['fee_minor']) ?></strong></article><?php endif; ?>
    <article class="metric-card"><span>Expected wallet credit</span><strong><?= money((int)$quote['expected_credit_minor']) ?></strong></article>
  </div>
</section>

<section class="card">
  <h2>Payment details</h2>
  <p><?= nl2br(e($method['instructions']??'')) ?></p>

  <?php if(($method['method_type']??'')==='BANK'): ?>
    <div class="card" style="margin-top:18px">
      <p class="section-label">International bank transfer</p>
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
            <dd><span><?= e($paymentDetails[$key]) ?></span> <button type="button" class="text-button" data-copy-value="<?= e($paymentDetails[$key]) ?>">Copy</button></dd>
          <?php endif; ?>
        <?php endforeach; ?>
      </dl>
      <p class="alert alert-warning">Send the exact required amount. Your bank or intermediary bank may charge separate transfer fees.</p>
    </div>
  <?php elseif(($method['method_type']??'')==='CRYPTO'): ?>
    <div class="card" style="margin-top:18px">
      <p class="section-label">Crypto payment</p>
      <h3><?= e(($paymentDetails['asset']??'Crypto').' · '.($paymentDetails['network']??'Network')) ?></h3>
      <?php if($methodQr): ?><img class="qr-image" src="<?= e($methodQr) ?>" alt="<?= e(($paymentDetails['asset']??'Crypto').' wallet QR code') ?>"><?php endif; ?>
      <?php if(!empty($paymentDetails['wallet_address'])): ?>
        <p><strong>Wallet address</strong></p>
        <code class="secret"><?= e($paymentDetails['wallet_address']) ?></code>
        <p><button type="button" class="button button-secondary button-small" data-copy-value="<?= e($paymentDetails['wallet_address']) ?>">Copy wallet address</button></p>
      <?php endif; ?>
      <p class="alert alert-warning">Send only <?= e($paymentDetails['asset']??'the displayed asset') ?> using <?= e($paymentDetails['network']??'the displayed network') ?>. A different asset or network can result in permanent loss.</p>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Confirm your payment</h2>
  <p class="muted">Complete the fields below only after you have made the payment.</p>
  <form method="post" action="<?= e(route('dashboard.deposit.submit')) ?>" class="form-stack" enctype="multipart/form-data">
    <?= app('csrf')->input() ?>
    <input type="hidden" name="method_id" value="<?= e($method['id']) ?>">
    <input type="hidden" name="amount" value="<?= e($amountInput) ?>">
    <?php require __DIR__.'/../components/dynamic-form.php'; ?>
    <div class="button-row">
      <a class="button button-secondary" href="<?= e(route('dashboard.deposit')) ?>?method=<?= e($method['id']) ?>">Change amount</a>
      <button class="button" type="submit">I have paid — submit for approval</button>
    </div>
  </form>
</section>
