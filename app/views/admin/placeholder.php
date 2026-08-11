<?php require_once APP_PATH . '/views/partials/icons.php'; ?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1><?= e($heading) ?></h1>
    <p><?= e($lede) ?></p>
  </div>
</div>

<div class="data-card">
  <div class="admin-empty">
    <span class="admin-empty__icon" style="background:var(--brand-tint);color:var(--brand)">
      <?= icon('clock') ?>
    </span>
    <strong>Designed, not built yet</strong>
    <p><?= e($note) ?></p>
  </div>
</div>
