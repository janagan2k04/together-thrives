<?php
/**
 * Proof-of-identity upload, shared by the organization, Grama Niladhari and
 * blood bank registration forms. The field is always named `document`.
 *
 * Caller sets:
 *   $docLabel     string  field label — leave empty when a section heading already names it
 *   $docNote      string  one-line instruction above the dropzone
 *   $docAccepted  array   list of acceptable document names
 */
?>
<div class="field <?= has_error('document') ? 'field--invalid' : '' ?>">
  <?php if (!empty($docLabel)): ?>
    <label class="field__label" for="document"><?= e($docLabel) ?> <span class="req">*</span></label>
  <?php endif; ?>

  <?php if (!empty($docNote)): ?>
    <p class="dropzone__note"><?= icon('file-check') ?> <?= e($docNote) ?></p>
  <?php endif; ?>

  <div class="auth-grid auth-grid--doc">
    <label class="dropzone" for="document">
      <?= icon('upload-cloud') ?>
      <span class="dropzone__text">
        Drag &amp; drop a file here or <b>click to browse</b>
        <span class="dropzone__hint">PDF, JPG or PNG (Max. 5MB)</span>
        <span class="dropzone__chosen" data-file-name hidden></span>
      </span>
      <input type="file" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png" data-file-input>
    </label>

    <div class="doc-accepted">
      <strong>Accepted documents (upload at least one)</strong>
      <ul>
        <?php foreach ($docAccepted as $doc): ?>
          <li><?= icon('check-circle') ?> <?= e($doc) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <span class="field__error"><?= error_for('document') ?></span>
</div>
