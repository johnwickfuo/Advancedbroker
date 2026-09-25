<?php $fmt=static fn(int $minor)=>country_money(new \App\Support\Money($minor,country()->currencyCode())); ?>
<div class="dashboard-page-head"><div><p class="eyebrow">AI trading</p><h1>Your AI trading codes</h1><p class="muted">Purchase codes, activate them when you are ready, and track timed progress from your dashboard.</p></div><a class="button" href="<?= e(route('ai-trading.catalogue')) ?>">Buy a code</a></div>
<div class="metric-grid"><article class="metric-card"><span>Available cash</span><strong><?= e($fmt((int)$wallet['available_balance_minor'])) ?></strong></article><article class="metric-card"><span>Total codes owned</span><strong><?= e(count($trades)) ?></strong></article><article class="metric-card"><span>Active cycles</span><strong><?= e(count(array_filter($trades,fn($t)=>$t['status']==='ACTIVE'))) ?></strong></article><article class="metric-card"><span>Completed cycles</span><strong><?= e(count(array_filter($trades,fn($t)=>$t['status']==='COMPLETED'))) ?></strong></article></div>
<?php if(!$trades): ?><div class="empty-state"><h2>No AI trading codes yet</h2><p>Choose a category to receive your first unique activation code.</p><a class="button" href="<?= e(route('ai-trading.catalogue')) ?>">Explore AI trading codes</a></div><?php else: ?>
<div class="ai-owned-grid">
<?php foreach($trades as $trade): ?>
  <article class="card ai-owned-card">
    <div class="ai-owned-head"><span class="badge"><?= e($trade['status']) ?></span><span><?= e($trade['category_name']) ?></span></div>
    <h2><?= e($trade['category_name']) ?></h2>
    <div class="ai-code-box notranslate" translate="no"><span>Activation code</span><code><?= e($trade['activation_code']) ?></code><button type="button" class="text-button" data-copy-value="<?= e($trade['activation_code']) ?>">Copy</button></div>
    <div class="ai-progress-meta"><span><?= e(number_format((float)$trade['progress_percent'],1)) ?>%</span><strong><?= $trade['status']==='PURCHASED'?'Waiting for activation':($trade['status']==='COMPLETED'?'Completed':'AI trading in progress') ?></strong></div>
    <div class="ai-progress"><i style="width:<?= e(min(100,max(0,(float)$trade['progress_percent']))) ?>%"></i></div>
    <dl class="detail-list"><dt>Purchase</dt><dd><?= e($fmt((int)$trade['purchase_amount_minor'])) ?></dd><dt>Fixed profit</dt><dd><?= e($fmt((int)$trade['profit_amount_minor'])) ?></dd><dt>Total completion payout</dt><dd><?= e($fmt((int)$trade['maturity_payout_minor'])) ?></dd><dt>Duration</dt><dd><?= e($trade['duration_value_snapshot'].' '.strtolower($trade['duration_unit_snapshot'])) ?></dd></dl>
    <a class="button button-secondary" href="<?= e(route('dashboard.ai-trading.show',['purchase'=>$trade['public_id']])) ?>"><?= $trade['status']==='PURCHASED'?'Activate code':'View details' ?></a>
  </article>
<?php endforeach; ?>
</div>
<?php endif; ?>
