<?php
/**
 * "Donation's Further Details - item" - frame 2 of Donation-User.svg.
 *
 * The donor-facing detail for an **item** request. The request, its item lines
 * and the reservations against it are all real rows: Reserve writes
 * `item_reservations` and moves `requested_items.qty_reserved`, and Undo on the
 * donor's own panel moves both back.
 *
 * Open to guests, which is why Reserve drops to Sign in when logged out.
 *
 * The frame's High Urgency badge, pickup address and operating hours are gone:
 * none of them has a column in the frozen schema, and a badge that is always
 * absent is worse than no badge.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Donation\ItemReservation;

/** Two letters for the avatar, from whatever the name gives us. */
$initialsOf = static function (?string $name): string {
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last) ?: 'AD';
};



$r        = $request;
$initials = $initialsOf($r['requester']);
$daysLeft = (int) $r['days_left'];

$requesterSub = Models\Donation\DonationRequest::REQUESTER_TYPES[$r['requester_type']]
    ?? $r['requester_type'];

$itemIcon = static fn(string $name): string => $name === 'Water & Drinks' ? 'droplet' : 'inbox';
?>

<div class="don-page">

<div class="crumbs">
  <a href="<?= url('/donations') ?>">Donations</a> ›
  <span class="is-current"><?= e($r['title']) ?></span>
</div>

<div class="don-head">
  <div class="don-head__text">
    <h1><?= e($r['title']) ?></h1>
    <p>
      By <?= e($r['requester']) ?> · <?= e($r['affected_area']) ?>
      · Collection ends <?= e(format_date($r['ends_on'])) ?>
    </p>
  </div>
  <div class="don-head__actions">
    <span class="don-status don-status--<?= e($tones[$r['status']]) ?>">
      <?= e($statuses[$r['status']]) ?>
    </span>
  </div>
</div>

<div class="split">
  <div class="stack">

    <section class="panel">
      <div class="panel__head"><?= icon('file-text') ?><h2>Disaster Context</h2></div>
      <div class="panel__body">
        <div class="don-photo">
          Disaster area photograph
          <span class="don-photo__tag">Affected Area: <?= e($r['district']) ?> District</span>
        </div>
        <p style="margin-top:var(--s-4);font-size:var(--fs-base);color:var(--ink-2)"><?= e($r['description']) ?></p>
        <?php if (!empty($r['disaster_details'])): ?>
          <p style="margin-top:var(--s-3);font-size:var(--fs-base);color:var(--ink-2)"><?= e($r['disaster_details']) ?></p>
        <?php endif; ?>
      </div>
    </section>

    <div class="split split--even">
      <section class="panel">
        <div class="panel__head"><?= icon('shield-check') ?><h2>Requester Info</h2></div>
        <div class="panel__body">
          <div class="don-who">
            <span class="don-who__pic"><?= e($initials) ?></span>
            <span>
              <strong><?= e($r['requester']) ?></strong>
              <small><?= e($requesterSub) ?></small>
              <?php if ($r['verified']): ?>
                <span class="don-who__verified"><?= icon('check-circle') ?>Verified Official</span>
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
            <div class="don-dates__arrow"><?= icon('arrow-right') ?></div>
            <div><dt>Ends</dt><dd class="is-end"><?= e(format_date($r['ends_on'])) ?></dd></div>
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
    </div>

    <?php if ($r['pickup_point']): ?>
      <section class="panel">
        <div class="panel__head"><?= icon('map-pin') ?><h2>Collection Point</h2></div>
        <div class="panel__body">
          <strong style="display:block;font-size:var(--fs-base);color:var(--ink)"><?= e($r['pickup_point']) ?></strong>
          <p class="muted" style="margin-top:4px"><?= e($r['affected_area']) ?>, <?= e($r['city']) ?></p>
        </div>
      </section>
    <?php endif; ?>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel__head">
        <?= icon('inbox') ?><h2>Items Needed</h2>
      </div>
      <div class="panel__body">
        <?php if (!$items): ?>
          <div class="empty-state">No item lines on this request.</div>
        <?php else: ?>
          <div class="don-need">
            <?php foreach ($items as $it): ?>
              <div class="don-need__row">
                <span class="don-need__icon"><?= icon($itemIcon($it['item_name'])) ?></span>
                <span>
                  <strong><?= e($it['item_name']) ?></strong>
                  <small><?= e($it['qty_required']) ?> <?= e($it['unit']) ?> requested</small>
                </span>
                <span class="don-need__side">
                  <em><?= e($it['qty_reserved']) ?> reserved</em>
                  <?php if (!is_logged_in()): ?>
                    <a class="btn btn--accent" href="<?= url('/login') ?>">Sign in</a>
                  <?php elseif ($r['status'] !== REQUEST_OPEN): ?>
                    <button class="btn btn--outline" type="button" disabled>Closed</button>
                  <?php else: ?>
                    <form method="post" action="<?= url('/donations/' . $r['id'] . '/reserve') ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="requested_item_id" value="<?= e($it['id']) ?>">
                      <input type="hidden" name="quantity" value="1">
                      <button class="btn btn--accent">Reserve</button>
                    </form>
                  <?php endif; ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>

          <p class="muted" style="margin-top:var(--s-4);text-align:center">
            Showing all <?= count($items) ?> item line<?= count($items) === 1 ? '' : 's' ?>.
          </p>
        <?php endif; ?>
      </div>
    </section>

    <?php /* What this donor has promised, and the two ways to take it back.
              Drawn only when there is something to take back - a panel that is
              always empty is noise on everyone else's screen. */ ?>
    <?php if ($mine): ?>
      <section class="panel">
        <div class="panel__head"><?= icon('inbox') ?><h2>Your reservations</h2></div>
        <div class="panel__body" style="padding:0">
          <table class="table">
            <thead><tr><th>Item</th><th>Quantity</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($mine as $v): ?>
              <tr>
                <td><strong style="color:var(--ink)"><?= e($v['item_name']) ?></strong></td>
                <td><?= e($v['quantity']) ?> <?= e($v['unit']) ?></td>
                <td>
                  <span class="badge badge--<?= e(ItemReservation::TONES[$v['status']] ?? 'neutral') ?>">
                    <?= e(ItemReservation::STATUSES[$v['status']] ?? $v['status']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($v['status'] === 'reserved'): ?>
                    <form method="post" class="inline"
                          action="<?= url('/donations/' . $r['id'] . '/reservations/' . $v['id'] . '/cancel') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn--outline"
                              data-confirm="Withdraw this reservation? The quantity goes back on the request.">
                        Undo
                      </button>
                    </form>
                  <?php else: ?>
                    <?php /* Received already - the goods arrived, so there is
                              nothing to give back and no button to press. */ ?>
                    <span class="muted">Received</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php $open = array_filter($mine, fn($v) => $v['status'] === 'reserved'); ?>
        <?php if (count($open) > 1): ?>
          <div class="panel__body">
            <form method="post" action="<?= url('/donations/' . $r['id'] . '/leave') ?>">
              <?= csrf_field() ?>
              <button class="btn btn--danger"
                      data-confirm="Leave this request? Every reservation you have not delivered goes back.">
                Leave this request
              </button>
            </form>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="panel">
      <div class="panel__head"><?= icon('help-circle') ?><h2>How reserving works</h2></div>
      <div class="panel__body">
        <ul class="don-ticks">
          <li><?= icon('check') ?>Reserve the quantity you can actually bring.</li>
          <li><?= icon('check') ?>Deliver it to the collection point within the period.</li>
          <li><?= icon('check') ?>The requester confirms receipt, which closes your reservation.</li>
          <li><?= icon('check') ?>Changed your mind? Undo a reservation any time before it is received.</li>
        </ul>
      </div>
    </section>
  </div>
</div>

</div>
