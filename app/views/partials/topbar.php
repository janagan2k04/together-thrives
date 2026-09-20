<?php
/**
 * The one topbar, used by every signed-in role including the administrator.
 *
 * There used to be two - topbar-admin.php and topbar-user.php - and they had
 * drifted apart: the logo sat 120px in for users and 24px in for the admin, the
 * admin had a sidebar toggle and nobody else did, messages were an 'inbox' icon
 * on one and 'message-circle' on the other, the icons ran in a different order,
 * sign out was a text button here and a bare icon there, and the user topbar
 * repeated a "Report a problem" link its own sidebar already carried.
 *
 * Everything role-specific now comes from these three variables, so there is
 * nothing left to drift.
 */
use Core\Auth;

require_once APP_PATH . '/views/partials/icons.php';

$u        = Auth::user();
$isAdmin  = Auth::role() === ROLE_ADMIN;
$unread   = (new Models\Notification())->unreadCount((int) $u['id']);
$roleName = $isAdmin
    ? 'Administrator'
    : ucwords(str_replace('_', ' ', $u['role'] ?? ''));
?>
<header class="topbar<?= $isAdmin ? ' topbar--admin' : '' ?>">
  <?php /* Brand first so it sits in the corner, toggle second - the order the
           admin bar already used, now the order every role gets. */ ?>
  <a class="logo" href="<?= url(Auth::homeFor()) ?>">Together<span>Thrives</span></a>

  <button type="button" class="nav-toggle" data-sidebar-toggle aria-label="Toggle navigation">
    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
      <path d="M4 7h16M4 12h16M4 17h16"/>
    </svg>
  </button>

  <div class="topbar__spacer"></div>

  <div class="topbar__right">
    <a class="icon-btn" href="<?= url('/messages') ?>" title="Messages"><?= icon('message-circle') ?></a>
    <a class="icon-btn icon-btn--count" href="<?= url('/notifications') ?>" title="Notifications">
      <?= icon('bell') ?>
      <?php if ($unread): ?><span class="icon-btn__count"><?= e($unread) ?></span><?php endif; ?>
    </a>

    <a class="user-chip" href="<?= url('/profile') ?>" title="View your profile">
      <span class="user-chip__avatar">
        <?php if (!empty($u['avatar_path'])): ?>
          <img src="<?= url('/avatar/' . $u['id']) ?>" alt="">
        <?php else: ?>
          <?= e(mb_strtoupper(mb_substr($u['full_name'] ?? '?', 0, 1))) ?>
        <?php endif; ?>
      </span>
      <span class="user-chip__meta">
        <strong><?= e($u['full_name'] ?? '') ?></strong>
        <small><?= e($roleName) ?></small>
      </span>
    </a>

    <form action="<?= url('/logout') ?>" method="post" class="inline">
      <?= csrf_field() ?>
      <button class="btn-signout" type="submit"><?= icon('log-out') ?><span>Sign out</span></button>
    </form>
  </div>
</header>
