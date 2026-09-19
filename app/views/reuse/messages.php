<?php
/**
 * The donor <-> requester thread for one reuse listing.
 *
 * No Figma frame covers it - the design has a generic inbox and nothing keyed
 * to a listing - so it borrows the chat markup already in components/ui.css and
 * the page frame the rest of the reuse module uses.
 *
 * Both sides open the same URL; `$meId` is what puts a bubble on the right.
 */
require_once APP_PATH . '/views/partials/icons.php';

$states = \Models\Reuse\ItemRequest::STATUSES;
$backTo = (int) $item['donor_id'] === $meId
    ? '/my/reuse/' . $item['id'] . '/requests'
    : '/my/activities';
?>

<div class="reuse-page">

  <a class="item-back" href="<?= url($backTo) ?>">
    <?= icon('arrow-left') ?> Back
  </a>

  <div class="page-header">
    <div>
      <h1>Messages</h1>
      <p class="page-header__sub">
        About <a href="<?= url('/reuse/' . $item['id']) ?>"><?= e($item['title']) ?></a>
        <?php if ($other): ?>
          &nbsp;·&nbsp; with <?= e($other['full_name']) ?>
        <?php endif; ?>
        &nbsp;·&nbsp;
        <span class="badge <?= $request['status'] === 'selected' ? 'badge--success' : ($request['status'] === 'pending' ? 'badge--pending' : 'badge--neutral') ?>">
          <?= e($states[$request['status']] ?? $request['status']) ?>
        </span>
      </p>
    </div>
  </div>

  <div class="card">
    <div class="chat__log" style="min-height:280px">
      <?php if (!$messages): ?>
        <div class="empty-state">
          <strong>No messages yet</strong>
          <p>Agree a time and a place for the handover. Only the two of you can read this.</p>
        </div>
      <?php else: ?>
        <?php foreach ($messages as $m): ?>
          <?php $mine = (int) $m['sender_id'] === $meId; ?>
          <div class="bubble<?= $mine ? ' bubble--mine' : '' ?>">
            <div class="bubble__text"><?= e($m['body']) ?></div>
            <span class="bubble__when">
              <?= e($mine ? 'You' : $m['sender_name']) ?> · <?= e(time_ago($m['created_at'])) ?>
            </span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <form method="post" action="<?= url('/reuse/requests/' . $request['id'] . '/messages') ?>">
      <?= csrf_field() ?>
      <div class="field <?= has_error('body') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="body">Your message</label>
        <textarea class="field__textarea" id="body" name="body" rows="3" maxlength="2000"
                  placeholder="e.g. I can be at the pickup point on Saturday morning - does 10am suit?"><?= old('body') ?></textarea>
        <span class="field__error"><?= error_for('body') ?></span>
      </div>
      <div class="form-actions">
        <button class="btn btn--primary" type="submit">Send message</button>
      </div>
    </form>
  </div>
</div>
