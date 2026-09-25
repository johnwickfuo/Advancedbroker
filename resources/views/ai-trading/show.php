<?php $fmt=static fn(int $minor)=>country_money(new \App\Support\Money($minor,country()->currencyCode())); ?>
<div class="dashboard-page-head"><div><p class="eyebrow">AI trading code</p><h1><?= e($trade['category_name']) ?></h1><p class="muted">Reference <?= e($trade['reference']) ?></p></div><a class="button button-secondary" href="<?= e(route('dashboard.ai-trading')) ?>">All AI trades</a></div>
<div class="grid two dashboard-split">
<section class="card ai-code-detail">
  <p class="section-label">Your unique code</p>
  <div class="ai-code-box large notranslate" translate="no"><span>Activation code</span><code><?= e($trade['activation_code']) ?></code><button type="button" class="text-button" data-copy-value="<?= e($trade['activation_code']) ?>">Copy code</button></div>
  <?php if($trade['status']==='PURCHASED'): ?>
  <h2>Activate when you are ready</h2>
  <p>The duration does not start until this exact code is entered and successfully activated.</p>
  <form method="post" action="<?= e(route('dashboard.ai-trading.activate',['purchase'=>$trade['public_id']])) ?>" class="form-stack">
    <?= app('csrf')->input() ?>
    <label class="field">Enter AI trading code<input class="notranslate" translate="no" name="code" autocomplete="off" required placeholder="AI-XXXX-XXXX-XXXX"></label>
    <button class="button" type="submit">Activate AI trading</button>
  </form>
  <?php else: ?>
  <div class="ai-progress-meta"><span><?= e(number_format((float)$trade['progress_percent'],1)) ?>%</span><strong><?= $trade['status']==='COMPLETED'?'Completed':'AI trading in progress' ?></strong></div>
  <div class="ai-progress large"><i style="width:<?= e(min(100,max(0,(float)$trade['progress_percent']))) ?>%"></i></div>
  <?php endif; ?>
</section>
<section class="card">
  <p class="section-label">Fixed terms</p><h2>Trade summary</h2>
  <dl class="detail-list"><dt>Status</dt><dd><span class="badge"><?= e($trade['status']) ?></span></dd><dt>Purchase amount</dt><dd><?= e($fmt((int)$trade['purchase_amount_minor'])) ?></dd><dt>Fixed profit</dt><dd><?= e($fmt((int)$trade['profit_amount_minor'])) ?> (<?= e(rtrim(rtrim((string)$trade['profit_percent_snapshot'],'0'),'.')) ?>%)</dd><dt>Total payout</dt><dd><?= e($fmt((int)$trade['maturity_payout_minor'])) ?></dd><dt>FX rate snapshot</dt><dd>1 USD = <?= e(rtrim(rtrim((string)$trade['fx_rate_snapshot'],'0'),'.')) ?> <?= e($trade['currency_code']) ?></dd><dt>FX snapshot date</dt><dd><?= e($trade['fx_snapshot_date']) ?></dd><dt>Purchased</dt><dd><?= e($trade['purchased_at']) ?></dd><dt>Activated</dt><dd><?= e($trade['activated_at']?:'Not yet') ?></dd><dt>Scheduled completion</dt><dd><?= e($trade['matures_at']?:'Begins after activation') ?></dd></dl>
</section>
</div>
