<?php
/**
 * "Blood donation Form" from Blood-User.svg - the medical screening every donor
 * completes, reached from a camp or from a critical request.
 *
 * The questions come from `screening_questions`; each carries the
 * answer that defers a donor, which is what the controller scores on submit.
 * Every question is required - a half-answered medical form is worse than none,
 * so a blank is an error rather than an assumed "no".
 */
require_once APP_PATH . '/views/partials/icons.php';
?>

<a class="blood-back" href="<?= url($back) ?>"><?= icon('arrow-left') ?>Back to Blood Donation</a>

<div class="screen-hero">
  <span class="screen-hero__ico"><?= icon('clipboard') ?></span>
  <div>
    <h1>Blood Donation Screening Form</h1>
    <p>Please answer all questions honestly. Your health and safety is our top priority.</p>
  </div>
</div>

<p class="muted" style="margin-bottom:var(--s-4)">
  <?= $context === 'critical' ? 'Responding to' : 'Registering for' ?>
  <strong style="color:var(--ink)"><?= e($forWhat) ?></strong>
</p>

<form method="post" action="<?= url($action) ?>">
  <?= csrf_field() ?>

  <section class="sec" style="margin-bottom:var(--s-5)">
    <div class="sec__head">
      <div><strong>Personal Information</strong></div>
    </div>
    <div class="sec__body">
      <div class="field-row">
        <div class="field <?= has_error('full_name') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="full_name">Full Name *</label>
          <input class="field__input" id="full_name" name="full_name" placeholder="Enter your full name"
                 value="<?= old('full_name', $user['full_name'] ?? '') ?>">
          <span class="field__error"><?= error_for('full_name') ?></span>
        </div>
        <div class="field <?= has_error('nic') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="nic">NIC / Passport No. *</label>
          <input class="field__input" id="nic" name="nic" placeholder="Enter NIC or Passport number"
                 value="<?= old('nic', $user['nic'] ?? '') ?>">
          <span class="field__error"><?= error_for('nic') ?></span>
        </div>
        <div class="field <?= has_error('date_of_birth') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="date_of_birth">Date of Birth *</label>
          <input class="field__input" id="date_of_birth" name="date_of_birth" type="date"
                 value="<?= old('date_of_birth') ?>">
          <span class="field__error"><?= error_for('date_of_birth') ?></span>
        </div>
      </div>

      <div class="field-row">
        <div class="field <?= has_error('gender') ? 'field--invalid' : '' ?>">
          <span class="field__label">Gender *</span>
          <div class="radio-row">
            <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label): ?>
              <label>
                <input type="radio" name="gender" value="<?= e($value) ?>"
                       <?= old('gender') === $value ? 'checked' : '' ?>><?= e($label) ?>
              </label>
            <?php endforeach; ?>
          </div>
          <span class="field__error"><?= error_for('gender') ?></span>
        </div>
        <div class="field <?= has_error('phone') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="phone">Phone Number *</label>
          <input class="field__input" id="phone" name="phone" placeholder="07X XXX XXXX"
                 value="<?= old('phone', $user['phone'] ?? '') ?>">
          <span class="field__error"><?= error_for('phone') ?></span>
        </div>
        <div class="field">
          <label class="field__label" for="email">Email Address</label>
          <input class="field__input" id="email" name="email" type="email" placeholder="Enter your email"
                 value="<?= old('email', $user['email'] ?? '') ?>">
        </div>
      </div>

      <div class="field-row">
        <div class="field <?= has_error('address') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="address">Address *</label>
          <input class="field__input" id="address" name="address" placeholder="Enter your full address"
                 value="<?= old('address') ?>">
          <span class="field__error"><?= error_for('address') ?></span>
        </div>
        <div class="field <?= has_error('district_id') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="district_id">District *</label>
          <select class="field__select" id="district_id" name="district_id">
            <option value="">Select your district</option>
            <?php foreach ($districts as $d): ?>
              <option value="<?= e($d['id']) ?>" <?= old('district_id') === (string) $d['id'] ? 'selected' : '' ?>>
                <?= e($d['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="field__error"><?= error_for('district_id') ?></span>
        </div>
      </div>
    </div>
  </section>

  <section class="sec" style="margin-bottom:var(--s-5)">
    <div class="sec__head">
      <div><strong>Donation Details</strong></div>
    </div>
    <div class="sec__body">
      <?php /* Donation Type - Whole Blood or Apheresis - came off on 2026-09-18.
                Nothing stored it: ScreeningService::answersFor() writes the
                questionnaire and nothing else, so the radio was validated and
                then dropped. A field with no column does not get drawn. */ ?>
      <div class="split split--even">
        <div class="field">
          <label class="field__label" for="last_donation">Last Donation Date</label>
          <input class="field__input" id="last_donation" name="last_donation" type="date"
                 value="<?= old('last_donation') ?>">
          <span class="field__hint">Leave blank if you have never donated.</span>
        </div>
        <div class="field">
          <label class="field__label" for="donation_count">If you have donated before, how many times?</label>
          <input class="field__input" id="donation_count" name="donation_count" type="number" min="0"
                 placeholder="Enter number of times" value="<?= old('donation_count') ?>">
        </div>
      </div>
    </div>
  </section>

  <section class="sec">
    <div class="sec__head">
      <div>
        <strong>Pre-Screening Questions</strong>
        <small><?= e(count($questions)) ?> questions · all required</small>
      </div>
    </div>
    <div class="sec__body">
      <?php foreach ($questions as $q): $key = \Models\Blood\ScreeningQuestion::fieldName($q); ?>
        <div class="q-row <?= has_error($key) ? 'is-invalid' : '' ?>">
          <span class="q-row__text"><?= e($q['question']) ?></span>
          <span class="q-row__answers">
            <label>
              <input type="radio" name="<?= e($key) ?>" value="yes" <?= old($key) === 'yes' ? 'checked' : '' ?>>Yes
            </label>
            <label>
              <input type="radio" name="<?= e($key) ?>" value="no" <?= old($key) === 'no' ? 'checked' : '' ?>>No
            </label>
          </span>
        </div>
      <?php endforeach; ?>

      <?php if ($questions && has_error(\Models\Blood\ScreeningQuestion::fieldName($questions[0]))): ?>
        <p class="field__error" style="margin-top:var(--s-3)">
          Please answer every question - a blank is not treated as a "no".
        </p>
      <?php endif; ?>
    </div>
  </section>

  <div class="form-foot">
    <p>Your answers are scored automatically. You will be told immediately whether you can donate.</p>
    <a class="btn btn--outline" href="<?= url($back) ?>">Cancel</a>
    <button class="btn btn--blood" type="submit">Submit Screening Form</button>
  </div>
</form>
