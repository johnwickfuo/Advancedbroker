<?php
$fmt=static fn(int $minor)=>country_money(new \App\Support\Money($minor,country()->currencyCode()));
?>
<div class="dashboard-hero-row">
  <div><p class="eyebrow">Portfolio</p><h1>Good morning, <?= e($user['first_name']??'Investor') ?>.</h1><p>Manage cash, investments and active trading positions from one place.</p></div>
  <div class="dashboard-balance-block"><span>Available cash</span><strong><?= e($fmt((int)$wallet['available_balance_minor'])) ?></strong></div>
</div>
<?php if($notice): ?><div class="alert alert-warning"><?= e($notice) ?></div><?php endif; ?>

<nav class="dashboard-action-table" aria-label="Quick actions">
  <a href="<?= e(route('companies.index')) ?>"><span>Invest</span><strong>Browse companies</strong><b>→</b></a>
  <a href="<?= e(route('dashboard.deposit')) ?>"><span>Deposit</span><strong>Add funds</strong><b>→</b></a>
  <a href="<?= e(route('dashboard.withdraw')) ?>"><span>Withdraw</span><strong>Move cash out</strong><b>→</b></a>
  <a href="<?= e(route('dashboard.ai-trading')) ?>"><span>AI Trading</span><strong>Manage codes</strong><b>→</b></a>
  <a href="<?= e(route('dashboard.transactions')) ?>"><span>Activity</span><strong>Transactions</strong><b>→</b></a>
</nav>

<div class="dashboard-stat-grid">
  <article><span>Active principal</span><strong><?= e($fmt((int)($portfolio['active_principal']??0))) ?></strong></article>
  <article><span>Projected profit</span><strong><?= e($fmt((int)($portfolio['projected_profit']??0))) ?></strong></article>
  <article><span>Realized profit</span><strong><?= e($fmt((int)($portfolio['realized_profit']??0))) ?></strong></article>
  <article><span>Identity status</span><strong><?= $kycApproved?'Verified':'Not verified' ?></strong></article>
</div>

<?php if(!empty($aiTrades)): ?>
<section class="dashboard-section">
  <div class="section-heading-inline"><div><p class="section-label">AI trading</p><h2>Active AI cycles</h2></div><a href="<?= e(route('dashboard.ai-trading')) ?>">View all</a></div>
  <div class="dashboard-ai-grid">
  <?php foreach($aiTrades as $trade): ?>
    <article class="dashboard-position-card">
      <div class="position-card-head"><span><?= e($trade['category_name']) ?></span><b><?= e($trade['status']) ?></b></div>
      <div class="ai-progress-meta"><span><?= e(number_format((float)$trade['progress_percent'],1)) ?>%</span><strong><?= $trade['status']==='PURCHASED'?'Waiting for activation':'In progress' ?></strong></div>
      <div class="ai-progress"><i style="width:<?= e(min(100,max(0,(float)$trade['progress_percent']))) ?>%"></i></div>
      <a href="<?= e(route('dashboard.ai-trading.show',['purchase'=>$trade['public_id']])) ?>"><?= $trade['status']==='PURCHASED'?'Activate code':'View progress' ?> →</a>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="dashboard-section">
  <div class="section-heading-inline"><div><p class="section-label">Featured investments</p><h2>Companies available to you</h2></div><a href="<?= e(route('companies.index')) ?>">See all investments</a></div>
  <?php if(!$featuredCompanies): ?><div class="empty-state"><h3>No featured companies available right now.</h3></div><?php else: ?>
  <div class="dashboard-featured-grid">
    <?php foreach($featuredCompanies as $company): ?>
    <a class="dashboard-company-row" href="<?= e(route('companies.show',['company'=>$company['public_id']])) ?>">
      <span class="dashboard-company-mark"><?= e(strtoupper(substr($company['display_name'],0,1))) ?></span>
      <span class="dashboard-company-name"><strong><?= e($company['display_name']) ?></strong><small><?= e($company['ticker']) ?> · <?= e($company['industry']?:$company['sector']?:'Company') ?></small></span>
      <span class="dashboard-company-price"><small>Investment price</small><strong><?= e(country_money(new \App\Support\Money((int)$company['share_price_minor'],$company['currency_code']))) ?></strong></span>
      <span class="dashboard-company-return"><small>Projected return</small><strong><?= e($company['projected_profit_value']) ?><?= $company['profit_type']==='PERCENTAGE'?'%':' fixed' ?></strong></span>
      <b>→</b>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
