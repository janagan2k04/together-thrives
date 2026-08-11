<?php
/** "GN - Report a Disaster" — four grouped cards, exactly as the frame orders them. */
require_once APP_PATH . '/views/partials/icons.php';
?>

<a class="back-link" href="<?= url('/gn/dashboard') ?>"><?= icon('arrow-left') ?>Back to Dashboard</a>

<div class="page-head">
  <div class="page-head__text">
    <h1>Report a Disaster</h1>
    <p>Submit a verified disaster report for your GN division. Every report must include
       supporting evidence before it can be submitted.</p>
  </div>
</div>

<form method="post" action="<?= url('/gn/disasters') ?>" enctype="multipart/form-data" class="stack">
  <?= csrf_field() ?>

  <section class="panel">
    <div class="panel__head">
      <?= icon('bug') ?>
      <h2>Disaster Details</h2>
      <small>What happened and when</small>
    </div>
    <div class="panel__body">
      <div class="field <?= has_error('disaster_type') ? 'field--invalid' : '' ?>">
        <span class="field__label">Disaster Type *</span>
        <div class="chip-row" style="margin-bottom:0">
          <?php foreach ($types as $value => $label): ?>
            <label class="amount" style="width:auto">
              <input type="radio" name="disaster_type" value="<?= e($value) ?>"
                     <?= old('disaster_type', 'flood') === $value ? 'checked' : '' ?>>
              <span style="padding:8px 16px"><?= e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <span class="field__error"><?= error_for('disaster_type') ?></span>
      </div>

      <div class="field-row">
        <div class="field <?= has_error('title') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="title">Report Title *</label>
          <input class="field__input" id="title" name="title"
                 placeholder="e.g. Flood in Kelaniya Area" value="<?= old('title') ?>">
          <span class="field__error"><?= error_for('title') ?></span>
        </div>
        <div class="field <?= has_error('occurred_at') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="occurred_at">Date &amp; Time of Occurrence *</label>
          <input class="field__input" id="occurred_at" name="occurred_at" type="datetime-local"
                 value="<?= old('occurred_at') ?>">
          <span class="field__error"><?= error_for('occurred_at') ?></span>
        </div>
      </div>

      <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="description">Description *</label>
        <textarea class="field__textarea" id="description" name="description"
                  placeholder="What happened, which areas are affected, and what people need."><?= old('description') ?></textarea>
        <span class="field__error"><?= error_for('description') ?></span>
      </div>
    </div>
  </section>

  <section class="panel">
    <div class="panel__head">
      <?= icon('map-pin') ?>
      <h2>Location</h2>
      <small>Where the disaster occurred</small>
    </div>
    <div class="panel__body">
      <div class="field-row">
        <div class="field <?= has_error('district_id') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="district_id">District *</label>
          <select class="field__select" id="district_id" name="district_id">
            <option value="">Select a district</option>
            <?php foreach ($districts as $d): ?>
              <option value="<?= e($d['id']) ?>" <?= old('district_id') === (string) $d['id'] ? 'selected' : '' ?>>
                <?= e($d['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="field__error"><?= error_for('district_id') ?></span>
        </div>
        <div class="field <?= has_error('gn_division') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="gn_division">GN Division *</label>
          <input class="field__input" id="gn_division" name="gn_division"
                 placeholder="e.g. Kelaniya - 272C" value="<?= old('gn_division') ?>">
          <span class="field__error"><?= error_for('gn_division') ?></span>
        </div>
        <div class="field">
          <label class="field__label" for="landmark">Nearest Landmark</label>
          <input class="field__input" id="landmark" name="landmark"
                 placeholder="e.g. Kelaniya Temple Road" value="<?= old('landmark') ?>">
        </div>
      </div>

      <div class="alert alert--info">
        Location is matched to your GN division so nearby volunteers, donors and
        organisations are notified automatically.
      </div>
    </div>
  </section>

  <section class="panel">
    <div class="panel__head">
      <?= icon('bar-chart') ?>
      <h2>Severity &amp; Impact</h2>
      <small>Helps prioritise the response</small>
    </div>
    <div class="panel__body">
      <div class="field <?= has_error('severity') ? 'field--invalid' : '' ?>">
        <span class="field__label">Severity Level *</span>
        <div class="chip-row" style="margin-bottom:0">
          <?php foreach ($severities as $value => $label): ?>
            <label class="amount" style="width:auto">
              <input type="radio" name="severity" value="<?= e($value) ?>"
                     <?= old('severity', 'medium') === $value ? 'checked' : '' ?>>
              <span style="padding:8px 20px"><?= e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <span class="field__error"><?= error_for('severity') ?></span>
      </div>

      <div class="field-row">
        <div class="field">
          <label class="field__label" for="families">Affected Families</label>
          <input class="field__input" id="families" name="families" type="number" min="0"
                 value="<?= old('families') ?>">
        </div>
        <div class="field">
          <label class="field__label" for="displaced">People Displaced</label>
          <input class="field__input" id="displaced" name="displaced" type="number" min="0"
                 value="<?= old('displaced') ?>">
        </div>
        <div class="field <?= has_error('contact_phone') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="contact_phone">Contact Number *</label>
          <input class="field__input" id="contact_phone" name="contact_phone"
                 placeholder="07X XXX XXXX" value="<?= old('contact_phone') ?>">
          <span class="field__error"><?= error_for('contact_phone') ?></span>
        </div>
      </div>
    </div>
  </section>

  <section class="panel">
    <div class="panel__head">
      <?= icon('upload-cloud') ?>
      <h2>Evidence</h2>
      <small>Required before submission</small>
    </div>
    <div class="panel__body">
      <label class="dropzone" for="photos">
        <?= icon('upload-cloud') ?>
        <span>Click to browse, or drop photographs here</span>
        <small>PNG, JPG or JPEG up to 5MB each</small>
        <input type="file" id="photos" name="photos[]" accept=".png,.jpg,.jpeg" multiple>
      </label>
      <p class="muted" style="margin-top:var(--s-3)">
        Photographs are geo-checked against the reported GN division during verification.
      </p>
    </div>
  </section>

  <div class="page-head__actions">
    <a class="btn btn--outline" href="<?= url('/gn/disasters') ?>">Cancel</a>
    <button class="btn btn--primary btn--lg" type="submit">Submit Report</button>
  </div>
</form>
