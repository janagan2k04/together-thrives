<?php
/**
 * "Messages" — the two-pane support desk from the Figma. The thread list and
 * the open conversation are sample content; the compose box is disabled rather
 * than pretending to send, so nobody mistakes it for a working chat.
 */
require_once APP_PATH . '/views/partials/icons.php';

$open = $threads[0] ?? null;
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Messages</h1>
    <p>
      <?php if ($isAdmin): ?>
        Support desk · 5 open threads, 2 awaiting your reply
      <?php else: ?>
        2 unread conversations
      <?php endif; ?>
    </p>
  </div>
  <div class="page-head__actions">
    <?php if ($isAdmin): ?>
      <a class="btn btn--outline" href="<?= url('/admin/announcements/create') ?>">Send announcement</a>
    <?php endif; ?>
    <button class="btn btn--brand" type="button" disabled>New message</button>
  </div>
</div>

<p class="sample-note">
  <?= icon('help-circle') ?>
  Chat is designed but not built. The three tables it needs are in the schema and no module
  writes to them yet, so this is the week 3 render and replying is switched off.
</p>

<div class="panel">
  <div class="chat">
    <div class="chat__list">
      <div class="chat__search">
        <?= icon('search') ?>
        <input type="search" placeholder="<?= $isAdmin ? 'Search complaints and disputes' : 'Search conversations' ?>" disabled>
      </div>

      <div class="chip-row">
        <?php if ($isAdmin): ?>
          <button type="button" class="chip is-active">Open</button>
          <button type="button" class="chip">In progress</button>
          <button type="button" class="chip">Resolved</button>
        <?php else: ?>
          <button type="button" class="chip is-active">Direct</button>
          <button type="button" class="chip">Group Chats</button>
        <?php endif; ?>
      </div>

      <?php foreach ($threads as $i => $t): ?>
        <button type="button" class="thread<?= $i === 0 ? ' is-active' : '' ?>">
          <span class="avatar"><?= e($t['initials']) ?></span>
          <span class="thread__body">
            <span class="thread__top">
              <strong><?= e($t['name']) ?></strong>
              <small><?= e($t['when']) ?></small>
            </span>
            <span class="thread__preview"><?= e($t['preview']) ?></span>
            <span class="thread__tag"><?= e($t['tag']) ?></span>
          </span>
          <?php if ($t['unread']): ?>
            <span class="thread__count"><?= e($t['unread']) ?></span>
          <?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="chat__panel">
      <?php if ($open): ?>
        <div class="chat__head">
          <span class="avatar avatar--brand"><?= e($open['initials']) ?></span>
          <div style="flex:1">
            <strong><?= e($open['name']) ?></strong>
            <small><?= e($open['tag']) ?></small>
          </div>
          <?php if ($open['state'] !== ''): ?>
            <span class="badge badge--pending"><?= e($open['state']) ?></span>
          <?php endif; ?>
          <?php if ($isAdmin): ?>
            <button class="btn btn--outline" type="button" disabled>Mark resolved</button>
          <?php endif; ?>
        </div>

        <div class="chat__log">
          <span class="chat__day">Today</span>
          <?php foreach ($bubbles as $b): ?>
            <div class="bubble<?= $b['mine'] ? ' bubble--mine' : '' ?>">
              <div class="bubble__text"><?= e($b['text']) ?></div>
              <span class="bubble__when"><?= e($b['when']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="chat__compose">
          <input type="text" placeholder="Type a message…" disabled>
          <button class="chat__send" type="button" disabled aria-label="Send"><?= icon('arrow-right') ?></button>
        </div>
      <?php else: ?>
        <div class="empty-state">Select a conversation.</div>
      <?php endif; ?>
    </div>
  </div>
</div>
