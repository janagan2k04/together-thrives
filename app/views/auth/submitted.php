<?php require_once APP_PATH . '/views/partials/icons.php'; ?>

<div class="card" style="padding:var(--s-16) var(--s-12);border-radius:var(--r-xl);box-shadow:var(--shadow-md)">
  <div class="auth-success">
    <span class="auth-success__mark"><?= icon('check-circle') ?></span>
    <h1>Registration Submitted Successfully!</h1>
    <p class="auth-note">
      Thank you for registering with TogetherThrives. Our team will review your
      submission shortly.
    </p>
  </div>

  <div class="next-steps">
    <strong>What happens next?</strong>
    <div class="grid grid--2">
      <div class="next-step">
        <span class="next-step__icon next-step__icon--blue"><?= icon('file-check') ?></span>
        <strong>We verify your details</strong>
        <p>Our team reviews your documents within 2-3 business days.</p>
      </div>
      <div class="next-step">
        <span class="next-step__icon next-step__icon--amber"><?= icon('bell') ?></span>
        <strong>You will be notified</strong>
        <p>We'll email you as soon as your account is approved.</p>
      </div>
    </div>
  </div>

  <div style="margin-top:var(--s-10);text-align:center">
    <a class="btn btn--outline btn--lg" href="<?= url('/') ?>">Back to Home</a>
    <a class="btn btn--brand btn--lg" href="<?= url('/login') ?>">Go to Sign In</a>
  </div>
</div>
