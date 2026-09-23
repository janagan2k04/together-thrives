<?php require_once APP_PATH . '/views/partials/icons.php'; ?>

<div class="auth-form">
  <h1 class="auth-form__title">Sign in to your account</h1>
  <p class="auth-form__sub">
    Don't have an account? <a href="<?= url('/register') ?>">Sign up</a>
  </p>

  <form method="post" action="<?= url('/login') ?>" style="margin-top:var(--s-8)">
    <?= csrf_field() ?>

    <div class="field <?= has_error('email') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="email">Email Address</label>
      <div class="control">
        <span class="control__icon"><?= icon('mail') ?></span>
        <input class="control__input" type="email" id="email" name="email"
               value="<?= old('email') ?>" placeholder="Enter your email address"
               autocomplete="email" autofocus required>
      </div>
      <span class="field__error"><?= error_for('email') ?></span>
    </div>

    <div class="field <?= has_error('password') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="password">Password</label>
      <div class="control">
        <span class="control__icon"><?= icon('lock') ?></span>
        <input class="control__input" type="password" id="password" name="password"
               placeholder="Enter your password" autocomplete="current-password" required>
        <button type="button" class="control__toggle" data-toggle-password
                aria-label="Show password"><?= icon('eye-off') ?></button>
      </div>
      <span class="field__error"><?= error_for('password') ?></span>
    </div>

    <div class="auth-row auth-row--end">
      <a class="auth-link" href="<?= url('/forgot-password') ?>">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn--auth">Sign In <?= icon('arrow-right') ?></button>
  </form>
</div>
