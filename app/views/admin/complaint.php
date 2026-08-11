<?php
/**
 * A single complaint. No frame exists for it — the Figma's "View" button has no
 * destination — so it follows `admin/verification/show.php`, which is the shape
 * this project already uses for "read the case, then decide".
 */
require_once APP_PATH . '/views/partials/icons.php';

$tone = ['Open' => 'pending', 'In Review' => 'neutral', 'Resolved' => 'active', 'Dismissed' => 'rejected'];
?>

<a class="back-link" href="<?= url('/admin/complaints') ?>"><?= icon('arrow-left') ?>Back to Complaints</a>

<div class="admin-head">
  <div class="admin-head__text">
    <h1><?= e($complaint['subject']) ?></h1>
    <p><?= e($complaint['id']) ?> · <?= e($complaint['module']) ?> ·
       raised by <?= e($complaint['by']) ?></p>
  </div>
  <span class="pill pill--<?= e($tone[$complaint['status']] ?? 'neutral') ?>"><?= e($complaint['status']) ?></span>
</div>

<div class="detail-grid">
  <div class="detail-card">
    <div class="detail-card__head"><?= icon('message-circle') ?><strong>The complaint</strong></div>
    <div class="detail-card__body">
      <p style="font-size:var(--fs-base);color:var(--ink-2)"><?= e($complaint['sub']) ?></p>

      <dl class="field-list" style="margin-top:var(--s-5)">
        <div><dt>Reference</dt><dd><?= e($complaint['id']) ?></dd></div>
        <div><dt>Module</dt><dd><?= e($complaint['module']) ?></dd></div>
        <div><dt>Raised by</dt><dd><?= e($complaint['by']) ?></dd></div>
        <div><dt>Account type</dt><dd><?= e($complaint['byRole']) ?></dd></div>
      </dl>
    </div>
  </div>

  <div class="detail-card">
    <div class="detail-card__head"><?= icon('check-circle') ?><strong>Decision</strong></div>
    <div class="detail-card__body">
      <form class="decision" method="post" action="<?= url('/admin/complaints/' . $complaint['id'] . '/resolve') ?>">
        <?= csrf_field() ?>
        <textarea name="note" placeholder="What did you do about this? The note is shown to the person who raised it."></textarea>
        <button class="btn btn--primary" type="submit"><?= icon('check') ?>Mark resolved</button>
        <button class="btn btn--danger" type="button" disabled><?= icon('x-circle') ?>Dismiss</button>
      </form>

      <p class="sample-note" style="margin-top:var(--s-5)">
        <?= icon('help-circle') ?>
        Resolving flashes and returns — complaints are not stored yet.
      </p>

      <a class="btn btn--outline btn--block" style="margin-top:var(--s-3)" href="<?= url('/messages') ?>">
        Open the conversation
      </a>
    </div>
  </div>
</div>
