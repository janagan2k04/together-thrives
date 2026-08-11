<?php
/** "Report" from Comm_User-Homepage.svg — report a problem with the platform. */
require_once APP_PATH . '/views/partials/icons.php';

$areas = [
    'general'  => 'General / not sure',
    'account'  => 'My account or sign-in',
    'reuse'    => 'Reuse items',
    'cleanup'  => 'Cleanup / volunteering',
    'donation' => 'Item or fund donation',
    'blood'    => 'Blood donation',
    'content'  => 'Inappropriate or false content',
];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Report a Problem</h1>
    <p>Found a bug or something that's not working? Let us know so we can fix it.</p>
  </div>
</div>

<div class="split">
  <section class="panel">
    <div class="panel__head">
      <?= icon('help-circle') ?>
      <h2>Tell us what happened</h2>
    </div>
    <div class="panel__body">
      <form method="post" action="<?= url('/report') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="field">
          <label class="field__label" for="area">Which part of the platform?</label>
          <select class="field__select" id="area" name="area">
            <?php foreach ($areas as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= old('area') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field <?= has_error('subject') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="subject">Subject *</label>
          <input class="field__input" id="subject" name="subject"
                 placeholder="Briefly describe the issue" value="<?= old('subject') ?>">
          <span class="field__error"><?= error_for('subject') ?></span>
        </div>

        <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="description">Description *</label>
          <textarea class="field__textarea" id="description" name="description"
                    maxlength="1000"
                    placeholder="Please provide detailed information about the issue…"><?= old('description') ?></textarea>
          <span class="field__hint">Up to 1000 characters.</span>
          <span class="field__error"><?= error_for('description') ?></span>
        </div>

        <div class="field">
          <label class="field__label" for="screenshots">Attach screenshots (optional)</label>
          <label class="dropzone" for="screenshots">
            <?= icon('upload-cloud') ?>
            <span>Click to browse, or drop files here</span>
            <small>PNG, JPG or JPEG up to 5MB each</small>
            <input type="file" id="screenshots" name="screenshots[]" accept=".png,.jpg,.jpeg" multiple>
          </label>
        </div>

        <button class="btn btn--brand btn--lg" type="submit"><?= icon('mail') ?>Submit Report</button>
      </form>
    </div>
  </section>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><h2>Before you report</h2></div>
      <div class="panel__body">
        <ul class="bullets">
          <li>Say which screen you were on and what you clicked.</li>
          <li>Include the exact wording of any error message.</li>
          <li>A screenshot usually saves a round of questions.</li>
          <li>For an urgent safety issue, contact the organizers directly.</li>
        </ul>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><h2>Other ways to reach us</h2></div>
      <div class="panel__body stack">
        <a class="btn btn--outline btn--block" href="<?= url('/messages') ?>">Message the support desk</a>
        <a class="btn btn--outline btn--block" href="<?= url('/support') ?>">Support the platform</a>
      </div>
    </section>
  </div>
</div>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  The form validates and confirms, but reports are not stored yet — the <code>reports</code>
  table belongs to the moderation backend.
</p>
