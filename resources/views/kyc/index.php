<div class="dashboard-page-head">
  <div>
    <p class="eyebrow">Account verification</p>
    <h1>Identity verification</h1>
    <p class="muted">Submit basic identity information and one valid government-issued identification document.</p>
  </div>
</div>

<?php if(!$config): ?>
<section class="card kyc-status-panel">
  <h2>Verification is not configured</h2>
  <p>Please contact support if you need identity verification.</p>
</section>
<?php elseif($submission&&$submission['status']==='APPROVED'): ?>
<section class="card success-state kyc-status-panel">
  <p class="section-label">Verification status</p>
  <h2>Identity verified</h2>
  <p>Your identity verification has been approved.</p>
</section>
<?php elseif($submission&&in_array($submission['status'],['PENDING','UNDER_REVIEW'],true)): ?>
<section class="card kyc-status-panel">
  <p class="section-label">Verification status</p>
  <h2><?= e($submission['status']==='PENDING'?'Submitted':'Under review') ?></h2>
  <p>Your identity information has been submitted and cannot be edited while it is being reviewed.</p>
</section>
<?php else: ?>
<section class="card kyc-status-panel">
  <p class="section-label"><?= $required?'Required verification':'Optional verification' ?></p>
  <h2><?= $required?'Complete identity verification':'Verify your identity' ?></h2>
  <?php if(!$required): ?><p class="muted">KYC is available for your account but is not currently required before withdrawals in your Country Pack.</p><?php endif; ?>
  <?php if($submission): ?><p>Status: <strong><?= e($submission['status']) ?></strong></p><?php endif; ?>

  <?php if(!empty($submission['rejection_reason'])): ?>
    <div class="alert error"><strong>Why this was rejected</strong><p><?= e($submission['rejection_reason']) ?></p></div>
  <?php endif; ?>

  <?php if(!empty($submission['resubmission_message'])): ?>
    <div class="alert warning"><strong>Update required</strong><p><?= e($submission['resubmission_message']) ?></p></div>
  <?php endif; ?>

  <p><?= nl2br(e($config['instructions']??'')) ?></p>

  <form method="post" action="<?= e(route('dashboard.kyc.save')) ?>" enctype="multipart/form-data" class="form-stack">
    <?= app('csrf')->input() ?>
    <?php require __DIR__.'/../components/dynamic-form.php'; ?>
    <div class="button-row">
      <button class="button button-secondary" name="submit" value="0">Save draft</button>
      <button class="button" name="submit" value="1">Submit for review</button>
    </div>
  </form>
</section>
<?php endif; ?>
