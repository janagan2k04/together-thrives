<?php
/**
 * Shared renderer for the four role sidebars.
 *
 * `$items` is a list of [href, label, icon] - the icon name is optional, and a
 * `null` entry draws a divider. The admin sidebar builds its own markup because
 * it also carries section headings and live badges.
 *
 * A route that appears in more than one sidebar must use the same label and the
 * same icon in each. They had drifted - one page was "Blood Donation" to a
 * community member and "Browse Campaigns" to a blood bank - so the shared ones
 * are fixed here and the role files copy from this table:
 *
 *   /reuse            Reuse Items            recycle
 *   /cleanup          Cleanup Campaigns      leaf
 *   /blood            Blood Donation         droplet
 *   /my/blood         My Blood Campaigns     droplet
 *   /donations        Donation Requests      gift
 *   /my/donations     My Donation Requests   gift
 *   /dengue           Dengue Alert           bug
 *   /my/activities    My Activities          clipboard
 *   /organizations    Organizations          building
 *   /settings         Settings               settings
 *   /support          Support                life-buoy
 *   /report           Report a Problem       help-circle
 *
 * No glyph should appear twice in one sidebar; that is how reuse and cleanup
 * ended up both wearing 'leaf'.
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
