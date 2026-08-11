<?php
/**
 * Sandbox card checkout, shared by every module that takes money.
 *
 * The card fields are the ones the interim brief lists — cardholder, number,
 * expiry, CVV, billing address — and nothing is submitted anywhere. The inputs
 * carry `autocomplete="off"` so a real card is never remembered by the browser
 * on a form that cannot possibly need it.
 */
require_once APP_PATH . '/views/partials/icons.php';
?>

<a class="back-link" href="<?= url('/support') ?>"><?= icon('arrow-left') ?>Back</a>

<div class="page-head">
  <div class="page-head__text">
    <h1>Checkout</h1>
    <p><?= e($purpose) ?></p>
  </div>
  <span class="badge badge--pending">Sandbox — no card is charged</span>
</div>

<div class="split">
  <section class="panel">
    <div class="panel__head"><?= icon('lock') ?><h2>Payment details</h2></div>
    <div class="panel__body">
      <form method="post" action="<?= url('/checkout') ?>" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="amount" value="<?= e($amount) ?>">

        <div class="field <?= has_error('cardholder') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="cardholder">Cardholder Name</label>
          <input class="field__input" id="cardholder" name="cardholder" autocomplete="off"
                 placeholder="Name as printed on the card" value="<?= old('cardholder') ?>">
          <span class="field__error"><?= error_for('cardholder') ?></span>
        </div>

        <div class="field <?= has_error('card_number') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="card_number">Card Number</label>
          <input class="field__input" id="card_number" name="card_number" inputmode="numeric"
                 autocomplete="off" placeholder="4242 4242 4242 4242" value="<?= old('card_number') ?>">
          <span class="field__hint">Sandbox mode — use any 16 digits.</span>
          <span class="field__error"><?= error_for('card_number') ?></span>
        </div>

        <div class="field-row">
          <div class="field <?= has_error('expiry') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="expiry">Expiry</label>
            <input class="field__input" id="expiry" name="expiry" placeholder="MM / YY"
                   autocomplete="off" value="<?= old('expiry') ?>">
            <span class="field__error"><?= error_for('expiry') ?></span>
          </div>
          <div class="field <?= has_error('cvv') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="cvv">CVV</label>
            <input class="field__input" id="cvv" name="cvv" inputmode="numeric"
                   autocomplete="off" placeholder="123" value="<?= old('cvv') ?>">
            <span class="field__error"><?= error_for('cvv') ?></span>
          </div>
        </div>

        <div class="field <?= has_error('billing') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="billing">Billing Address</label>
          <input class="field__input" id="billing" name="billing"
                 placeholder="Street, city, postal code" value="<?= old('billing') ?>">
          <span class="field__error"><?= error_for('billing') ?></span>
        </div>

        <button class="btn btn--brand btn--lg btn--block" type="submit">
          Pay <?= e(format_money($amount)) ?>
        </button>
      </form>
    </div>
  </section>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><h2>Summary</h2></div>
      <div class="panel__body">
        <dl class="kv kv--single">
          <div><dt>Purpose</dt><dd><?= e($purpose) ?></dd></div>
          <div><dt>Amount</dt><dd><?= e(format_money($amount)) ?></dd></div>
          <div><dt>Processing fee</dt><dd><?= e(format_money(0)) ?></dd></div>
          <div><dt>Total</dt><dd><strong><?= e(format_money($amount)) ?></strong></dd></div>
          <div><dt>Gateway</dt><dd>PayHere sandbox</dd></div>
        </dl>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('shield-check') ?><h2>What happens next</h2></div>
      <div class="panel__body">
        <ul class="bullets">
          <li>You are shown a receipt with a reference number.</li>
          <li>Nothing is charged and no card details are stored.</li>
          <li>The real gateway is wired when the donation module lands.</li>
        </ul>
      </div>
    </section>
  </div>
</div>
