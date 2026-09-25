<?php
$context=country();
$countryLanguages=app('countries')->languages($context->country);
$isRtl=$context->languageCode==='ar';
$branding=app('branding')->forCountry($context->country);
$description??='';
$currentUser=!empty($_SESSION['user_id'])?app('users')->find((int)$_SESSION['user_id']):null;
?>
<!doctype html>
<html lang="<?= e($context->languageCode) ?>"<?= $isRtl?' dir="rtl"':'' ?>>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?= e($description ?: ($branding['meta_description']??'')) ?>">
<meta property="og:title" content="<?= e($title??$branding['brand_name']) ?>">
<meta property="og:description" content="<?= e($description ?: ($branding['meta_description']??'')) ?>">
<title><?= e(($title??'').' · '.($branding['brand_name']??config('app.name'))) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<style nonce="<?= e(app('theme')->nonce()) ?>"><?= app('theme')->css($context->theme()) ?></style>
</head>
<body>
<header class="site-header">
  <?php require __DIR__.'/../partials/brand.php'; ?>
  <nav aria-label="Primary">
    <a href="<?= e(route('companies.index')) ?>">Investments</a>
    <a href="<?= e(route('ai-trading.catalogue')) ?>">AI Trading</a>
    <a href="<?= e(route('how-it-works')) ?>">How it works</a>
    <a href="<?= e(route('about')) ?>">About</a>
    <a href="<?= e(route('faq')) ?>">FAQ</a>
    <a href="<?= e(route('contact')) ?>">Contact</a>
    <?php if(count($countryLanguages)>1): ?>
      <form action="<?= e(route('language.update')) ?>" method="post" class="language-form notranslate" translate="no" data-auto-submit>
        <?= app('csrf')->input() ?><input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI']??'/') ?>">
        <select name="language"><?php foreach($countryLanguages as $language): ?><option value="<?= e($language['code']) ?>"<?= $language['code']===$context->languageCode?' selected':'' ?>><?= e($language['native_name']) ?></option><?php endforeach; ?></select>
      </form>
    <?php endif; ?>
    <?php if($currentUser): ?>
      <?php $isAdmin=($currentUser['role']??'')==='super_admin'; ?>
      <a class="button button-small" href="<?= e($isAdmin?route('admin.index'):route('dashboard.index')) ?>"><?= $isAdmin?'Admin':'Dashboard' ?></a>
    <?php else: ?>
      <a class="sign-in-link" href="<?= e(route('login')) ?>">Sign in</a><a class="button button-small" href="<?= e(route('register')) ?>">Get started</a>
    <?php endif; ?>
  </nav>
</header>
<?php if($currentUser): ?><?php require __DIR__.'/../partials/popups.php'; ?><?php endif; ?>
<?php require __DIR__.'/../partials/flash.php'; ?>
<main><?= $content ?></main>
<footer class="site-footer">
  <div class="footer-grid">
    <div class="footer-brand"><?php require __DIR__.'/../partials/brand.php'; ?><p><?= e($branding['footer_text'] ?: 'Investment opportunities presented with clear terms, company context and organised account records.') ?></p></div>
    <div><h3>Invest</h3><a href="<?= e(route('companies.index')) ?>">Investment opportunities</a><a href="<?= e(route('ai-trading.catalogue')) ?>">AI Trading Codes</a><a href="<?= e(route('how-it-works')) ?>">How it works</a></div>
    <div><h3>Company</h3><a href="<?= e(route('about')) ?>">About</a><a href="<?= e(route('faq')) ?>">FAQ</a><a href="<?= e(route('contact')) ?>">Contact</a></div>
    <div><h3>Legal</h3><a href="<?= e(route('terms')) ?>">Terms</a><a href="<?= e(route('privacy')) ?>">Privacy</a><a href="<?= e(route('risk-disclosure')) ?>">Risk disclosure</a></div>
  </div>
  <div class="footer-bottom"><span>© <?= date('Y') ?> <?= e($branding['brand_name']??config('app.name')) ?></span><span>Investing involves risk. Projected returns are not guaranteed.</span></div>
</footer>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php require __DIR__.'/../partials/smartsupp.php'; ?>
</body></html>
