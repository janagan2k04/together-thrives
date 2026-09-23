<?php
/**
 * "Settings" for a signed-in account.
 *
 * No frame exists for this screen - the sidebars all link to it and the only
 * settings frame in the file is the administrator's - so it follows that
 * frame's shape.
 *
 * What is here is what has a column. `users.locale` is the one preference the
 * frozen schema stores, so language is the one thing this screen saves. The
 * four notification switches and the two privacy switches were removed on
 * 2026-09-04: no table holds them, so every one of them was a promise the
 * platform could not keep, reset to its default on the next page load.
 * Giving them a home needs a table and a group decision.
 *
 * "Close my account" went the same way. It was a disabled button, and closing an
 * account is not something to half-offer.
 */
require_once APP_PATH . '/views/partials/icons.php';

$locales = ['en' => 'English', 'si' => 'සිංහල (Sinhala)', 'ta' => 'தமிழ் (Tamil)'];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Settings</h1>
    <p>Your language, your account, and the ways to reach us.</p>
  </div>
</div>

<div class="split split--even">
  <div class="stack">
    <section class="panel">
      <div class="panel__head"><?= icon('globe') ?><h2>Language</h2></div>
      <div class="panel__body">
        <form method="post" action="<?= url('/settings') ?>">
          <?= csrf_field() ?>
          <div class="field">
            <label class="field__label" for="locale">Interface language</label>
            <select class="field__select" id="locale" name="locale">
              <?php foreach ($locales as $code => $label): ?>
                <option value="<?= e($code) ?>" <?= ($user['locale'] ?? 'en') === $code ? 'selected' : '' ?>>
                  <?= e($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="field__hint">
              Or switch straight away:
              <?php foreach ($locales as $code => $label): ?>
                <a href="<?= url('/language/' . $code) ?>"><?= e(strtoupper($code)) ?></a>
              <?php endforeach; ?>
            </span>
          </div>
          <button class="btn btn--primary" type="submit">Save language</button>
        </form>
      </div>
    </section>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><?= icon('lock') ?><h2>Account</h2></div>
      <div class="panel__body stack">
        <a class="btn btn--outline btn--block" href="<?= url('/profile') ?>">Edit my profile</a>
        <a class="btn btn--outline btn--block" href="<?= url('/forgot-password') ?>">Change my password</a>
        <a class="btn btn--outline btn--block" href="<?= url('/support') ?>">Support the platform</a>
        <a class="btn btn--outline btn--block" href="<?= url('/report') ?>">Report a problem</a>
      </div>
    </section>
  </div>
</div>
