<?php require_once APP_PATH . '/views/partials/icons.php'; ?>

<div class="auth-card">
  <span class="auth-card__label">Reset Password</span>

  <div class="auth-card__head">
    <h1>Choose a New Password</h1>
    <p>Pick something you haven't used here before. The link you followed stops
       working as soon as this is saved.</p>
  </div>

  <form method="post" action="<?= url('/reset-password') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div class="field <?= has_error('password') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="password">New Password</label>
      <div class="control">
        <span class="control__icon"><?= icon('lock') ?></span>
        <input class="control__input" type="password" id="password" name="password"
               placeholder="At least 8 characters, with a letter and a number"
               autocomplete="new-password" autofocus required>
        <button type="button" class="control__toggle" data-toggle-password
                aria-label="Show password"><?= icon('eye-off') ?></button>
      </div>
      <span class="field__error"><?= error_for('password') ?></span>
    </div>

    <div class="field <?= has_error('password_confirm') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="password_confirm">Confirm New Password</label>
      <div class="control">
        <span class="control__icon"><?= icon('lock') ?></span>
        <input class="control__input" type="password" id="password_confirm" name="password_confirm"
               placeholder="Re-enter your new password"
               autocomplete="new-password" required>
        <button type="button" class="control__toggle" data-toggle-password
                aria-label="Show password"><?= icon('eye-off') ?></button>
      </div>
      <span class="field__error"><?= error_for('password_confirm') ?></span>
    </div>

    <button type="submit" class="btn btn--brand btn--block btn--lg">Update Password</button>
  </form>

  <div class="or-split"><span>OR</span></div>

  <a class="btn btn--outline btn--block btn--lg" href="<?= url('/login') ?>">Back to Sign In</a>
</div>
