<?php
$context = country();
$countryLanguages = app('countries')->languages($context->country);
$isRtl = $context->languageCode === 'ar';
$branding=app('branding')->forCountry($context->country);
$description ??= '';
$extra_css = $extra_css ?? [];
?>
<!doctype html>
<html lang="<?= e($context->languageCode) ?>"<?= $isRtl ? ' dir="rtl"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?= e($description ?: ($branding['meta_description']??'')) ?>">
<meta property="og:title" content="<?= e($title??$branding['brand_name']) ?>">
<meta property="og:description" content="<?= e($description ?: ($branding['meta_description']??'')) ?>">
<title><?= e(($title ?? '') . ' · ' . ($branding['brand_name']??config('app.name'))) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<style nonce="<?= e(app('theme')->nonce()) ?>"><?= app('theme')->css($context->theme()) ?></style>
</head>
<body>
<header class="site-header">
    <a class="brand notranslate" translate="no" href="<?= e(route('home')) ?>"><?= e($branding['short_name']??'UPGRADED BROKER') ?></a>
    <nav aria-label="Primary">
        <a href="<?= e(route('companies.index')) ?>">Investments</a>
        <a href="<?= e(route('how-it-works')) ?>">How it works</a>
        <a href="<?= e(route('about')) ?>">About</a>
        <a href="<?= e(route('faq')) ?>">FAQ</a>
        <a href="<?= e(route('contact')) ?>">Contact</a>
        <?php if(count($countryLanguages)>1): ?>
        <form action="<?= e(route('language.update')) ?>" method="post" class="language-form notranslate" translate="no" data-auto-submit>
            <?= app('csrf')->input() ?><input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
            <label class="sr-only" for="language">Language</label>
            <select id="language" name="language"><?php foreach ($countryLanguages as $language): ?><option value="<?= e($language['code']) ?>"<?= $language['code'] === $context->languageCode ? ' selected' : '' ?>><?= e($language['native_name']) ?></option><?php endforeach; ?></select>
        </form>
        <?php endif; ?>
        <?php if(!empty($_SESSION['user_id'])): ?><a href="<?= e(route('dashboard.index')) ?>">Dashboard</a><?php else: ?><a class="sign-in-link" href="<?= e(route('login')) ?>">Sign in</a><a class="button button-small" href="<?= e(route('register')) ?>">Get started</a><?php endif; ?>
    </nav>
</header>
<?php require __DIR__ . '/../partials/flash.php'; ?>
<?php if (config('app.debug')): ?><aside class="debug-context"><b>DEV</b> <?= e($context->name()) ?> · <?= e($context->resolutionSource) ?> · <?= e($context->languageCode) ?> · <?= e($context->currencyCode()) ?></aside><?php endif; ?>
<main><?= $content ?></main>
<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <strong><?= e($branding['brand_name']??config('app.name')) ?></strong>
            <p><?= e($branding['footer_text'] ?: 'Investment opportunities presented with clear terms, company context and organised account records.') ?></p>
            <?php if($branding['support_email']): ?><a href="mailto:<?= e($branding['support_email']) ?>"><?= e($branding['support_email']) ?></a><?php endif; ?>
        </div>
        <div><h3>Invest</h3><a href="<?= e(route('companies.index')) ?>">Investment opportunities</a><a href="<?= e(route('how-it-works')) ?>">How it works</a><a href="<?= e(route('register')) ?>">Create account</a><a href="<?= e(route('login')) ?>">Sign in</a></div>
        <div><h3>Company</h3><a href="<?= e(route('about')) ?>">About</a><a href="<?= e(route('faq')) ?>">FAQ</a><a href="<?= e(route('contact')) ?>">Contact</a><a href="<?= e(route('license')) ?>">Licence information</a></div>
        <div><h3>Legal</h3><a href="<?= e(route('terms')) ?>">Terms</a><a href="<?= e(route('privacy')) ?>">Privacy</a><a href="<?= e(route('risk-disclosure')) ?>">Risk disclosure</a></div>
    </div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> <?= e($branding['brand_name']??config('app.name')) ?></span><span>Investing involves risk. Projected returns are not guaranteed.</span></div>
</footer>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
