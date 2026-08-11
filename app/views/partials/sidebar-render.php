<?php
/**
 * Shared renderer for the four role sidebars.
 *
 * `$items` is a list of [href, label, icon] — the icon name is optional, and a
 * `null` entry draws a divider. The admin sidebar builds its own markup because
 * it also carries section headings and live badges.
 */
require_once APP_PATH . '/views/partials/icons.php';
require_once APP_PATH . '/views/partials/nav-active.php';
?>
<aside class="sidebar">
  <nav>
    <?php foreach ($items as $item): ?>
      <?php if ($item === null): ?>
        <hr class="sidebar__divider">
      <?php else: [$href, $label] = $item; $ico = $item[2] ?? null; ?>
        <a class="sidebar__link<?= sidebar_active($href) ?>" href="<?= url($href) ?>">
          <?php if ($ico): ?><span class="sidebar__icon"><?= icon($ico) ?></span><?php endif; ?>
          <span class="sidebar__label"><?= e($label) ?></span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
</aside>
