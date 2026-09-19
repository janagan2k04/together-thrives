<?php
/**
 * "My Activities" - everything this member has taken part in, across all four
 * modules.
 *
 * No Figma frame covers this screen (the design draws the donor's side only),
 * so it follows the table pattern already used by views/reuse/requests.php.
 *
 * The counters at the top are about reuse, which is the half with a lifecycle
 * worth counting. The three sections below it are read-only summaries that link
 * back into their own module, and each one is left out entirely when the member
 * has nothing in it - four empty tables would bury the one that is not empty.
 *
 * The review form appears on exactly one condition: the donor selected you and
 * then confirmed the handover. Anything looser would let people rate donors
 * they never actually met.
 */
require_once APP_PATH . '/views/partials/icons.php';

$states = \Models\Reuse\ItemRequest::STATUSES;

/*
 * `/reuse` carries block_role:blood_bank,organization, so those two roles have
 * no reuse half to show - not an empty one, none. Drawing it would put a
 * "Browse reuse items" button in front of a role that gets 403 on it, which is
 * the dead-link shape the week 6 navigation audit was run to catch.
 */
$usesReuse = !role_is(ROLE_BLOOD_BANK, ROLE_ORGANIZATION);
$anything  = $requests || $cleanups || $camps || $giving;

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
    <?php /* /reuse is blocked for blood banks and organizations, so this button
             was a link straight to a 403 for both - the same shape $usesReuse
             already guards the section below against. */ ?>
    <?php if ($usesReuse): ?>
      <a class="btn btn--outline" href="<?= url('/reuse') ?>">Browse items</a>
    <?php endif; ?>
  </div>

  <?php if ($usesReuse): ?>
  <div class="stat-strip">
    <?php foreach ($cells as [$label, $value]): ?>
      <div class="stat-strip__cell">
        <strong><?= (int) $value ?></strong>
        <span><?= e($label) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$anything): ?>
    <div class="card empty-state">
      <strong>Nothing here yet</strong>
      <p>
        Campaigns you join, camps you register for and donations you make all appear on this page.
      </p>
      <?php if (role_is(ROLE_GN)): ?>
        <a class="btn btn--primary" href="<?= url('/donations') ?>">See donation requests</a>
      <?php else: ?>
        <a class="btn btn--primary" href="<?= url('/cleanup') ?>">Find a cleanup campaign</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

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

  <?php if ($usesReuse && $requests): ?>
  <h2 class="reuse-section">Reuse items I asked for</h2>
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
                <a class="btn btn--outline btn--sm" href="<?= url('/reuse/requests/' . $r['id'] . '/messages') ?>">
                  Message
                </a>
                <?php if ($r['status'] === 'selected' && $r['item_status'] === ITEM_RESERVED): ?>
                  <?php /* The donor has "Mark as handed over" on their queue;
                           this is the same close from the other side. */ ?>
                  <form method="post" action="<?= url('/reuse/requests/' . $r['id'] . '/received') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn btn--primary btn--sm"
                            data-confirm="Confirm you have collected this item?">
                      I have received it
                    </button>
                  </form>
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

  <?php /* ---------------- cleanup ---------------- */ ?>
  <?php if ($cleanups): ?>
    <h2 class="reuse-section">Cleanup campaigns I joined</h2>
    <div class="card">
      <table class="table">
        <thead>
          <tr><th>Campaign</th><th>Organiser</th><th>Date</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($cleanups as $c): ?>
          <tr>
            <td>
              <div class="cell-item__title"><?= e($c['title']) ?></div>
              <div class="link-muted"><?= e($c['meeting_point']) ?> · <?= e($c['city']) ?></div>
            </td>
            <td><?= e($c['organiser']) ?></td>
            <td><?= e(format_date($c['event_date'])) ?></td>
            <td>
              <?php if ($c['checked_in_at']): ?>
                <span class="badge badge--success">Attended</span>
              <?php elseif ($c['status'] === 'confirmed'): ?>
                <span class="badge badge--green">Confirmed</span>
              <?php else: ?>
                <span class="badge badge--pending">Awaiting approval</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="row-actions">
                <a class="btn btn--outline btn--sm" href="<?= url('/cleanup/' . $c['event_id']) ?>">View</a>
                <?php if ($c['event_status'] === EVENT_COMPLETED): ?>
                  <a class="btn btn--outline btn--sm" href="<?= url('/cleanup/' . $c['event_id'] . '/feedback') ?>">
                    Give feedback
                  </a>
                <?php elseif (!$c['checked_in_at'] && role_is(ROLE_COMMUNITY)): ?>
                  <form method="post" class="inline"
                        action="<?= url('/cleanup/' . $c['event_id'] . '/leave') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="from" value="activities">
                    <button class="btn btn--danger btn--sm"
                            data-confirm="Leave this campaign? Your place is given back to the organiser.">
                      Leave
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php /* ---------------- blood ---------------- */ ?>
  <?php if ($camps || $donated): ?>
    <h2 class="reuse-section">Blood donation</h2>
    <div class="card">
      <table class="table">
        <thead>
          <tr><th>Camp</th><th>Date</th><th>Screening</th><th></th></tr>
        </thead>
        <tbody>
        <?php
        // Camps this member has donated at, so a registration that became a
        // donation is shown as one rather than as two rows saying different
        // things.
        $donatedAt = array_map('intval', array_column($donated, 'campaign_id'));
        ?>
        <?php foreach ($camps as $c): ?>
          <tr>
            <td>
              <div class="cell-item__title"><?= e($c['title']) ?></div>
              <div class="link-muted"><?= e($c['venue']) ?> · <?= e($c['city']) ?></div>
            </td>
            <td><?= e(format_date($c['campaign_date'])) ?></td>
            <td>
              <?php if (in_array((int) $c['campaign_id'], $donatedAt, true)): ?>
                <span class="badge badge--success">Donated</span>
              <?php else: ?>
                <span class="badge badge--<?= e(\Models\Blood\ScreeningResponse::TONES[$c['result']]) ?>">
                  <?= e(\Models\Blood\ScreeningResponse::RESULTS[$c['result']]) ?>
                </span>
              <?php endif; ?>
            </td>
            <td>
              <div class="row-actions">
                <a class="btn btn--outline btn--sm" href="<?= url('/blood/' . $c['campaign_id']) ?>">View</a>
                <?php if (in_array((int) $c['campaign_id'], $donatedAt, true)): ?>
                  <a class="btn btn--outline btn--sm" href="<?= url('/blood/certificate/' . $c['campaign_id']) ?>">
                    Certificate
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php /* ---------------- donation ---------------- */ ?>
  <?php if ($giving): ?>
    <h2 class="reuse-section">Donations I made</h2>
    <div class="card">
      <table class="table">
        <thead>
          <tr><th>Request</th><th>What I gave</th><th>When</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($giving as $g): ?>
          <tr>
            <td>
              <div class="cell-item__title"><?= e($g['title']) ?></div>
              <div class="link-muted"><?= $g['kind'] === 'items' ? 'Item request' : 'Fund request' ?></div>
            </td>
            <td><?= e($g['what']) ?></td>
            <td><?= e(time_ago($g['at'])) ?></td>
            <td><span class="badge badge--<?= e($g['tone']) ?>"><?= e($g['status']) ?></span></td>
            <td>
              <div class="row-actions">
                <a class="btn btn--outline btn--sm" href="<?= url('/donations/' . $g['request_id']) ?>">View</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
