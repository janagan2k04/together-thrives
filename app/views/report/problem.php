<?php
/** "Report" from Comm_User-Homepage.svg - report a problem with the platform. */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Report;
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
      <form method="post" action="<?= url('/report') ?>">
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

        <button class="btn btn--brand btn--lg" type="submit"><?= icon('mail') ?>Submit Report</button>
      </form>
    </div>
  </section>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><h2>Your reports</h2></div>
      <div class="panel__body">
        <?php if (!$mine): ?>
          <p class="muted">You have not reported anything yet.</p>
        <?php else: ?>
          <div class="mini-list">
            <?php foreach ($mine as $m): ?>
              <div>
                <span>
                  <strong style="color:var(--ink)"><?= e($m['reason']) ?></strong>
                  <small style="display:block;color:var(--muted-3)"><?= e(time_ago($m['created_at'])) ?></small>
                </span>
                <span class="badge badge--<?= e(Report::TONES[$m['status']] ?? 'neutral') ?>">
                  <?= e(Report::STATUSES[$m['status']] ?? $m['status']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>

