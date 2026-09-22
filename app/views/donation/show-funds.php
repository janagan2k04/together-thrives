<?php
/**
 * "Donation - Fund Request Details (Community)" - frame 3 of Donation-User.svg.
 *
 * The community-facing detail for a **fund** request. Weeks 4-5: the request is
 * a real row. Make a Donation carries the chosen amount to the checkout screen;
 * nothing is charged, and `fund_donations` and `payments` have no writer yet.
 *
 * Two things the frame draws are gone rather than invented: the High Urgency
 * badge (no column), and the "How Funds Will Be Used" breakdown (no table -
 * `donation_requests` has a single `target_amount`). Both would have had to be
 * fabricated on every real request.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Donation\DonationRequest;

/** Two letters for the avatar, from whatever the name gives us. */
$initialsOf = static function (?string $name): string {
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last) ?: 'AD';
};



$r         = $request;
$initials  = $initialsOf($r['requester']);
$target    = (float) $r['target_amount'];
$collected = (float) $r['collected_amount'];
$remaining = max(0, $target - $collected);
$fill      = $target > 0 ? min(100, (int) round($collected / $target * 100)) : 0;
$daysLeft  = (int) $r['days_left'];

$requesterSub = Models\Donation\DonationRequest::REQUESTER_TYPES[$r['requester_type']]
    ?? $r['requester_type'];
?>

<div class="don-page">

<a class="back-link" href="<?= url('/donations') ?>"><?= icon('arrow-left') ?>Back to Donation Requests</a>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__body">
    <div class="row-between" style="align-items:flex-start">
      <div>
        <div style="display:flex;flex-wrap:wrap;gap:var(--s-2);margin-bottom:var(--s-3)">
          <span class="don-status don-status--brand">Fund Request</span>
          <?php if ($r['verified']): ?>
            <span class="don-status don-status--green"><?= icon('check') ?>Verified Request</span>
          <?php endif; ?>
        </div>
        <h1 style="font-size:var(--fs-2xl);font-weight:700;color:var(--ink)"><?= e($r['title']) ?></h1>
        <p class="muted" style="margin-top:5px">
          By <?= e($r['requester']) ?> · <?= e($r['district']) ?> District
          · Collection ends <?= e(format_date($r['ends_on'])) ?>
        </p>
        <p style="margin-top:var(--s-3);font-size:var(--fs-base);color:var(--ink-2)"><?= e($r['description']) ?></p>
      </div>
      <a class="btn btn--outline" href="<?= url('/messages') ?>">Share</a>
      <a class="btn btn--outline" href="<?= url('/report') ?>">Report</a>
    </div>
  </div>
</section>

<div class="split">
  <div class="stack">

    <section class="panel">
      <div class="panel__head"><?= icon('bar-chart') ?><h2>Fundraising Progress</h2></div>
      <div class="panel__body">
        <div class="row-between" style="align-items:flex-end">
          <div>
            <div class="don-raised"><b><?= e($fill) ?>%</b></div>
            <small class="muted">of target reached</small>
          </div>
          <div style="text-align:right">
            <strong style="font-size:var(--fs-xl);color:var(--ink)">LKR <?= e(number_format($collected)) ?></strong>
            <small style="display:block;color:var(--muted-3)">raised of LKR <?= e(number_format($target)) ?> target</small>
          </div>
        </div>

        <div class="don-progress" style="margin-top:var(--s-4)">
          <div class="don-progress__bar" style="width:<?= e($fill) ?>%"></div>
        </div>

        <div class="don-figures" style="grid-template-columns:repeat(3,minmax(0,1fr))">
          <div class="don-figure"><dt>Donors</dt><dd><?= e(number_format((int) $r['donors'])) ?></dd></div>
          <div class="don-figure"><dt>Still needed</dt><dd>LKR <?= e(number_format($remaining)) ?></dd></div>
          <div class="don-figure">
            <dt>Remaining</dt>
            <dd><?= $daysLeft >= 0 ? e($daysLeft) . ' days' : 'Closed' ?></dd>
          </div>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('file-text') ?><h2>About This Request</h2></div>
      <div class="panel__body">
        <p style="font-size:var(--fs-sm);font-weight:600;color:var(--brand)">Affected Area: <?= e($r['affected_area']) ?></p>
        <?php if (!empty($r['disaster_details'])): ?>
          <p style="margin-top:var(--s-3);font-size:var(--fs-base);color:var(--ink-2)"><?= e($r['disaster_details']) ?></p>
        <?php endif; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head">
        <?= icon('users') ?><h2>Recent Donors</h2>
        <small><?= e(number_format((int) $r['donors'])) ?> donors</small>
      </div>
      <div class="panel__body">
        <?php if (!$donors): ?>
          <p class="muted">No donations yet. Gifts appear here as they are made.</p>
        <?php else: ?>
        <div class="don-donors">
          <?php foreach ($donors as $d): ?>
            <div class="don-donor">
              <span class="don-avatar"><?= e($d['donor'] ? $initialsOf($d['donor']) : 'AD') ?></span>
              <span>
                <strong><?= e($d['donor'] ?: 'Anonymous Donor') ?></strong>
                <small><?= e(time_ago($d['created_at'])) ?></small>
              </span>
              <span class="don-donor__amt">
                <b><?= e(format_money((float) $d['amount'])) ?></b>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
            </div>
    </section>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><?= icon('gift') ?><h2>Make a Donation</h2></div>
      <div class="panel__body">
        <p class="muted" style="margin-bottom:var(--s-3)">Choose an amount to contribute</p>

        <form method="get" action="<?= url('/donations/' . $r['id'] . '/checkout') ?>">
          <div class="don-amounts">
            <?php foreach (DonationRequest::AMOUNTS as $amt): ?>
              <a class="<?= $amt === 2500 ? 'is-active' : '' ?>"
                 href="<?= url('/donations/' . $r['id'] . '/checkout?amount=' . $amt) ?>">
                LKR <?= e(number_format($amt)) ?>
              </a>
            <?php endforeach; ?>
            <a href="<?= url('/donations/' . $r['id'] . '/checkout') ?>">Custom</a>
          </div>

          <div class="field" style="margin-top:var(--s-4)">
            <label class="field__label" for="amount">Donation Amount (LKR)</label>
            <input class="field__input" id="amount" name="amount" type="number" min="100" value="2500">
          </div>

          <label class="don-check">
            <input type="checkbox" name="anonymous" value="1" checked>
            Donate anonymously
          </label>

          <?php if (!is_logged_in()): ?>
            <a class="btn btn--accent btn--block" style="margin-top:var(--s-4)"
               href="<?= url('/login') ?>">Sign in to Donate</a>
          <?php elseif ($r['status'] !== REQUEST_OPEN): ?>
            <button class="btn btn--outline btn--block" style="margin-top:var(--s-4)" type="button" disabled>
              This request is closed
            </button>
          <?php else: ?>
            <button class="btn btn--accent btn--block" style="margin-top:var(--s-4)">Donate Now</button>
          <?php endif; ?>
        </form>

        <p class="muted" style="margin-top:var(--s-3);font-size:var(--fs-xs)">
          Secured sandbox payment gateway. Funds are released only to the verified requester
          after successful payment.
        </p>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('building') ?><h2>Requester</h2></div>
      <div class="panel__body">
        <div class="don-who">
          <span class="don-who__pic"><?= e($initials) ?></span>
          <span>
            <strong><?= e($r['requester']) ?></strong>
            <?php if ($r['verified']): ?>
              <span class="don-who__verified"><?= icon('check') ?><?= e($requesterSub) ?></span>
            <?php else: ?>
              <small><?= e($requesterSub) ?></small>
            <?php endif; ?>
          </span>
        </div>
        <div class="don-contact">
          <div><?= icon('phone') ?><?= e($r['requester_phone'] ?: 'Not recorded') ?></div>
          <div><?= icon('mail') ?><?= e($r['requester_email']) ?></div>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('clock') ?><h2>Timeline</h2></div>
      <div class="panel__body">
        <dl class="don-dates">
          <div><dt>Started</dt><dd><?= e(format_date($r['starts_on'])) ?></dd></div>
          <div><dt>Ends</dt><dd><?= e(format_date($r['ends_on'])) ?></dd></div>
        </dl>
        <p class="don-note don-note--red" style="margin-top:var(--s-4)">
          <?= icon('clock') ?>
          <?php if ($daysLeft > 0): ?>
            <?= e($daysLeft) ?> Days Remaining
          <?php elseif ($daysLeft === 0): ?>
            Closes today
          <?php else: ?>
            Collection period has ended
          <?php endif; ?>
        </p>
      </div>
    </section>

    <?php if ($r['reviewed_at']): ?>
      <div class="don-note don-note--green">
        <?= icon('check-circle') ?><strong>Verified by Administrator</strong>
        <p>Checked on <?= e(format_date($r['reviewed_at'])) ?> before it was published to donors.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

</div>
