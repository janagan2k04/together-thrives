<?php
/**
 * Adapted from the organization form — the Figma file has no blood bank frame,
 * but the route and the blood_banks table both exist.
 */
require_once APP_PATH . '/views/partials/icons.php';
?>

<div class="auth-form auth-form--wide">
  <h1 class="auth-form__title">Create your blood bank account</h1>
  <p class="auth-form__sub">
    Already have an account? <a href="<?= url('/login') ?>">Sign in</a>
  </p>

  <form method="post" action="<?= url('/register/blood-bank') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="auth-section">
      <span class="auth-section__icon"><?= icon('droplet') ?></span>
      <div>
        <strong>Blood Bank Details</strong>
        <p>Tell us about your blood bank.</p>
      </div>
    </div>

    <div class="auth-grid">
      <div class="field <?= has_error('bank_name') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="bank_name">Blood Bank Name <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('droplet') ?></span>
          <input class="control__input" id="bank_name" name="bank_name"
                 value="<?= old('bank_name') ?>" placeholder="Enter blood bank name" autofocus>
        </div>
        <span class="field__error"><?= error_for('bank_name') ?></span>
      </div>

      <div class="field <?= has_error('license_no') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="license_no">Licence Number <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('clipboard') ?></span>
          <input class="control__input" id="license_no" name="license_no"
                 value="<?= old('license_no') ?>" placeholder="Enter licence number">
        </div>
        <span class="field__error"><?= error_for('license_no') ?></span>
      </div>

      <div class="field <?= has_error('hospital_name') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="hospital_name">Hospital <span style="color:var(--muted-3);font-weight:400">(if attached to one)</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('building') ?></span>
          <input class="control__input" id="hospital_name" name="hospital_name"
                 value="<?= old('hospital_name') ?>" placeholder="Enter hospital name">
        </div>
        <span class="field__error"><?= error_for('hospital_name') ?></span>
      </div>

      <div class="field <?= has_error('phone') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="phone">Hotline Number <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('phone') ?></span>
          <input class="control__input" id="phone" name="phone"
                 value="<?= old('phone') ?>" placeholder="07XXXXXXXX">
        </div>
        <span class="field__error"><?= error_for('phone') ?></span>
      </div>
    </div>

    <div class="field <?= has_error('email') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="email">Blood Bank Email <span class="req">*</span></label>
      <div class="control">
        <span class="control__icon"><?= icon('mail') ?></span>
        <input class="control__input" type="email" id="email" name="email"
               value="<?= old('email') ?>" placeholder="Enter blood bank email">
      </div>
      <span class="field__error"><?= error_for('email') ?></span>
    </div>

    <?php
      $docLabel    = 'Blood Bank Proof Documents';
      $docNote     = 'Please upload a valid document to verify your blood bank.';
      $docAccepted = ['Blood Bank Licence', 'Hospital Authorization Letter'];
      require APP_PATH . '/views/partials/document-upload.php';
    ?>

    <div class="auth-section">
      <span class="auth-section__icon"><?= icon('lock') ?></span>
      <div>
        <strong>Account Security</strong>
        <p>You will use this email and password to sign in.</p>
      </div>
    </div>

    <div class="auth-grid">
      <div class="field <?= has_error('password') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="password">Password <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('lock') ?></span>
          <input class="control__input" type="password" id="password" name="password"
                 placeholder="Create a password" autocomplete="new-password">
          <button type="button" class="control__toggle" data-toggle-password
                  aria-label="Show password"><?= icon('eye-off') ?></button>
        </div>
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
    </div>
    <span class="field__hint" style="display:block;margin:calc(var(--s-5) * -1) 0 var(--s-5)">
      Must be at least 8 characters and include a letter and a number.
    </span>

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
