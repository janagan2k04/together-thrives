<?php
/**
 * "Donation - Payment Success & Receipt" - frame 5 of Donation-User.svg.
 *
 * The screen a donor lands on after the sandbox gateway returns. Everything on
 * it comes from the `payments` row and the `fund_donations` row it settles;
 * week 3 renders the sample receipt because neither row is written yet.
 */
require_once APP_PATH . '/views/partials/icons.php';

$r = $receipt['request'];
?>

<section class="panel" style="max-width:760px;margin:0 auto var(--s-4)">
  <div class="don-success">
    <span class="don-success__tick"><?= icon('check') ?></span>
    <h1>Payment Successful</h1>
    <p>
      Thank you, <?= e($receipt['donor_first']) ?>. Your donation has been recorded and a receipt
      has been emailed to <?= e($receipt['donor_email']) ?>
    </p>
    <div class="don-success__amt">LKR <?= e(number_format($receipt['total'], 2)) ?></div>
    <span class="don-success__when">Transaction Completed · <?= e($receipt['paid_at']) ?></span>
  </div>
</section>

<section class="panel" style="max-width:760px;margin:0 auto var(--s-4)">
  <div class="panel__head">
    <?= icon('file-text') ?><h2>Donation Receipt</h2>
    <small>Receipt No. <?= e($receipt['receipt_no']) ?></small>
  </div>
  <div class="panel__body">
    <div class="don-money">
      <div class="don-money__row"><span>Transaction ID</span><b><?= e($receipt['transaction_ref']) ?></b></div>
      <div class="don-money__row"><span>Date &amp; Time</span><b><?= e($receipt['paid_at']) ?></b></div>
      <div class="don-money__row"><span>Payment Method</span><b><?= e($receipt['method']) ?></b></div>
      <div class="don-money__row"><span>Donor</span><b><?= e($receipt['donor']) ?></b></div>
      <div class="don-money__row"><span>Donation Request</span><b><?= e($r['title']) ?> (#<?= e($r['ref']) ?>)</b></div>
      <div class="don-money__row"><span>Recipient</span><b><?= e($receipt['recipient']) ?></b></div>
    </div>

    <div class="don-money" style="margin-top:var(--s-4)">
      <div class="don-money__row"><span>Donation amount</span><b>LKR <?= e(number_format($receipt['amount'], 2)) ?></b></div>
      <div class="don-money__row"><span>Transaction fee</span><b>LKR <?= e(number_format($receipt['fee'], 2)) ?></b></div>
      <div class="don-money__row don-money__row--total">
        <span>Total paid</span><b>LKR <?= e(number_format($receipt['total'], 2)) ?></b>
      </div>
    </div>
  </div>
</section>

<section class="panel" style="max-width:760px;margin:0 auto">
  <div class="panel__head"><?= icon('check-circle') ?><h2>What Happens Next</h2></div>
  <div class="panel__body">
    <div class="don-next">
      <div class="don-next__row">
        <span class="don-next__icon don-next__icon--num">1</span>
        <span>
          <strong>Funds transferred</strong>
          <small>The donated amount is transferred to the verified requester after payment settlement.</small>
        </span>
      </div>
      <div class="don-next__row">
        <span class="don-next__icon don-next__icon--num">2</span>
        <span>
          <strong>Relief distributed</strong>
          <small>The recipient distributes the funds to affected families in the <?= e($r['affected_area']) ?> division.</small>
        </span>
      </div>
      <div class="don-next__row">
        <span class="don-next__icon don-next__icon--num">3</span>
        <span>
          <strong>Impact report published</strong>
          <small>You are notified when the campaign closes and the impact report is available in My Activities.</small>
        </span>
      </div>
    </div>

    <div class="don-actions">
      <button class="btn btn--primary" type="button" onclick="window.print()">
        <?= icon('download') ?>Print or Save as PDF
      </button>
      <a class="btn btn--outline" href="<?= url('/my/activities') ?>">View My Donation History</a>
      <a class="btn btn--outline" href="<?= url('/donations') ?>">Back to Donation Requests</a>
    </div>
  </div>
</section>

