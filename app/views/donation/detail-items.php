<?php
/**
 * "Donation - View Details · Items (Org)" - frame 3 of Donation-Org.svg.
 *
 * The requester's own view of an item request, and where its writes start:
 * Edit and Cancel Request (this module's DELETE - a soft delete plus
 * `status = cancelled`, the shape CLAUDE.md sets out), and confirming each
 * delivery on the Reserved by Donors table below - which moves
 * `requested_items.qty_received`.
 *
 * Confirmation is per reservation rather than one button for the whole request:
 * the requester needs to see what they are confirming, and two donors promising
 * the same item do not arrive on the same day.
 *
 * The frame's Disaster Type, Affected People, Risk Level, contact person and
 * notes panels are gone - none has a column in the frozen schema, and this is
 * the requester's own record, where an always-blank field is pure noise.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Donation\DonationRequest;
use Models\Donation\ItemReservation;

$r         = $request;
$needed    = array_sum(array_column($items, 'qty_required'));
$received  = array_sum(array_column($items, 'qty_received'));
$cancelled = $r['deleted_at'] !== null;

?>

<a class="back-link" href="<?= url('/my/donations') ?>"><?= icon('arrow-left') ?>Back to Donation Requests</a>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__body">
    <div class="row-between" style="align-items:flex-start">
      <div>
        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:var(--s-2)">
          <h1 style="font-size:var(--fs-2xl);font-weight:700;color:var(--ink)"><?= e($r['title']) ?></h1>
          <span class="don-status don-status--amber">Relief Items</span>
          <span class="don-status don-status--<?= e($tones[$r['status']]) ?>">
            <?= e($statuses[$r['status']]) ?>
          </span>
        </div>
        <p class="muted" style="margin-top:6px">
          Request ID <?= e($r['ref']) ?> · Requested on <?= e(format_date($r['created_at'])) ?> ·
          <?= e($r['affected_area']) ?>
        </p>
      </div>

      <?php if (!$cancelled): ?>
        <a class="btn btn--outline" href="<?= url('/my/donations/' . $r['id'] . '/edit') ?>">Edit Request</a>

        <form method="post" action="<?= url('/my/donations/' . $r['id']) ?>" style="display:inline">
          <?= csrf_field() ?><?= method_field('DELETE') ?>
          <button class="btn btn--danger" data-confirm="Cancel this request? Donors who reserved items are notified.">
            Cancel Request
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($cancelled): ?>
  <div class="don-note don-note--amber" style="margin-bottom:var(--s-5)">
    <?= icon('help-circle') ?>
    This request was cancelled. It stays here for your records and is no longer shown to donors.
  </div>
<?php elseif ($r['status'] === MOD_REJECTED && $r['reject_reason']): ?>
  <div class="don-note don-note--red" style="margin-bottom:var(--s-5)">
    <?= icon('help-circle') ?><strong>Not approved:</strong> <?= e($r['reject_reason']) ?>
  </div>
<?php endif; ?>

<div class="don-stats">
  <div class="don-stat don-stat--amber">
    <span class="don-stat__icon"><?= icon('inbox') ?></span>
    <span><strong><?= e(number_format($needed)) ?></strong><small>Items requested</small></span>
  </div>
  <div class="don-stat don-stat--green">
    <span class="don-stat__icon"><?= icon('check-circle') ?></span>
    <span><strong><?= e(number_format($received)) ?></strong><small>Items received</small></span>
  </div>
  <div class="don-stat don-stat--blue">
    <span class="don-stat__icon"><?= icon('users') ?></span>
    <span><strong><?= e($r['donors']) ?></strong><small>Donors contributed</small></span>
  </div>
</div>

<div class="split">
  <div class="stack">

    <section class="don-sec">
      <div class="don-sec__head">
        <span class="don-sec__num">1.</span>
        <strong>Donation Requirements</strong>
        <small>Quantities update automatically as donors reserve and deliver items.</small>
      </div>
      <div class="don-sec__body" style="padding:0">
        <?php if (!$items): ?>
          <div class="empty-state">No item lines on this request.</div>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr><th>Item</th><th>Needed</th><th>Reserved</th><th>Received</th></tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td>
                    <strong style="color:var(--ink)"><?= e($it['item_name']) ?></strong>
                    <small style="display:block;color:var(--muted-3)"><?= e($it['unit']) ?></small>
                  </td>
                  <td><?= e($it['qty_required']) ?></td>
                  <td><?= e($it['qty_reserved']) ?></td>
                  <td style="color:var(--green-dark);font-weight:700"><?= e($it['qty_received']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </section>

    <section class="don-sec">
      <div class="don-sec__head">
        <span class="don-sec__num">2.</span>
        <strong>Reserved by Donors</strong>
        <small>Confirm each delivery when the items reach you.</small>
      </div>
      <div class="don-sec__body" style="padding:0">
        <?php if (!$reservations): ?>
          <div class="empty-state">Nobody has reserved anything yet.</div>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr><th>Donor</th><th>Item</th><th>Quantity</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($reservations as $v): ?>
                <tr>
                  <td><strong style="color:var(--ink)"><?= e($v['donor']) ?></strong></td>
                  <td><?= e($v['item_name']) ?></td>
                  <td><?= e($v['quantity']) ?> <?= e($v['unit']) ?></td>
                  <td>
                    <span class="badge badge--<?= e(ItemReservation::TONES[$v['status']] ?? 'neutral') ?>">
                      <?= e(ItemReservation::STATUSES[$v['status']] ?? $v['status']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($v['status'] !== 'confirmed'): ?>
                      <form method="post" class="inline"
                            action="<?= url('/my/donations/' . $r['id'] . '/reservations/' . $v['id'] . '/confirm') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn--outline" type="submit">Confirm received</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </section>

    <section class="don-sec">
      <div class="don-sec__head">
        <span class="don-sec__num">3.</span>
        <strong>Situation</strong>
      </div>
      <div class="don-sec__body">
        <dl class="kv" style="grid-template-columns:repeat(3,minmax(0,1fr))">
          <div><dt>Affected Area</dt><dd><?= e($r['affected_area']) ?></dd></div>
          <div><dt>District</dt><dd><?= e($r['district']) ?></dd></div>
          <div>
            <dt>Posted by</dt>
            <dd><?= e(DonationRequest::REQUESTER_TYPES[$r['requester_type']] ?? $r['requester_type']) ?></dd>
          </div>
        </dl>

        <dl class="kv kv--single" style="margin-top:var(--s-5)">
          <div><dt>Summary</dt><dd><?= e($r['description']) ?></dd></div>
          <?php if (!empty($r['disaster_details'])): ?>
            <div><dt>Detailed report</dt><dd><?= e($r['disaster_details']) ?></dd></div>
          <?php endif; ?>
        </dl>
      </div>
    </section>
  </div>

  <div class="stack">
    <section class="don-sec">
      <div class="don-sec__head">
        <span class="don-sec__num">4.</span>
        <strong>Collection &amp; Logistics</strong>
      </div>
      <div class="don-sec__body">
        <div class="don-contact" style="margin-top:0">
          <div>
            <?= icon('calendar') ?>
            <span>
              <small style="display:block;color:var(--muted-3)">Collection period</small>
              <?= e(format_date($r['starts_on'])) ?> - <?= e(format_date($r['ends_on'])) ?>
            </span>
          </div>
          <div>
            <?= icon('map-pin') ?>
            <span>
              <small style="display:block;color:var(--muted-3)">Pickup point</small>
              <?= e($r['pickup_point'] ?: 'Not set') ?>
            </span>
          </div>
          <div>
            <?= icon('phone') ?>
            <span>
              <small style="display:block;color:var(--muted-3)">Contact number</small>
              <?= e($r['requester_phone'] ?: 'Not recorded on your account') ?>
            </span>
          </div>
        </div>
      </div>
    </section>

  </div>
</div>

