<?php
/**
 * Admin topbar from admin.svg: brand, sidebar toggle, wide search, bell and
 * inbox with unread counts, then the account chip.
 */
use Core\Auth;

require_once APP_PATH . '/views/partials/icons.php';

$u      = Auth::user();
$unread = (new Models\Notification())->unreadCount((int) $u['id']);
?>
<header class="topbar topbar--admin">
  <a class="logo" href="<?= url('/admin/dashboard') ?>">Together<span>Thrives</span></a>

  <button type="button" class="icon-btn topbar__toggle" data-sidebar-toggle
          aria-label="Toggle navigation">
    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.7" stroke-linecap="round" aria-hidden="true">
      <path d="M4 7h16M4 12h16M4 17h16"/>
    </svg>
  </button>

  <form class="topbar__search" action="<?= url('/admin/users') ?>" method="get">
    <span class="topbar__search-icon"><?= icon('search') ?></span>
    <input type="search" name="q" placeholder="Search users, organizations, campaigns, reports…"
           value="<?= old('q') ?>">
  </form>

  <div class="topbar__spacer"></div>

  <div class="topbar__right">
    <a class="icon-btn icon-btn--count" href="<?= url('/notifications') ?>" title="Notifications">
      <?= icon('bell') ?>
      <?php if ($unread): ?><span class="icon-btn__count"><?= e($unread) ?></span><?php endif; ?>
    </a>
    <a class="icon-btn" href="<?= url('/messages') ?>" title="Messages"><?= icon('inbox') ?></a>

    <div class="user-chip">
      <span class="user-chip__avatar"><?= icon('user') ?></span>
      <span class="user-chip__meta">
        <strong><?= e($u['full_name'] ?? '') ?></strong>
        <small>Super Administrator</small>
      </span>
      <form action="<?= url('/logout') ?>" method="post" class="inline">
        <?= csrf_field() ?>
        <button class="icon-btn" type="submit" title="Sign out"><?= icon('log-out') ?></button>
      </form>
    </div>
  </div>
</header>
