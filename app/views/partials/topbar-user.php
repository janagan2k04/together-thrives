<?php use Core\Auth; $u = Auth::user(); ?>
<header class="topbar">
  <a class="logo" href="<?= url(Auth::homeFor()) ?>">Together<span>Thrives</span></a>

  <form class="topbar__search" action="<?= url('/search') ?>" method="get">
    <input type="search" name="q" placeholder="Search campaigns, requests, resources…">
  </form>

  <div class="topbar__spacer"></div>

  <div class="topbar__right">
    <a class="icon-btn" href="<?= url('/messages') ?>" title="Messages">&#9993;</a>
    <a class="icon-btn" href="<?= url('/notifications') ?>" title="Notifications">&#9873;</a>
    <div class="user-chip">
      <span class="user-chip__avatar"><?= e(mb_substr($u['full_name'] ?? '?', 0, 1)) ?></span>
      <span class="user-chip__meta">
        <strong><?= e($u['full_name'] ?? '') ?></strong>
        <small><?= e(ucwords(str_replace('_', ' ', $u['role'] ?? ''))) ?></small>
      </span>
    </div>
    <form action="<?= url('/logout') ?>" method="post" class="inline">
      <?= csrf_field() ?>
      <button class="btn btn--ghost" type="submit">Sign out</button>
    </form>
  </div>
</header>
