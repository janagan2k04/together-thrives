<?php require_once APP_PATH . '/views/partials/icons.php'; ?>

<div class="auth-form auth-form--wide">
  <h1 class="auth-form__title">Grama Niladhari Registration</h1>
  <p class="auth-form__sub">
    Already have an account? <a href="<?= url('/login') ?>">Sign in</a>
  </p>

  <form method="post" action="<?= url('/register/gn') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="auth-section">
      <span class="auth-section__icon"><?= icon('user') ?></span>
      <div>
        <strong>Personal Information</strong>
        <p>Enter your details exactly as they appear on your NIC.</p>
      </div>
    </div>

    <div class="auth-grid">
      <div class="field <?= has_error('full_name') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="full_name">Full Name (as per NIC) <span class="req">*</span></label>
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
        <label class="field__label" for="phone">Office Phone Number <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('phone') ?></span>
          <input class="control__input" id="phone" name="phone"
                 value="<?= old('phone') ?>" placeholder="07XXXXXXXX">
        </div>
        <span class="field__error"><?= error_for('phone') ?></span>
      </div>

      <div class="field <?= has_error('nic') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="nic">National Identity Card No. <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('clipboard') ?></span>
          <input class="control__input" id="nic" name="nic"
                 value="<?= old('nic') ?>" placeholder="Enter your NIC number">
        </div>
        <span class="field__error"><?= error_for('nic') ?></span>
      </div>
    </div>

    <div class="auth-section">
      <span class="auth-section__icon"><?= icon('building') ?></span>
      <div>
        <strong>Grama Niladhari Details</strong>
        <p>The division you are appointed to serve.</p>
      </div>
    </div>

    <div class="auth-grid">
      <div class="field <?= has_error('district_id') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="district_id">District <span class="req">*</span></label>
        <div class="control">
          <select class="control__input" id="district_id" name="district_id" required>
            <option value="" disabled <?= old('district_id') ? '' : 'selected' ?>>Select your district</option>
            <?php foreach ($districts as $d): ?>
              <option value="<?= e($d['id']) ?>" <?= old('district_id') == $d['id'] ? 'selected' : '' ?>>
                <?= e($d['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="control__chevron"><?= icon('chevron-down') ?></span>
        </div>
        <span class="field__error"><?= error_for('district_id') ?></span>
      </div>

      <div class="field <?= has_error('ds_division') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="ds_division">Divisional Secretariat (DS Division)</label>
        <div class="control">
          <span class="control__icon"><?= icon('map-pin') ?></span>
          <input class="control__input" id="ds_division" name="ds_division"
                 value="<?= old('ds_division') ?>" placeholder="Enter your DS division">
        </div>
        <span class="field__error"><?= error_for('ds_division') ?></span>
      </div>

      <div class="field <?= has_error('gn_division') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="gn_division">Grama Niladhari Division <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('map-pin') ?></span>
          <input class="control__input" id="gn_division" name="gn_division"
                 value="<?= old('gn_division') ?>" placeholder="Enter your GN division">
        </div>
        <span class="field__error"><?= error_for('gn_division') ?></span>
      </div>
    </div>

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

    <div class="auth-section">
      <span class="auth-section__icon"><?= icon('file-check') ?></span>
      <div>
        <strong>Official Document</strong>
        <p>Please upload a valid document to verify your authorization.</p>
      </div>
    </div>

    <?php
      // the section heading above already names this field
      $docLabel    = '';
      $docNote     = '';
      $docAccepted = ['Government Appointment Letter', 'Other Official Document'];
      require APP_PATH . '/views/partials/document-upload.php';
    ?>

    <button type="submit" class="btn btn--auth">Create Account <?= icon('arrow-right') ?></button>
  </form>

  <p class="auth-secure"><?= icon('shield-check') ?> Your data is protected and secure with us.</p>
</div>
