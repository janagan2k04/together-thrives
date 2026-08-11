<?php
/**
 * "My Activities" — the requester's side of the reuse request flow.
 *
 * No Figma frame covers this screen (the design draws the donor's side only),
 * so it follows the table pattern already used by views/reuse/requests.php.
 *
 * The review form appears on exactly one condition: the donor selected you and
 * then confirmed the handover. Anything looser would let people rate donors
 * they never actually met.
 */
require_once APP_PATH . '/views/partials/icons.php';

$states = \Models\Reuse\ItemRequest::STATUSES;

$counts   = ['pending' => 0, 'selected' => 0, 'received' => 0];
$toReview = [];

foreach ($requests as $r) {
    if (isset($counts[$r['status']])) {
        $counts[$r['status']]++;
    }
    if ($r['status'] === 'selected' && $r['item_status'] === ITEM_DONATED) {
        $counts['received']++;
        if ($r['feedback_id'] === null) {
            $toReview[] = $r;
        }
    }
}

$cells = [
    ['Requests Made',    count($requests)],
    ['Awaiting a Reply', $counts['pending']],
    ['Accepted',         $counts['selected']],
    ['Items Received',   $counts['received']],
];
?>

<div class="reuse-page">

  <div class="page-header">
    <div>
      <h1>My Activities</h1>
      <p class="page-header__sub">
        Everything you have asked for on the reuse board, and what happened to it.
      </p>
    </div>
    <div class="page-header__spacer"></div>
    <a class="btn btn--outline" href="<?= url('/reuse') ?>">Browse items</a>
  </div>

  <div class="stat-strip">
    <?php foreach ($cells as [$label, $value]): ?>
      <div class="stat-strip__cell">
        <strong><?= (int) $value ?></strong>
        <span><?= e($label) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($toReview): ?>
    <h2 class="reuse-section">Waiting for your review</h2>

    <?php if (has_error('rating') || has_error('comment')): ?>
      <div class="field__error" style="margin-bottom: var(--s-3)">
        <?= error_for('rating') ?: error_for('comment') ?>
      </div>
    <?php endif; ?>

    <?php foreach ($toReview as $r): ?>
      <div class="card review-card">
        <div class="cell-item">
          <span class="cell-item__thumb">
            <?php if ($r['image_id']): ?>
              <img src="<?= url('/reuse/image/' . $r['image_id']) ?>" alt="">
            <?php else: ?>
              <?= icon('gift') ?>
            <?php endif; ?>
          </span>
          <div>
            <div class="cell-item__title"><?= e($r['title']) ?></div>
            <div class="link-muted">
              Given to you by
              <a href="<?= url('/reuse/donor/' . $r['donor_id']) ?>"><?= e($r['donor_name']) ?></a>
            </div>
          </div>
        </div>

        <form method="post" action="<?= url('/my/activities/' . $r['id'] . '/feedback') ?>">
          <?= csrf_field() ?>

          <label class="field__label" for="rating-<?= (int) $r['id'] ?>-5">How was the handover?</label>
          <div class="rating-stars">
            <?php for ($n = 5; $n >= 1; $n--): ?>
              <input type="radio" required
                     id="rating-<?= (int) $r['id'] ?>-<?= $n ?>"
                     name="rating" value="<?= $n ?>">
              <label for="rating-<?= (int) $r['id'] ?>-<?= $n ?>"
                     title="<?= $n ?> out of 5">★</label>
            <?php endfor; ?>
          </div>

          <div class="field">
            <textarea class="field__textarea" name="comment" rows="2" maxlength="500"
                      placeholder="Anything you would like others to know about this donor? (optional)"></textarea>
          </div>

          <button class="btn btn--primary btn--sm">Submit review</button>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <h2 class="reuse-section">All my requests</h2>

  <?php if (!$requests): ?>
    <div class="card empty-state">
      <strong>You have not asked for anything yet</strong>
      <p>When you request an item from the reuse board, it will appear here.</p>
      <a class="btn btn--primary" href="<?= url('/reuse') ?>">Browse reuse items</a>
    </div>
  <?php else: ?>
    <div class="card">
      <table class="table">
        <thead>
          <tr><th>Item</th><th>Donor</th><th>Asked</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td>
              <div class="cell-item">
                <span class="cell-item__thumb">
                  <?php if ($r['image_id']): ?>
                    <img src="<?= url('/reuse/image/' . $r['image_id']) ?>" alt="">
                  <?php else: ?>
                    <?= icon('gift') ?>
                  <?php endif; ?>
                </span>
                <div>
                  <div class="cell-item__title"><?= e($r['title']) ?></div>
                  <div class="link-muted"><?= e($r['category']) ?> · <?= e($r['city']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <a href="<?= url('/reuse/donor/' . $r['donor_id']) ?>"><?= e($r['donor_name']) ?></a>
            </td>
            <td><?= e(time_ago($r['created_at'])) ?></td>
            <td>
              <span class="badge <?= $r['status'] === 'selected' ? 'badge--success' : ($r['status'] === 'pending' ? 'badge--pending' : 'badge--neutral') ?>">
                <?= e($states[$r['status']] ?? $r['status']) ?>
              </span>
              <?php if ($r['status'] === 'selected' && $r['item_status'] !== ITEM_DONATED): ?>
                <div class="link-muted">Arrange pickup with the donor.</div>
              <?php endif; ?>
            </td>
            <td>
              <div class="row-actions">
                <?php if ($r['item_deleted_at'] === null): ?>
                  <a class="btn btn--outline btn--sm" href="<?= url('/reuse/' . $r['item_id']) ?>">
                    View listing
                  </a>
                <?php endif; ?>
                <?php if ($r['feedback_id'] !== null): ?>
                  <span class="rating-given" title="You rated this <?= (int) $r['feedback_rating'] ?> out of 5">
                    <?= str_repeat('★', (int) $r['feedback_rating']) ?>
                  </span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
