<?php
$popupUserId=(int)($_SESSION['user_id']??0);
$popupNotifications=$popupUserId?app('notifications')->consumePopups($popupUserId,6):[];
?>
<?php if($popupNotifications): ?>
<div class="popup-stack" aria-live="polite" aria-label="Notifications">
<?php foreach($popupNotifications as $popup): $data=json_decode((string)$popup['data'],true)?:[]; ?>
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
