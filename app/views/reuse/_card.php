<?php
/**
 * One item card, as used by the browse grid and the "Similar items" strip.
 * Expects $item with: id, title, status, category, city, created_at, image_id.
 */
$statusLabel = \Models\Reuse\ReuseItem::STATUSES[$item['status']] ?? $item['status'];
?>
<a class="item-card" href="<?= url('/reuse/' . $item['id']) ?>">
  <div class="item-card__media">
    <?php if (!empty($item['image_id'])): ?>
      <img src="<?= url('/reuse/image/' . $item['image_id']) ?>" alt="<?= e($item['title']) ?>">
    <?php else: ?>
      <span class="item-card__placeholder"><?= icon('gift') ?></span>
    <?php endif; ?>
    <span class="item-status item-status--<?= e($item['status']) ?>"><?= e($statusLabel) ?></span>
  </div>
  <div class="item-card__body">
    <h3 class="item-card__title"><?= e($item['title']) ?></h3>
    <p class="item-card__meta"><?= e($item['category']) ?></p>
    <p class="item-card__meta item-card__meta--icon">
      <?= icon('map-pin') ?><?= e($item['city']) ?>
    </p>
  </div>
  <div class="item-card__foot">
    <span><?= icon('clock') ?><?= e(time_ago($item['created_at'])) ?></span>
  </div>
</a>
