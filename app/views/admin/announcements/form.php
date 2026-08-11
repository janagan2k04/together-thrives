<?php
/**
 * Create and edit share one form, the same way `views/reuse/_form.php` does —
 * the fields are identical and two copies drift apart the first time one
 * changes. `$announcement` is null when creating.
 */
require_once APP_PATH . '/views/partials/icons.php';
?>

<a class="back-link" href="<?= url('/admin/announcements') ?>"><?= icon('arrow-left') ?>Back to Announcements</a>

<div class="admin-head">
  <div class="admin-head__text">
    <h1><?= e($heading) ?></h1>
    <p>Notices appear in the notification list of every account in the chosen audience.</p>
  </div>
</div>

<form method="post" action="<?= url($action) ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <?php if ($method === 'PUT'): ?><?= method_field('PUT') ?><?php endif; ?>

  <section class="panel">
    <div class="panel__head"><?= icon('megaphone') ?><h2>Announcement</h2></div>
    <div class="panel__body">
      <div class="field <?= has_error('title') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="title">Title</label>
        <input class="field__input" id="title" name="title"
               value="<?= old('title', $announcement['title'] ?? '') ?>"
               placeholder="e.g. Monsoon preparedness advisory">
        <span class="field__error"><?= error_for('title') ?></span>
      </div>

      <div class="field <?= has_error('body') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="body">Message</label>
        <textarea class="field__textarea" id="body" name="body"
                  placeholder="What do people need to know, and what should they do?"><?= old('body', $announcement['sub'] ?? '') ?></textarea>
        <span class="field__error"><?= error_for('body') ?></span>
      </div>

      <div class="field <?= has_error('audience') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="audience">Audience</label>
        <select class="field__select" id="audience" name="audience">
          <?php foreach ($audiences as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= old('audience') === $value ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span class="field__error"><?= error_for('audience') ?></span>
      </div>
    </div>
  </section>

  <div class="page-head__actions" style="margin-top:var(--s-5)">
    <a class="btn btn--outline" href="<?= url('/admin/announcements') ?>">Cancel</a>
    <button class="btn btn--brand" type="submit">
      <?= $method === 'PUT' ? 'Save changes' : 'Publish announcement' ?>
    </button>
  </div>
</form>
