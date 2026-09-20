<?php
/**
 * Admin navigation, matching the sidebar drawn on every frame of admin.svg.
 * Badge counts are live - the verification badge is the whole point of it.
 *
 * Reorganised 2026-08-18 around one idea: ACCOUNTS are decided where they are
 * listed, CONTENT has a queue of its own.
 *
 *   Community Users / Organizations / GS
 *       one screen per kind of account, each with its own approve, reject and
 *       suspend, and each badged with what it is actually waiting on. The old
 *       single "Verification Requests" account queue mixed all four roles into
 *       one table and then sent the administrator to a different screen to see
 *       the rest of that role.
 *   Verification Requests
 *       now means posts - every cleanup campaign, donation request, blood camp,
 *       critical blood request and reuse listing, waiting for approval. It moved
 *       under Content Management on 2026-09-18, which is what it decides.
 *
 * "Suspended Users and Posts" is gone. Each of the three account screens filters
 * by Suspended over real rows, so it was a third route to the same data.
 *
 * Three more came off on 2026-09-18 - Donation Request Review, Blood Campaign
 * Review and Cleanup Campaign Review. Every post they list is already in
 * Verification Requests, which reaches all five kinds through one service, so
 * they were three more routes to rows the queue above already shows. The
 * screens themselves are untouched and each member's module still declares
 * them in its own route file; only the sidebar stopped repeating them.
 *
 * Analytics & Stats came off the same day: the admin dashboard draws its eight
 * figures now, from the one Services\PlatformStatsService.
 */
require_once APP_PATH . '/views/partials/icons.php';
require_once APP_PATH . '/views/partials/nav-active.php';

$users = new Models\User();

// Accounts, split the way the three screens are.
$pendingCommunity = $users->pendingCountFor(ROLE_COMMUNITY);
$pendingOrgs      = $users->pendingCountFor(ROLE_ORGANIZATION, ROLE_BLOOD_BANK);
$pendingGs        = $users->pendingCountFor(ROLE_GN);

// Posts, across all five module tables at once.
$pendingPosts = (new Services\ContentVerificationService())->pendingCount();

// GS letters attached to reuse requests - Member 4's review queue. Not the same
// thing as the GS screen above: this checks letters an officer has ISSUED.
$letters = (new Models\Reuse\GsVerification())->pendingCount();

$sections = [
    ['', [
        ['/admin/dashboard', 'Dashboard', 'grid', null],
    ]],
    ['User & Organization Management', [
        ['/admin/users',         'Community Users',       'users',        $pendingCommunity],
        ['/admin/organizations', 'Organizations',         'building',     $pendingOrgs],
        ['/admin/gs',            'GS',                    'shield-check', $pendingGs],
    ]],
    ['Content Management', [
        // Posts, all five kinds, one queue. It sits here rather than under
        // accounts because what it decides is content - see the note above.
        ['/admin/verification',    'Verification Requests',   'file-check', $pendingPosts],
        ['/admin/reuse/letters',   'GS Letter Review',        'file-text',  $letters],
        // /gn/disasters is role:grama_niladhari,admin since 2026-09-13 - an
        // administrator files a disaster report and reads every officer's.
        ['/gn/disasters',          'Disaster Reports',        'alert-triangle', null],
        // And posts donation requests of their own, same date, same decision.
        // These are the admin's OWN appeals; the review queue above is everyone's.
        ['/my/donations',          'My Donation Requests',    'gift',           null],
        // 'message-circle' here collided with the topbar's Messages button -
        // two identical glyphs on one screen meaning different things.
        ['/admin/complaints',    'Complaints & Feedback', 'life-buoy',      null],
        ['/admin/announcements', 'Announcements',         'megaphone',      null],
        ['/admin/dengue',        'Dengue Alert',          'bug',            null],
    ]],
    ['System Management', [
        ['/admin/admins',    'Admin Management', 'user-cog',  null],
        ['/admin/logs',      'System Logs',      'file-text', null],
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
        <a class="sidebar__link<?= sidebar_active($href) ?>" href="<?= url($href) ?>">
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
