<?php $currentUser=app('users')->find((int)($_SESSION['user_id']??0)); $dashboardBrand=app('branding')->forCountry(country()->country); ?>
<!doctype html>
<html lang="<?= e(country()->languageCode) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(($title ?? '') . ' · ' . ($dashboardBrand['brand_name']??'ApexTrades')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<style nonce="<?= e(app('theme')->nonce()) ?>"><?= app('theme')->css(country()->theme()) ?></style>
</head>
<body class="app-shell brokerage-shell">
<?php require __DIR__.'/../partials/user-sidebar.php'; ?>
<section class="workspace brokerage-workspace">
  <header class="workspace-topbar">
    <button class="icon-button" data-menu aria-label="Open navigation">☰</button>
    <div class="workspace-search"><a href="<?= e(route('companies.index')) ?>">Search investments</a></div>
    <div class="header-account"><span><?= e($currentUser['first_name']??'Account') ?></span><a class="avatar" href="<?= e(route('dashboard.profile')) ?>"><?= e(strtoupper(substr((string)($currentUser['first_name']??'A'),0,1))) ?></a></div>
  </header>
  <?php require __DIR__.'/../partials/popups.php'; ?>
  <main><?php require __DIR__.'/../partials/flash.php'; ?><?= $content ?></main>
</section>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php require __DIR__.'/../partials/smartsupp.php'; ?>
</body></html>
