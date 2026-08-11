<?php require_once APP_PATH . '/views/partials/icons.php'; ?>

<div class="auth-form auth-form--wide">
  <h1 class="auth-form__title">Create your organization account</h1>
  <p class="auth-form__sub">
    Already have an account? <a href="<?= url('/login') ?>">Sign in</a>
  </p>

  <form method="post" action="<?= url('/register/organization') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="auth-section">
      <span class="auth-section__icon"><?= icon('building') ?></span>
      <div>
        <strong>Organization Details</strong>
        <p>Tell us about your organization.</p>
      </div>
    </div>

    <div class="auth-grid">
      <div class="field <?= has_error('org_name') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="org_name">Organization Name <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('building') ?></span>
          <input class="control__input" id="org_name" name="org_name"
                 value="<?= old('org_name') ?>" placeholder="Enter organization name" autofocus>
        </div>
        <span class="field__error"><?= error_for('org_name') ?></span>
      </div>

      <div class="field <?= has_error('org_type') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="org_type">Organization Type <span class="req">*</span></label>
        <div class="control">
          <select class="control__input" id="org_type" name="org_type" required>
            <option value="" disabled <?= old('org_type') ? '' : 'selected' ?>>Select organization type</option>
            <?php foreach ($orgTypes as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= old('org_type') === $value ? 'selected' : '' ?>>
                <?= e($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="control__chevron"><?= icon('chevron-down') ?></span>
        </div>
        <span class="field__error"><?= error_for('org_type') ?></span>
      </div>

      <div class="field <?= has_error('reg_number') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="reg_number">Registration Number <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('clipboard') ?></span>
          <input class="control__input" id="reg_number" name="reg_number"
                 value="<?= old('reg_number') ?>" placeholder="Enter registration number">
        </div>
        <span class="field__error"><?= error_for('reg_number') ?></span>
      </div>

      <div class="field <?= has_error('email') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="email">Organization Email <span class="req">*</span></label>
        <div class="control">
          <span class="control__icon"><?= icon('mail') ?></span>
          <input class="control__input" type="email" id="email" name="email"
                 value="<?= old('email') ?>" placeholder="Enter organization email">
        </div>
        <span class="field__error"><?= error_for('email') ?></span>
      </div>
    </div>

    <div class="field <?= has_error('website') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="website">Website <span style="color:var(--muted-3);font-weight:400">(if available)</span></label>
      <div class="control">
        <span class="control__icon"><?= icon('globe') ?></span>
        <input class="control__input" id="website" name="website"
               value="<?= old('website') ?>" placeholder="https://www.example.org">
      </div>
      <span class="field__error"><?= error_for('website') ?></span>
    </div>

    <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="description">Short Description <span class="req">*</span></label>
      <div class="control">
        <textarea class="control__input" id="description" name="description" maxlength="300"
                  data-count-for="descCount"
                  placeholder="Briefly describe your organization, mission and the services you provide…"><?= old('description') ?></textarea>
      </div>
      <span class="control__count" id="descCount">0/300</span>
      <span class="field__error"><?= error_for('description') ?></span>
    </div>

    <?php
      $docLabel    = 'Organization Proof Documents';
      $docNote     = 'Please upload a valid document to verify your organization.';
      $docAccepted = ['Registration Certificate', 'Other Official Document'];
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
