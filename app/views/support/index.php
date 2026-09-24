<?php
/**
 * "Support the System" from Comm_User-Homepage.svg - a fund donation to the
 * platform itself, not to a relief request. Submitting hands off to the sandbox
 * checkout at /checkout.
 */
require_once APP_PATH . '/views/partials/icons.php';

$reasons = [
    ['shield-check', 'Keep the platform free for everyone'],
    ['bar-chart',    'Improve features and user experience'],
    ['lock',         'Ensure data security and reliability'],
    ['life-buoy',    'Support our community outreach programs'],
];

$faq = [
    ['Where does the money go?', 'Hosting, SMS alerts during disasters, and the sandbox payment gateway fees. TogetherThrives is run by volunteers and takes no commission from any relief donation.'],
    ['Is this the same as donating to a relief request?', 'No. A relief donation goes to a specific Grama Niladhari or organization through the Donations module. This one supports the platform.'],
    ['Can I get a receipt?', 'Yes - the checkout screen issues one for every contribution.'],
];
?>
<div class="page-wrap">

  <div class="page-head">
    <div class="page-head__text">
      <h1>Support</h1>
      <p>Help keep TogetherThrives running for the communities that rely on it.</p>
    </div>
  </div>

  <div class="split">
    <section class="panel hero-panel">
      <div class="hero-panel__head">
        <span class="hero-panel__icon"><?= icon('life-buoy') ?></span>
        <div>
          <h2>Fund Donation to Our System</h2>
          <p>
            TogetherThrives is a non-profit platform built to serve communities across Sri Lanka.
            Your financial support helps us maintain and improve the platform for everyone.
          </p>
        </div>
      </div>

      <div class="hero-panel__inner">
        <h3 style="font-size:var(--fs-base);font-weight:700;margin-bottom:var(--s-4)">Why Your Donation Matters</h3>
        <div class="reasons">
          <?php foreach ($reasons as [$ico, $text]): ?>
            <div><?= icon($ico) ?><span><?= e($text) ?></span></div>
          <?php endforeach; ?>
        </div>

        <?php if (is_logged_in()): ?>
          <form method="post" action="<?= url('/support/donate') ?>" style="margin-top:var(--s-6)">
            <?= csrf_field() ?>

            <h3 style="font-size:var(--fs-base);font-weight:700;margin-bottom:var(--s-3)">Choose Donation Amount</h3>
            <div class="amount-row">
              <?php foreach ($amounts as $a): ?>
                <label class="amount">
                  <input type="radio" name="amount" value="<?= e($a) ?>" <?= $a === 2500 ? 'checked' : '' ?>>
                  <span><?= icon('life-buoy') ?>LKR <?= e(number_format($a)) ?></span>
                </label>
              <?php endforeach; ?>
              <label class="amount">
                <input type="radio" name="amount" value="other">
                <span>Other Amount</span>
              </label>
            </div>

            <div class="field <?= has_error('amount') ? 'field--invalid' : '' ?>" style="margin-top:var(--s-4);max-width:280px">
              <label class="field__label" for="other_amount">Other amount (LKR)</label>
              <input class="field__input" id="other_amount" name="other_amount" type="number" min="100" placeholder="e.g. 750">
              <span class="field__error"><?= error_for('amount') ?></span>
            </div>

            <div class="row-between" style="margin-top:var(--s-5)">
              <span><span class="badge badge--neutral">Sandbox gateway - no money moves</span></span>
              <button class="btn btn--brand btn--lg" type="submit"><?= icon('life-buoy') ?>Donate Securely</button>
            </div>
          </form>
        <?php else: ?>
          <p class="muted" style="margin-top:var(--s-5)">Sign in to support the platform.</p>
          <a class="btn btn--brand btn--lg" style="margin-top:var(--s-3)" href="<?= url('/login') ?>">Sign in</a>
        <?php endif; ?>
      </div>
    </section>

    <div class="stack">
      <section class="panel">
        <div class="panel__head"><h2>Common questions</h2></div>
        <div class="panel__body stack">
          <?php foreach ($faq as [$q, $a]): ?>
            <div>
              <strong style="display:block;font-size:var(--fs-sm)"><?= e($q) ?></strong>
              <p class="muted" style="margin-top:4px"><?= e($a) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </div>

</div>
