<?php
/**
 * "Post a Critical Blood Request" from Blood-User.svg.
 *
 * The right rail mirrors what has been typed on the left. It is static here -
 * making it live needs JS, and week 3 is HTML/CSS - so it shows the defaults
 * the frame shows and the values are labelled as a preview.
 */
require_once APP_PATH . '/views/partials/icons.php';

$steps = [
    'Admin verifies your request details.',
    'Nearby donors with a matching blood group are notified.',
    'Donors complete a medical screening form.',
    "Eligible donors' contact details are shared with you.",
    'You contact the donor directly to arrange donation.',
];
?>

<a class="blood-back" href="<?= url($backUrl) ?>"><?= icon('arrow-left') ?><?= e($backLabel) ?></a>

<div class="blood-head">
  <div class="blood-head__text">
    <h1>Post a Critical Blood Request</h1>
    <p>Submit an urgent request. Once verified, eligible donors nearby are notified immediately.</p>
  </div>
</div>

<form method="post" action="<?= url('/blood/critical') ?>">
  <?= csrf_field() ?>

  <div class="split">
    <div class="stack">

      <section class="sec">
        <div class="sec__head">
          <div><strong>Patient Information</strong><small>Who the blood is needed for</small></div>
        </div>
        <div class="sec__body">
          <div class="field-row">
            <div class="field <?= has_error('patient_name') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="patient_name">Patient Name *</label>
              <input class="field__input" id="patient_name" name="patient_name"
                     placeholder="Enter patient's full name" value="<?= old('patient_name') ?>">
              <span class="field__error"><?= error_for('patient_name') ?></span>
            </div>
            <div class="field <?= has_error('contact_phone') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="contact_phone">Contact Number *</label>
              <input class="field__input" id="contact_phone" name="contact_phone"
                     placeholder="07X XXX XXXX" value="<?= old('contact_phone') ?>">
              <span class="field__error"><?= error_for('contact_phone') ?></span>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('hospital') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="hospital">Hospital *</label>
              <input class="field__input" id="hospital" name="hospital"
                     placeholder="e.g. National Hospital of Sri Lanka" value="<?= old('hospital') ?>">
              <span class="field__error"><?= error_for('hospital') ?></span>
            </div>
            <div class="field <?= has_error('city_id') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="city_id">District *</label>
              <select class="field__select" id="city_id" name="city_id">
                <option value="">Select district</option>
                <?php foreach ($cities as $c): ?>
                  <option value="<?= e($c['id']) ?>" <?= old('city_id') === (string) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="field__hint">Donors are matched to this district.</span>
              <span class="field__error"><?= error_for('city_id') ?></span>
            </div>
            <div class="field">
              <label class="field__label" for="ward">Ward / Bed No.</label>
              <input class="field__input" id="ward" name="ward"
                     placeholder="e.g. Ward 12, Bed 4" value="<?= old('ward') ?>">
            </div>
          </div>

          <div class="field <?= has_error('address') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="address">Full Address *</label>
            <input class="field__input" id="address" name="address"
                   placeholder="e.g. 23 Deshamanya H K Dharmadasa Mawatha, Colombo 02"
                   value="<?= old('address') ?>">
            <span class="field__error"><?= error_for('address') ?></span>
            <span class="field__hint">Street address of the hospital, so a donor can get there without asking.</span>
          </div>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Blood Requirement</strong><small>What is needed and by when</small></div>
        </div>
        <div class="sec__body">
          <div class="field-row">
            <div class="field <?= has_error('blood_group') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="blood_group">Blood Group *</label>
              <select class="field__select" id="blood_group" name="blood_group">
                <option value="">Select group</option>
                <?php foreach ($groups as $g): ?>
                  <option value="<?= e($g) ?>" <?= old('blood_group') === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                <?php endforeach; ?>
              </select>
              <span class="field__error"><?= error_for('blood_group') ?></span>
            </div>
            <div class="field <?= has_error('units_needed') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="units_needed">Units Needed *</label>
              <input class="field__input" id="units_needed" name="units_needed" type="number" min="1"
                     value="<?= old('units_needed', '1') ?>">
              <span class="field__error"><?= error_for('units_needed') ?></span>
            </div>
            <div class="field <?= has_error('priority') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="priority">Priority *</label>
              <select class="field__select" id="priority" name="priority">
                <?php foreach ($priorities as $value => $label): ?>
                  <option value="<?= e($value) ?>" <?= old('priority') === $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="field__error"><?= error_for('priority') ?></span>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('needed_by') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="needed_by">Required Before *</label>
              <input class="field__input" id="needed_by" name="needed_by" type="datetime-local"
                     value="<?= old('needed_by') ?>">
              <span class="field__error"><?= error_for('needed_by') ?></span>
            </div>
            <div class="field">
              <label class="field__label" for="requested_by">Requested By</label>
              <input class="field__input" id="requested_by" name="requested_by"
                     placeholder="e.g. Janindu Perera (Family)"
                     value="<?= old('requested_by', $user['full_name'] ?? '') ?>">
              <span class="field__hint">
                Shown to donors so they know who they are helping. Stored against your account.
              </span>
            </div>
          </div>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Reason for Request</strong><small>Helps donors understand the urgency</small></div>
        </div>
        <div class="sec__body">
          <div class="field <?= has_error('notes') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="notes">Description *</label>
            <textarea class="field__textarea" id="notes" name="notes" maxlength="1000"
                      placeholder="Describe why the blood is needed and by when."><?= old('notes') ?></textarea>
            <span class="field__error"><?= error_for('notes') ?></span>
          </div>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="sec">
        <div class="sec__head">
          <div><strong>Request Summary</strong><small>Review before submitting</small></div>
        </div>
        <div class="sec__body">
          <div class="mini-list">
            <div><span>Blood group</span><span class="group-tag"><?= e(old('blood_group') ?: 'Not set') ?></span></div>
            <div><span>Units needed</span><span><?= e(old('units_needed') ?: '1') ?> units</span></div>
            <div><span>Priority</span><span style="color:var(--red);font-weight:600">
              <?= e($priorities[old('priority')] ?? 'Urgent') ?></span></div>
            <div><span>Hospital</span><span><?= e(old('hospital') ?: 'Not set') ?></span></div>
            <div><span>Address</span><span><?= e(old('address') ?: 'Not set') ?></span></div>
            <div><span>Needed before</span><span><?= e(old('needed_by') ?: 'Not set') ?></span></div>
          </div>
          <p class="muted" style="margin-top:var(--s-3)">
            Filled in from your last submission - it refreshes when you submit.
          </p>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Verification Required</strong></div>
        </div>
        <div class="sec__body">
          <div class="alert alert--info alert--stack">
            <strong>Not visible yet</strong>
            <p style="margin-top:4px">
              An administrator fact-checks your request before nearby donors are notified.
            </p>
          </div>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>How This Works</strong></div>
        </div>
        <div class="sec__body">
          <ol class="step-list">
            <?php foreach ($steps as $s): ?><li><?= e($s) ?></li><?php endforeach; ?>
          </ol>
        </div>
      </section>
    </div>
  </div>

  <div class="form-foot">
    <p>Critical requests are reviewed immediately and notified by district to matching donors.</p>
    <a class="btn btn--outline" href="<?= url($backUrl) ?>">Cancel</a>
    <button class="btn btn--blood" type="submit">Submit Request</button>
  </div>
</form>
