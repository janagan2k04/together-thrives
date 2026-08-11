<?php
/**
 * Admin navigation, matching the sidebar drawn on every frame of admin.svg.
 * Badge counts are live — the verification badge is the whole point of it.
 */
require_once APP_PATH . '/views/partials/icons.php';

$pending = (new Models\User())->pendingCount();

$sections = [
    ['', [
        ['/admin/dashboard', 'Dashboard', 'grid', null],
    ]],
    ['User & Organization Management', [
        ['/admin/users',         'Users',                     'users',        null],
        ['/admin/organizations', 'Organizations',             'building',     null],
        ['/admin/verification',  'Verification Requests',     'shield-check', $pending],
        ['/admin/suspended',     'Suspended Users and Posts', 'ban',          null],
    ]],
    ['Content Management', [
        ['/admin/complaints',    'Complaints & Feedback', 'message-circle', null],
        ['/admin/announcements', 'Announcements',         'megaphone',      null],
        ['/admin/dengue',        'Dengue Alert',          'bug',            null],
    ]],
    ['System Management', [
        ['/admin/admins',    'Admin Management', 'user-cog',  null],
        ['/admin/logs',      'System Logs',      'file-text', null],
        ['/admin/analytics', 'Analytics & Stats', 'bar-chart', null],
        ['/admin/settings',  'Settings',         'settings',  null],
    ]],
];
?>
<aside class="sidebar sidebar--admin">
  <nav>
    <?php foreach ($sections as [$label, $links]): ?>
      <?php if ($label !== ''): ?>
        <p class="sidebar__section"><?= e($label) ?></p>
      <?php endif; ?>

      <?php foreach ($links as [$href, $text, $ico, $badge]): ?>
        <a class="sidebar__link<?= nav_active($href) ?>" href="<?= url($href) ?>">
          <span class="sidebar__icon"><?= icon($ico) ?></span>
          <span class="sidebar__label"><?= e($text) ?></span>
          <?php if ($badge): ?>
            <span class="sidebar__badge"><?= e($badge) ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
</aside>
