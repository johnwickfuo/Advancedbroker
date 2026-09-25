<?php
$popupUserId=(int)($_SESSION['user_id']??0);
$popupNotifications=$popupUserId?app('notifications')->consumePopups($popupUserId,6):[];
$blockingPopups=array_values(array_filter($popupNotifications,static fn(array $popup):bool=>(string)$popup['notification_type']==='admin_popup'));
$toastPopups=array_values(array_filter($popupNotifications,static fn(array $popup):bool=>(string)$popup['notification_type']!=='admin_popup'));
?>

<?php if($blockingPopups): ?>
<div class="blocking-popup-layer" data-popup-modal-layer role="presentation">
  <div class="blocking-popup-stack" role="dialog" aria-modal="true" aria-label="Important notification">
    <?php foreach($blockingPopups as $popup): ?>
      <article class="blocking-popup-card" data-popup-modal>
        <button type="button" class="blocking-popup-close" data-popup-modal-close aria-label="Close notification">×</button>
        <p class="section-label">Important notice</p>
        <h2><?= e($popup['title']) ?></h2>
        <?php if($popup['body']): ?><p><?= nl2br(e($popup['body'])) ?></p><?php endif; ?>
        <?php if((int)$popup['display_limit']>1): ?>
          <small class="blocking-popup-count">Display <?= e($popup['display_count']) ?> of <?= e($popup['display_limit']) ?></small>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if($toastPopups): ?>
<div class="popup-stack" aria-live="polite" aria-label="Notifications">
<?php foreach($toastPopups as $popup): $data=json_decode((string)$popup['data'],true)?:[]; ?>
<article class="popup-toast" data-popup>
  <div class="popup-accent"></div>
  <div class="popup-body">
    <div class="popup-topline"><strong><?= e($popup['title']) ?></strong><button type="button" class="popup-close" data-popup-close aria-label="Dismiss">×</button></div>
    <?php if($popup['body']): ?><p><?= e($popup['body']) ?></p><?php endif; ?>
    <?php if(!empty($data['url'])): ?><a href="<?= e(url($data['url'])) ?>">Open</a><?php endif; ?>
  </div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
