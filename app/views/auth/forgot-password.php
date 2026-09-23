<?php require_once APP_PATH . '/views/partials/icons.php'; ?>

<div class="auth-card">
  <span class="auth-card__label">Forgot Password</span>

  <div class="auth-card__head">
    <h1>Reset Your Password</h1>
    <p>Enter your email address and we'll send you a link to reset your password.</p>
  </div>

  <form method="post" action="<?= url('/forgot-password') ?>">
    <?= csrf_field() ?>

    <div class="field <?= has_error('email') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="email">Email Address</label>
      <div class="control">
        <span class="control__icon"><?= icon('mail') ?></span>
        <input class="control__input" type="email" id="email" name="email"
               value="<?= old('email') ?>" placeholder="Enter your registered email"
               autocomplete="email" autofocus required>
      </div>
      <span class="field__error"><?= error_for('email') ?></span>
    </div>

    <button type="submit" class="btn btn--brand btn--block btn--lg">Send Reset Link</button>
  </form>

  <div class="or-split"><span>OR</span></div>

  <a class="btn btn--outline btn--block btn--lg" href="<?= url('/login') ?>">Back to Sign In</a>

  <?php if (!empty($devLink)): ?>
    <div class="dev-link">
      <strong>Development only - no mail is sent yet</strong>
      This link is also written to <code>storage/logs/app.log</code>.
      <a href="<?= e($devLink) ?>"><?= e($devLink) ?></a>
    </div>
  <?php endif; ?>
</div>
