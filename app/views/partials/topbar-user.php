<?php
/**
 * Topbar for every signed-in role except the administrator.
 *
 * The bell carries the real unread count — the same query the admin topbar
 * uses — so it is worth glancing at rather than decorative.
 */
use Core\Auth;

require_once APP_PATH . '/views/partials/icons.php';

$u      = Auth::user();
$unread = (new Models\Notification())->unreadCount((int) $u['id']);
?>
<header class="topbar">
  <a class="logo" href="<?= url(Auth::homeFor()) ?>">Together<span>Thrives</span></a>

  <form class="topbar__search" action="<?= url('/search') ?>" method="get">
    <input type="search" name="q" placeholder="Search campaigns, requests, resources…">
  </form>

  <div class="topbar__spacer"></div>

  <div class="topbar__right">
    <a class="icon-btn" href="<?= url('/report') ?>" title="Report a problem"><?= icon('help-circle') ?></a>
    <a class="icon-btn" href="<?= url('/messages') ?>" title="Messages"><?= icon('message-circle') ?></a>
    <a class="icon-btn icon-btn--count" href="<?= url('/notifications') ?>" title="Notifications">
      <?= icon('bell') ?>
      <?php if ($unread): ?><span class="icon-btn__count"><?= e($unread) ?></span><?php endif; ?>
    </a>

    <a class="user-chip" href="<?= url('/profile') ?>">
      <span class="user-chip__avatar"><?= e(mb_strtoupper(mb_substr($u['full_name'] ?? '?', 0, 1))) ?></span>
      <span class="user-chip__meta">
        <strong><?= e($u['full_name'] ?? '') ?></strong>
        <small><?= e(ucwords(str_replace('_', ' ', $u['role'] ?? ''))) ?></small>
      </span>
    </a>

    <form action="<?= url('/logout') ?>" method="post" class="inline">
      <?= csrf_field() ?>
      <button class="btn btn--ghost" type="submit">Sign out</button>
    </form>
  </div>
</header>
