<?php
/**
 * "Donation - Fund Payment Checkout" - frame 4 of Donation-User.svg.
 *
 * Sandbox only. `payments.gateway` defaults to `payhere_sandbox` in the frozen
 * schema, so this screen is the sandbox checkout in weeks 4-5 too - the
 * difference then is that a `fund_donations` row is written first and the
 * gateway callback settles it. Week 3 validates and redirects to the receipt.
 *
 * Card fields are deliberately not named after anything storable: no card
 * number, expiry or CVV ever reaches the database, in week 3 or later.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Donation\DonationRequest;


$r     = $request;
$fee   = 125;
$total = $amount + $fee;
?>

<a class="back-link" href="<?= url('/donations/' . $r['id']) ?>"><?= icon('arrow-left') ?>Back to Donation Request</a>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__body">
    <h1 style="font-size:var(--fs-2xl);font-weight:700;color:var(--ink)">Secure Payment</h1>
    <p class="muted" style="margin-top:4px">
      <?= e($r['title']) ?> · <?= e($r['requester']) ?> · Request #<?= e($r['ref']) ?>
    </p>
  </div>
</section>

<form method="post" action="<?= url('/donations/' . $r['id'] . '/checkout') ?>">
  <?= csrf_field() ?>

  <div class="split">
    <div class="stack">

      <section class="panel">
        <div class="panel__head"><?= icon('gift') ?><h2>Donation Amount</h2></div>
        <div class="panel__body">
          <div class="don-amounts">
            <?php foreach (DonationRequest::AMOUNTS as $amt): ?>
              <a class="<?= (int) $amount === $amt ? 'is-active' : '' ?>"
                 href="<?= url('/donations/' . $r['id'] . '/checkout?amount=' . $amt) ?>">
                LKR <?= e(number_format($amt)) ?>
              </a>
            <?php endforeach; ?>
            <a href="<?= url('/donations/' . $r['id'] . '/checkout?amount=0') ?>">Other</a>
          </div>

          <div class="field <?= has_error('amount') ? 'field--invalid' : '' ?>" style="margin-top:var(--s-4)">
            <label class="field__label" for="amount">Custom Amount (LKR) *</label>
            <input class="field__input" id="amount" name="amount" type="number" min="100" step="1"
                   value="<?= old('amount', (string) $amount) ?>">
            <span class="field__error"><?= error_for('amount') ?></span>
          </div>

          <label class="don-check">
            <input type="checkbox" name="cover_fee" value="1" checked>
            Add LKR <?= e($fee) ?> to cover the payment gateway transaction fee
          </label>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('user') ?><h2>Donor Details</h2></div>
        <div class="panel__body">
          <div class="field-row">
            <div class="field <?= has_error('full_name') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="full_name">Full Name *</label>
              <input class="field__input" id="full_name" name="full_name"
                     placeholder="Janindu Perera" value="<?= old('full_name') ?>">
              <span class="field__error"><?= error_for('full_name') ?></span>
            </div>
            <div class="field <?= has_error('email') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="email">Email Address *</label>
              <input class="field__input" id="email" name="email" type="email"
                     placeholder="nimal.perera@gmail.com" value="<?= old('email') ?>">
              <span class="field__error"><?= error_for('email') ?></span>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('phone') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="phone">Phone Number *</label>
              <input class="field__input" id="phone" name="phone"
                     placeholder="071 234 5678" value="<?= old('phone') ?>">
              <span class="field__error"><?= error_for('phone') ?></span>
            </div>
            <div class="field">
              <label class="field__label" for="nic">NIC Number (optional)</label>
              <input class="field__input" id="nic" name="nic"
                     placeholder="199012345678" value="<?= old('nic') ?>">
              <span class="field__hint">Kept for the donor's own receipt only - no column stores it.</span>
            </div>
          </div>

          <label class="don-check">
            <input type="checkbox" name="is_anonymous" value="1">
            Show my name as "Anonymous Donor" on the public donor list
          </label>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('lock') ?><h2>Payment Method</h2></div>
        <div class="panel__body">
          <div class="don-methods">
            <?php foreach (DonationRequest::PAY_METHODS as $i => $method): ?>
              <label>
                <input type="radio" name="method" value="<?= e($method) ?>" <?= $i === 0 ? 'checked' : '' ?>>
                <span><?= e($method) ?></span>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="field">
            <label class="field__label" for="card_number">Card Number</label>
            <input class="field__input" id="card_number" inputmode="numeric"
                   placeholder="4242 4242 4242 4242" autocomplete="off">
          </div>

          <div class="field-row">
            <div class="field">
              <label class="field__label" for="card_name">Name on Card</label>
              <input class="field__input" id="card_name" placeholder="NIMAL PERERA" autocomplete="off">
            </div>
            <div class="field">
              <label class="field__label" for="card_exp">Expiry Date</label>
              <input class="field__input" id="card_exp" placeholder="MM / YY" autocomplete="off">
            </div>
            <div class="field">
              <label class="field__label" for="card_cvv">CVV</label>
              <input class="field__input" id="card_cvv" placeholder="•••" autocomplete="off">
            </div>
          </div>

          <p class="don-note">
            <?= icon('lock') ?>Payments are processed through the PayHere sandbox gateway over an
            encrypted connection. TogetherThrives never stores your card details - these four
            fields have no <code>name</code> attribute, so they are not even posted.
          </p>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="panel">
        <div class="panel__head"><?= icon('file-text') ?><h2>Summary</h2></div>
        <div class="panel__body">
          <div class="don-money">
            <div class="don-money__row"><span>Donation amount</span><b>LKR <?= e(number_format($amount)) ?></b></div>
            <div class="don-money__row"><span>Transaction fee</span><b>LKR <?= e(number_format($fee)) ?></b></div>
            <div class="don-money__row don-money__row--total">
              <span>Total to pay</span><b>LKR <?= e(number_format($total)) ?></b>
            </div>
          </div>

          <dl class="kv kv--single" style="margin-top:var(--s-4)">
            <div><dt>Request</dt><dd><?= e($r['title']) ?></dd></div>
            <div><dt>Recipient</dt><dd><?= e($r['requester']) ?></dd></div>
            <div><dt>Reference</dt><dd><?= e($r['ref']) ?></dd></div>
          </dl>
        </div>
      </section>

      <div class="don-note don-note--green">
        <?= icon('shield-check') ?><strong>Funds are held until release</strong>
        <p>The amount reaches <?= e($r['requester']) ?> only after the administrator verifies the
        request has been fulfilled.</p>
      </div>
    </div>
  </div>

  <div class="don-form-foot">
    <p>
      <label class="don-check">
        <input type="checkbox" name="agree" value="1" checked>
        I agree that this donation is voluntary and non-refundable
      </label>
    </p>
    <a class="btn btn--outline" href="<?= url('/donations/' . $r['id']) ?>">Cancel</a>
    <button class="btn btn--primary">Pay LKR <?= e(number_format($total, 2)) ?></button>
  </div>
</form>

