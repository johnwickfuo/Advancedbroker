<?php
$sidebarUser=$currentUser??app('users')->find((int)($_SESSION['user_id']??0));
$marketName=country()->isGlobal()?null:country()->name();
$uri=(string)($_SERVER['REQUEST_URI']??'/');
$active=static fn(string $path): string => str_starts_with($uri,$path)?' active':'';
?>
<aside class="dashboard-sidebar persistent-user-sidebar">
  <div class="sidebar-brand"><?php require __DIR__.'/brand.php'; ?></div>
  <div class="sidebar-account-card">
    <span><?= e($sidebarUser['first_name']??'Investor') ?> <?= e($sidebarUser['last_name']??'') ?></span>
    <strong><?= $marketName?e($marketName).' · ':'' ?><?= e(country()->currencyCode()) ?></strong>
  </div>
  <nav class="dashboard-nav" aria-label="Account">
    <a class="<?= e($active('/dashboard/')) ?>" href="<?= e(route('dashboard.index')) ?>">Overview</a>
    <a href="<?= e(route('companies.index')) ?>">Invest</a>
    <a class="<?= e($active('/dashboard/ai-trading')) ?>" href="<?= e(route('dashboard.ai-trading')) ?>">AI Trading</a>
    <a class="<?= e($active('/dashboard/portfolio')) ?>" href="<?= e(route('dashboard.portfolio')) ?>">Portfolio</a>
    <a class="<?= e($active('/dashboard/deposit')) ?>" href="<?= e(route('dashboard.deposit')) ?>">Deposit</a>
    <a class="<?= e($active('/dashboard/withdraw')) ?>" href="<?= e(route('dashboard.withdraw')) ?>">Withdraw</a>
    <a class="<?= e($active('/dashboard/transactions')) ?>" href="<?= e(route('dashboard.transactions')) ?>">Transactions</a>
    <a class="<?= e($active('/dashboard/kyc')) ?>" href="<?= e(route('dashboard.kyc')) ?>">KYC</a>
    <a class="<?= e($active('/dashboard/profile')) ?>" href="<?= e(route('dashboard.profile')) ?>">Profile</a>
    <a class="<?= e($active('/dashboard/security')) ?>" href="<?= e(route('dashboard.security')) ?>">Security</a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= e(route('home')) ?>">Public website</a>
    <form method="post" action="<?= e(route('logout')) ?>"><?= app('csrf')->input() ?><button type="submit">Sign out</button></form>
  </div>
</aside>
