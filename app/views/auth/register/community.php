<?php require_once APP_PATH . '/views/partials/icons.php'; ?>

<div class="auth-form auth-form--wide">
  <h1 class="auth-form__title">Create your account</h1>
  <p class="auth-form__sub">
    Already have an account? <a href="<?= url('/login') ?>">Sign in</a>
  </p>

  <form method="post" action="<?= url('/register/community') ?>"
        enctype="multipart/form-data" style="margin-top:var(--s-8)">
    <?= csrf_field() ?>

    <div class="field <?= has_error('full_name') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="full_name">Full Name <span class="req">*</span></label>
      <div class="control">
        <span class="control__icon"><?= icon('user') ?></span>
        <input class="control__input" id="full_name" name="full_name"
               value="<?= old('full_name') ?>" placeholder="Enter your full name" autofocus>
      </div>
      <span class="field__error"><?= error_for('full_name') ?></span>
    </div>

    <div class="field <?= has_error('email') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="email">Email Address <span class="req">*</span></label>
      <div class="control">
        <span class="control__icon"><?= icon('mail') ?></span>
        <input class="control__input" type="email" id="email" name="email"
               value="<?= old('email') ?>" placeholder="Enter your email address">
      </div>
      <span class="field__error"><?= error_for('email') ?></span>
    </div>

    <div class="field <?= has_error('phone') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="phone">Phone Number <span class="req">*</span></label>
      <div class="control">
        <span class="control__icon"><?= icon('phone') ?></span>
        <input class="control__input" id="phone" name="phone"
               value="<?= old('phone') ?>" placeholder="07XXXXXXXX">
      </div>
      <span class="field__error"><?= error_for('phone') ?></span>
    </div>

    <?php
      $docLabel    = 'Upload NIC';
      $docNote     = 'Please upload your NIC to verify your identity.';
      $docAccepted = ['NIC front and back', 'Valid Passport or Driving Licence'];
      require APP_PATH . '/views/partials/document-upload.php';
    ?>

    <div class="field <?= has_error('password') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="password">Password <span class="req">*</span></label>
      <div class="control">
        <span class="control__icon"><?= icon('lock') ?></span>
        <input class="control__input" type="password" id="password" name="password"
               placeholder="Create a password" autocomplete="new-password">
        <button type="button" class="control__toggle" data-toggle-password
                aria-label="Show password"><?= icon('eye-off') ?></button>
      </div>
      <span class="field__hint">Must be at least 8 characters and include a letter and a number.</span>
      <span class="field__error"><?= error_for('password') ?></span>
    </div>

    <div class="field <?= has_error('password_confirm') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="password_confirm">Confirm Password <span class="req">*</span></label>
      <div class="control">
        <span class="control__icon"><?= icon('lock') ?></span>
        <input class="control__input" type="password" id="password_confirm" name="password_confirm"
               placeholder="Confirm your password" autocomplete="new-password">
        <button type="button" class="control__toggle" data-toggle-password
                aria-label="Show password"><?= icon('eye-off') ?></button>
      </div>
      <span class="field__error"><?= error_for('password_confirm') ?></span>
    </div>

    <div class="field">
      <label class="field__label">I am signing up as</label>
      <div class="role-radio">
        <span class="role-radio__opt">
          <span class="role-radio__box is-active">
            <span class="role-radio__dot"></span>
            <?= icon('user') ?>
            <span>Community User</span>
          </span>
        </span>
        <a class="role-radio__opt" href="<?= url('/register/gn') ?>">
          <span class="role-radio__box">
            <span class="role-radio__dot"></span>
            <?= icon('shield-check') ?>
            <span>Grama Niladhari</span>
          </span>
        </a>
        <a class="role-radio__opt" href="<?= url('/register/organization') ?>">
          <span class="role-radio__box">
            <span class="role-radio__dot"></span>
            <?= icon('building') ?>
            <span>Organization</span>
          </span>
        </a>
      </div>
    </div>

    <div class="field <?= has_error('terms') ? 'field--invalid' : '' ?>">
      <label class="check">
        <input type="checkbox" name="terms" value="1" <?= old('terms') ? 'checked' : '' ?>>
        <span class="check__box"><?= icon('check') ?></span>
        <span class="check__text">
          I agree to the <a href="<?= url('/about') ?>">Terms of Service</a>
          and <a href="<?= url('/about') ?>">Privacy Policy</a>
        </span>
      </label>
      <span class="field__error"><?= error_for('terms') ?></span>
    </div>

    <button type="submit" class="btn btn--auth">Create Account <?= icon('arrow-right') ?></button>
  </form>

  <p class="auth-secure"><?= icon('shield-check') ?> Your data is protected and secure with us.</p>
</div>
