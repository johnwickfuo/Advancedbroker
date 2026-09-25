<?php
$formatLocal=static fn(int $minor)=>country_money(new \App\Support\Money($minor,country()->currencyCode()));
?>
<section class="ai-public-hero">
  <div>
    <p class="eyebrow">AI trading codes</p>
    <h1>Activate a fixed-profit AI trading cycle.</h1>
    <p class="lead">Choose a category, purchase one unique code, then activate it to start the timed AI trading cycle. Each category has a fixed USD base price, fixed profit rate and defined duration.</p>
    <div class="ai-hero-points"><span>Unique single-use code</span><span>Fixed profit terms</span><span>Activation-based timing</span></div>
  </div>
  <div class="ai-orbit-visual" aria-hidden="true"><div class="ai-core">AI</div><i></i><i></i><i></i><span>01</span><span>50</span><span>%</span></div>
</section>

<section class="ai-catalogue-shell">
  <div class="home-section-heading">
    <div><p class="section-label">Available categories</p><h2>Choose the code category that fits your budget.</h2></div>
    <p>Prices below are shown in <?= e(country()->currencyCode()) ?> using the fixed exchange-rate snapshot locked into this build. Existing purchases never change when exchange rates move later.</p>
  </div>
  <?php if(!$categories): ?>
    <div class="empty-state"><h2>No AI trading categories yet</h2><p>Categories will appear here after they are created by the platform administrator.</p></div>
  <?php else: ?>
  <div class="ai-category-grid">
    <?php foreach($categories as $category): $q=$category['quote']; ?>
    <article class="ai-category-card">
      <div class="ai-category-top"><span class="mini-label"><?= e($category['name']) ?></span><span class="ai-stock"><?= e($category['available_codes']) ?> codes ready</span></div>
      <h3><?= e($category['name']) ?></h3>
      <p><?= e($category['description'] ?: 'A fixed-profit AI trading code with a defined activation period.') ?></p>
      <dl class="ai-category-facts">
        <div><dt>Price</dt><dd><?= e($formatLocal((int)$q['purchase_amount_minor'])) ?></dd><small>$<?= e(number_format((int)$category['price_usd_minor']/100,2)) ?> USD base</small></div>
        <div><dt>Fixed profit</dt><dd><?= e(rtrim(rtrim(number_format((float)$category['profit_percent'],4,'.',''),'0'),'.')) ?>%</dd><small><?= e($formatLocal((int)$q['profit_amount_minor'])) ?> profit</small></div>
        <div><dt>Total at completion</dt><dd><?= e($formatLocal((int)$q['maturity_payout_minor'])) ?></dd><small>Purchase amount + fixed profit</small></div>
        <div><dt>Duration</dt><dd><?= e($category['duration_value'].' '.strtolower($category['duration_unit'])) ?></dd><small>Begins when you activate</small></div>
      </dl>
      <?php if(!empty($_SESSION['user_id'])): ?>
      <form method="post" action="<?= e(route('ai-trading.buy',['category'=>$category['public_id']])) ?>" data-confirm="Purchase this AI trading code?">
        <?= app('csrf')->input() ?>
        <button class="button ai-buy-button" type="submit">Buy AI trading code</button>
      </form>
      <?php else: ?>
      <div class="ai-card-actions"><a class="button" href="<?= e(route('login')) ?>">Sign in to buy</a><a class="button button-secondary" href="<?= e(route('register')) ?>">Create account</a></div>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<section class="ai-how-shell">
  <p class="section-label">How it works</p><h2>Purchase. Activate. Track. Complete.</h2>
  <div class="process-grid">
    <article><span>01</span><h3>Choose a category</h3><p>Review the price, fixed profit rate and duration before purchase.</p></article>
    <article><span>02</span><h3>Receive your code</h3><p>Your purchase is assigned one unique single-use code from that category.</p></article>
    <article><span>03</span><h3>Activate it</h3><p>Enter the code from your account. The duration begins only after successful activation.</p></article>
    <article><span>04</span><h3>Track progress</h3><p>Your dashboard progress bar advances with elapsed time until the scheduled completion point.</p></article>
  </div>
</section>
