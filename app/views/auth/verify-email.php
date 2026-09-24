<?php require_once APP_PATH . '/views/partials/icons.php'; ?>

<div class="auth-card">
  <div class="auth-success">
    <?php if ($confirmed): ?>
      <span class="auth-success__mark"><?= icon('check-circle') ?></span>
      <h1>Email Confirmed</h1>
      <p class="auth-note">
        Thanks - we've confirmed <strong><?= e($email) ?></strong>.
        <?php if ($awaitingAdmin): ?>
          Your account is still being reviewed by an administrator; we'll let you
          know as soon as that is done.
        <?php endif; ?>
      </p>
    <?php else: ?>
      <span class="auth-success__mark auth-success__mark--warn"><?= icon('x-circle') ?></span>
      <h1>Link No Longer Valid</h1>
      <p class="auth-note">
        This confirmation link is invalid or has expired. Confirmation links last
        48 hours - sign in and request a new one from your profile.
      </p>
    <?php endif; ?>
  </div>

  <div style="margin-top:var(--s-8)">
    <a class="btn btn--brand btn--block btn--lg"
       href="<?= url(is_logged_in() ? '/dashboard' : '/login') ?>">
      <?= is_logged_in() ? 'Go to Dashboard' : 'Go to Sign In' ?>
    </a>
    <div class="or-split"><span>OR</span></div>
    <a class="btn btn--outline btn--block btn--lg" href="<?= url('/') ?>">Back to Home</a>
  </div>
</div>
