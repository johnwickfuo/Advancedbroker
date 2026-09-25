<?php
$variant = $experience['variant'];
$hasFeatured = !empty($featured_companies);
$formatOffering = static function(array $company): string {
    return country_money(new \App\Support\Money((int)$company['share_price_minor'], (string)$company['currency_code']));
};
$marketLabel = $country->isGlobal() ? null : $country->name();
?>
<div class="market-home home-variant-<?= e($variant) ?>">
<section class="home-hero home-hero-with-photo">
    <div class="home-hero-copy">
        <p class="home-kicker"><?= e($experience['kicker']) ?></p>
        <h1><?= e($experience['hero']) ?></h1>
        <p class="home-lead"><?= e($experience['sub']) ?></p>
        <div class="home-actions">
            <a class="button home-button-primary" href="<?= e(route('companies.index')) ?>"><?= e($experience['primary_cta']) ?></a>
            <a class="button home-button-ghost" href="<?= e(route('register')) ?>"><?= e($experience['secondary_cta']) ?></a>
        </div>
        <p class="home-risk-note">Investing involves risk. Projected returns are not guaranteed.</p>
    </div>
    <figure class="home-hero-photo">
        <img src="<?= e($media['hero_url']) ?>" alt="<?= e($media['alt']) ?>" loading="eager" fetchpriority="high">
        <figcaption>
            <?php if($marketLabel): ?><span><?= e($marketLabel) ?></span><?php else: ?><span>Investment perspective</span><?php endif; ?>
            <strong><?= e($country->currencyCode()) ?></strong>
        </figcaption>
        <?php if($hasFeatured): $heroCompany=$featured_companies[0]; ?>
        <a class="photo-opportunity" href="<?= e(route('companies.show',['company'=>$heroCompany['public_id']])) ?>">
            <span>Featured opportunity</span>
            <strong><?= e($heroCompany['display_name']) ?></strong>
            <small><?= e($formatOffering($heroCompany)) ?> · <?= e($heroCompany['duration_value'].' '.strtolower($heroCompany['duration_unit'])) ?></small>
        </a>
        <?php endif; ?>
    </figure>
</section>

<section class="home-trust-strip" aria-label="Platform principles">
    <?php foreach($experience['trust'] as $index=>$item): ?><div><span>0<?= $index+1 ?></span><strong><?= e($item) ?></strong></div><?php endforeach; ?>
</section>

<section class="home-section home-intro">
    <div class="section-label">Our approach</div>
    <div class="home-two-col home-two-col-wide">
        <div><h2><?= e($experience['introTitle']) ?></h2></div>
        <div class="prose-large"><p><?= e($experience['introBody']) ?></p><p>Rather than reducing an investment to a single projected figure, the platform gives the business, the structure and the practical terms enough space to be understood together.</p><a class="home-inline-link" href="<?= e(route('about')) ?>">Learn more about our approach →</a></div>
    </div>
</section>

<section class="home-market-image-band">
    <img src="<?= e($media['card_url']) ?>" alt="<?= e($media['alt']) ?>" loading="lazy">
    <div><p class="section-label">A clearer market view</p><h2>Research the business. Read the terms. Make your own decision.</h2></div>
</section>

<section class="home-section home-featured">
    <div class="home-section-heading"><div><p class="section-label">Current opportunities</p><h2><?= e($experience['featured_title']) ?></h2></div><div><p><?= e($experience['featured_body']) ?></p><a class="home-inline-link" href="<?= e(route('companies.index')) ?>">View all investments →</a></div></div>
    <?php if($hasFeatured): ?>
    <div class="home-investment-grid five-featured">
        <?php foreach(array_slice($featured_companies,0,5) as $company): ?>
            <article class="home-investment-card">
                <div class="investment-card-top"><span class="company-monogram"><?= e(strtoupper(substr($company['display_name'],0,1))) ?></span><span class="mini-label"><?= e($company['ticker']) ?></span></div>
                <p class="investment-sector"><?= e($company['industry'] ?: $company['sector'] ?: 'Listed company') ?></p>
                <h3><?= e($company['display_name']) ?></h3>
                <p class="investment-description"><?= e($company['short_description'] ?? 'Review company information and the current investment terms.') ?></p>
                <dl class="investment-facts"><div><dt>Price</dt><dd><?= e($formatOffering($company)) ?></dd></div><div><dt>Projected return</dt><dd><?= e($company['projected_profit_value']) ?><?= $company['profit_type']==='PERCENTAGE'?'%':' fixed' ?></dd></div><div><dt>Duration</dt><dd><?= e($company['duration_value'].' '.strtolower($company['duration_unit'])) ?></dd></div></dl>
                <a class="home-card-link" href="<?= e(route('companies.show',['company'=>$company['public_id']])) ?>">Review opportunity <span>→</span></a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="home-empty-opportunity"><div><span class="mini-label">Opportunity catalogue</span><h3>New investment opportunities are being prepared.</h3></div><p>When an opportunity becomes active, the company information and offering terms will appear here for review.</p><a class="button button-secondary" href="<?= e(route('register')) ?>">Create account</a></div>
    <?php endif; ?>
</section>

<?php if(!empty($featured_sectors)): ?><section class="home-section home-sectors"><p class="section-label">Explore by sector</p><div class="sector-row"><?php foreach($featured_sectors as $sector): ?><a href="<?= e(route('companies.index').'?industry='.urlencode($sector)) ?>"><?= e($sector) ?><span>↗</span></a><?php endforeach; ?></div></section><?php endif; ?>


<section class="home-ai-trading">
  <div class="home-ai-copy">
    <p class="section-label">AI Trading Codes</p>
    <h2>A fixed-profit trading cycle activated by your unique code.</h2>
    <p>Choose an AI trading category with a defined price, guaranteed fixed-profit rate and duration. After purchase, you receive one unique single-use code. Enter that code when you are ready to activate the cycle; only then does the timer begin.</p>
    <p>Once activated, your dashboard shows a percentage-based progress bar from 0% to 100%. At scheduled completion, the platform credits your original purchase amount plus the fixed profit specified for that category.</p>
    <div class="ai-home-actions"><a class="button" href="<?= e(route('ai-trading.catalogue')) ?>">Explore AI Trading Codes</a><?php if(empty($_SESSION['user_id'])): ?><a class="home-inline-link" href="<?= e(route('register')) ?>">Create an account →</a><?php endif; ?></div>
  </div>
  <div class="home-ai-visual" aria-hidden="true">
    <div class="ai-home-chip"><span>AI CODE</span><strong>AI-4F7A-91C2-B830</strong></div>
    <div class="ai-home-profit"><span>Fixed profit</span><strong>+50%</strong></div>
    <div class="ai-home-progress"><span>AI trading progress</span><div><i></i></div><small>68% complete</small></div>
    <div class="ai-home-steps"><b>Purchase</b><b>Activate</b><b>Complete</b></div>
  </div>
</section>

<section class="home-section home-why"><div class="home-two-col"><div class="why-copy"><p class="section-label">Why this platform</p><h2><?= e($experience['whyTitle']) ?></h2><p><?= e($experience['whyBody']) ?></p></div><div class="why-grid"><article><span>01</span><h3>Company context</h3><p>Understand what the business does and review the information recorded for the company.</p></article><article><span>02</span><h3>Visible terms</h3><p>See investment price, projected outcome, duration and other configured terms before purchase.</p></article><article><span>03</span><h3>Account records</h3><p>Keep transaction and investment activity organised in a single account experience.</p></article><article><span>04</span><h3>Your decision</h3><p>The platform presents information and structure; it does not turn projections into promises.</p></article></div></div></section>

<section class="home-section home-process"><div class="home-section-heading"><div><p class="section-label">How it works</p><h2>From account setup to portfolio tracking.</h2></div><p>A straightforward process keeps each stage of the investment journey understandable.</p></div><div class="process-grid"><?php foreach($experience['process'] as $step): ?><article><span><?= e($step[0]) ?></span><h3><?= e($step[1]) ?></h3><p><?= e($step[2]) ?></p></article><?php endforeach; ?></div><a class="home-inline-link" href="<?= e(route('how-it-works')) ?>">See the complete process →</a></section>

<section class="home-section home-philosophy"><div class="philosophy-panel"><p class="section-label">Investment philosophy</p><h2><?= e($experience['philosophyTitle']) ?></h2><p><?= e($experience['philosophyBody']) ?></p><blockquote>“The purpose of the platform is to make an opportunity easier to understand—not to make the decision for you.”</blockquote></div></section>

<section class="home-section home-security"><div class="home-two-col"><div><p class="section-label">Account protection</p><h2><?= e($experience['security_title']) ?></h2><p>Your investment experience depends on more than the opportunity itself. Account access, private information and financial records are treated as part of the core product.</p></div><div class="security-list"><?php foreach($experience['security_points'] as $item): ?><div><span aria-hidden="true">✓</span><strong><?= e($item) ?></strong></div><?php endforeach; ?></div></div></section>

<section class="home-section home-portfolio"><div class="portfolio-shell"><div class="portfolio-copy"><p class="section-label">Portfolio experience</p><h2><?= e($experience['portfolio_title']) ?></h2><p><?= e($experience['portfolio_body']) ?></p><a class="home-inline-link" href="<?= e(route('register')) ?>">Create your account →</a></div><div class="portfolio-preview" aria-hidden="true"><div class="preview-head"><i></i><i></i><i></i><span>Portfolio</span></div><div class="preview-balance"><span>Portfolio activity</span><strong>Clear. Organised. Traceable.</strong></div><div class="preview-bars"><b style="height:38%"></b><b style="height:56%"></b><b style="height:44%"></b><b style="height:74%"></b><b style="height:61%"></b><b style="height:88%"></b></div><div class="preview-lines"><i></i><i></i><i></i></div></div></div></section>

<section class="home-section home-risk"><div class="risk-mark">!</div><div><p class="section-label">Risk & transparency</p><h2><?= e($experience['risk_title']) ?></h2><p><?= e($experience['risk_body']) ?></p><a class="home-inline-link" href="<?= e(route('risk-disclosure')) ?>">Read the risk disclosure →</a></div></section>

<section class="home-section home-resources"><div class="home-section-heading"><div><p class="section-label">Investor resources</p><h2><?= e($experience['resources_title']) ?></h2></div><p>Strong investment habits begin with understanding how to read an opportunity, not simply finding one.</p></div><div class="resource-grid"><?php foreach($experience['resources'] as $i=>$resource): ?><article><span>0<?= $i+1 ?></span><h3><?= e($resource[0]) ?></h3><p><?= e($resource[1]) ?></p></article><?php endforeach; ?></div></section>

<section class="home-section home-faq"><div class="faq-heading"><p class="section-label">Frequently asked questions</p><h2>Useful answers before you get started.</h2><p>Start with the essentials, then visit the full help section for more detail.</p><a class="home-inline-link" href="<?= e(route('faq')) ?>">View all FAQs →</a></div><div class="faq-list"><?php foreach($experience['faq'] as $i=>$faq): ?><details<?= $i===0?' open':'' ?>><summary><?= e($faq[0]) ?><span>+</span></summary><p><?= e($faq[1]) ?></p></details><?php endforeach; ?></div></section>

<section class="home-final-cta"><div><p class="section-label">Ready when you are</p><h2><?= e($experience['finalTitle']) ?></h2><p><?= e($experience['finalBody']) ?></p></div><div class="home-actions"><a class="button home-button-light" href="<?= e(route('companies.index')) ?>">Explore investments</a><a class="button home-button-outline-light" href="<?= e(route('register')) ?>">Create account</a></div></section>
</div>
