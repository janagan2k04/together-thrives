<?php
/**
 * "Messages" - every thread this account is part of.
 *
 * The Figma draws a two-pane chat with the reply box on the right. This is the
 * list half only, on purpose: a thread belongs to the module that opened it, and
 * that module's screen already holds both the reply box and the guard deciding
 * who may read the thread. A second compose box here would mean a second copy of
 * that check.
 *
 * Every row is a real `conversations` row joined through `conversation_members`,
 * so the counts and the previews are what the account actually has. It used to
 * draw sample threads, which showed a newly registered user five conversations
 * it had never had.
 */
require_once APP_PATH . '/views/partials/icons.php';

/** Where a thread is read, by the context that opened it. */
$openUrl = static function (array $t): ?string {
    return match ($t['context_type'] ?? null) {
        'reuse' => '/reuse/requests/' . (int) $t['context_id'] . '/messages',
        default => null,
    };
};
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Messages</h1>
    <p>
      <?php if (!$threads): ?>
        No conversations yet
      <?php else: ?>
        <?= e(count($threads)) ?> conversation<?= count($threads) === 1 ? '' : 's' ?>
      <?php endif; ?>
    </p>
  </div>
</div>

<div class="panel">
  <div class="panel__body<?= $threads ? ' panel__body--flush' : '' ?>">
    <?php if (!$threads): ?>
      <div class="empty-state">
        <strong>No conversations yet</strong>
        <p>
          Ask a donor for an item on the reuse board, or answer a request for one
          of yours, and the thread appears here.
        </p>
      </div>
    <?php else: ?>
      <div class="mini-list">
        <?php foreach ($threads as $t): ?>
          <?php $href = $openUrl($t); ?>
          <div>
            <span>
              <strong style="color:var(--ink)">
                <?= e($t['other_name'] ?? $t['title'] ?? 'Conversation') ?>
              </strong>
              <small style="display:block;color:var(--muted-3)">
                <?php if (!empty($t['context_type'])): ?>
                  <?= e($labels[$t['context_type']] ?? ucfirst($t['context_type'])) ?>
                  <?= $t['title'] ? ' · ' . e($t['title']) : '' ?>
                <?php endif; ?>
              </small>
              <?php if ($t['last_body']): ?>
                <small style="display:block;color:var(--muted-3)">
                  <?= e(str_limit((string) $t['last_body'], 90)) ?>
                  · <?= e(time_ago($t['last_at'])) ?>
                </small>
              <?php else: ?>
                <small style="display:block;color:var(--muted-3)">No messages yet</small>
              <?php endif; ?>
            </span>
            <?php if ($href): ?>
              <a class="btn btn--outline" href="<?= url($href) ?>">Open</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
