<?php
/**
 * "Reuse - Donor Public Profile" frame from UI/Reuse/Reuse.svg.
 *
 * The frame shows a rating, review list and handover rate. Those come from
 * `reuse_feedback`, which nobody has written to yet, so the review panel has a
 * real empty state rather than invented numbers.
 */
require_once APP_PATH . '/views/partials/icons.php';

$initials = mb_strtoupper(mb_substr($donor['full_name'], 0, 1));
$cells = [
    ['Items Donated',           $stats['donated']],
    ['Currently Listed',        $stats['listed']],
    ['Requests Received',       $stats['received']],
    ['Disaster-Affected Helped', $stats['disaster_helped']],
];
?>

<div class="reuse-page">

  <a class="item-back" href="<?= url('/reuse') ?>"><?= icon('arrow-left') ?> Back to Reuse Items</a>

  <div class="donor-card">
    <span class="donor-card__avatar"><?= e($initials) ?></span>
    <div>
      <div class="donor-card__name">
        <?= e($donor['full_name']) ?>
        <span class="donor-card__verified"><?= icon('shield-check') ?> Verified Donor</span>
      </div>
      <div class="donor-card__meta">
        <?php if ($rating['reviews'] > 0): ?>
          ★ <?= e($rating['average']) ?> (<?= (int) $rating['reviews'] ?> reviews) &nbsp;·&nbsp;
        <?php endif; ?>
        Member since <?= e(format_date($donor['created_at'], 'F Y')) ?>
      </div>
    </div>
  </div>

  <div class="stat-strip">
    <?php foreach ($cells as [$label, $value]): ?>
      <div class="stat-strip__cell">
        <strong><?= (int) $value ?></strong>
        <span><?= e($label) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="item-detail">
    <div>
      <h2 class="reuse-section">Active Listings (<?= count($listings) ?>)</h2>
      <?php if (!$listings): ?>
        <div class="card empty-state">
          <strong>Nothing listed right now</strong>
          <p>This member has no items available at the moment.</p>
        </div>
      <?php else: ?>
        <div class="item-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr))">
          <?php foreach ($listings as $item): ?>
            <?php require APP_PATH . '/views/reuse/_card.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h2 class="reuse-section">Reviews from Recipients</h2>
      <?php if (!$reviews): ?>
        <div class="card empty-state">
          <strong>No reviews yet</strong>
          <p>Recipients can rate a donor once an item has been handed over.</p>
        </div>
      <?php else: ?>
        <div class="card">
          <?php foreach ($reviews as $review): ?>
            <div class="donor-card__meta" style="margin-bottom: var(--s-4)">
              <strong><?= e($review['recipient_name']) ?></strong>
              — <?= str_repeat('★', (int) $review['rating']) ?>
              <span class="link-muted"><?= e(time_ago($review['created_at'])) ?></span>
              <p><?= e($review['comment'] ?: '') ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <aside>
      <div class="guide-card">
        <div class="guide-card__head"><?= icon('shield-check') ?> Verification</div>
        <ul>
          <li><?= $donor['email_verified_at'] ? 'Email address verified' : 'Email address not confirmed yet' ?></li>
          <li><?= $donor['phone'] ? 'Mobile number on file' : 'No mobile number on file' ?></li>
          <li><?= $donor['nic_document_path'] ? 'NIC document submitted' : 'No NIC document submitted' ?></li>
          <li>Account in good standing</li>
        </ul>
      </div>

      <div class="guide-card" style="margin-top: var(--s-4)">
        <div class="guide-card__head"><?= icon('help-circle') ?> Safety Reminder</div>
        <p class="page-header__sub">
          Always arrange handover in a public place. Never share bank details or
          make payments for reuse items — all listings are free.
        </p>
      </div>
    </aside>
  </div>
</div>
