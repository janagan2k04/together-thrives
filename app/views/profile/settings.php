<?php
/**
 * "Settings" for a signed-in account.
 *
 * No frame exists for this screen — the sidebars all link to it and the only
 * settings frame in the file is the administrator's — so it follows that
 * frame's shape: grouped cards of toggles with a save bar.
 */
require_once APP_PATH . '/views/partials/icons.php';

$notify = [
    ['nearby',   'Alerts near me',        'Disasters, dengue alerts and campaigns in my city', true],
    ['requests', 'Requests and matches',  'Someone answers a request, or a donor picks me',    true],
    ['messages', 'Messages',              'New messages in a conversation I am part of',       true],
    ['digest',   'Weekly digest',         'A summary of activity in my area every Monday',     false],
];

$privacy = [
    ['show_profile', 'Show my profile to other members', 'Your name and rating appear on listings you donate', true],
    ['show_city',    'Show my city on listings',         'Helps recipients judge whether collection is practical', true],
];

$locales = ['en' => 'English', 'si' => 'සිංහල (Sinhala)', 'ta' => 'தமிழ් (Tamil)'];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Settings</h1>
    <p>Control what TogetherThrives sends you and what other members can see.</p>
  </div>
</div>

<form method="post" action="<?= url('/settings') ?>">
  <?= csrf_field() ?>

  <div class="split split--even">
    <div class="stack">
      <section class="panel">
        <div class="panel__head"><?= icon('bell') ?><h2>Notifications</h2></div>
        <div class="panel__body">
          <?php foreach ($notify as [$name, $label, $hint, $on]): ?>
            <div class="switch-row">
              <div class="switch-row__text">
                <strong><?= e($label) ?></strong>
                <small><?= e($hint) ?></small>
              </div>
              <label class="switch">
                <input type="checkbox" name="notify[<?= e($name) ?>]" <?= $on ? 'checked' : '' ?>>
                <span></span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('shield-check') ?><h2>Privacy</h2></div>
        <div class="panel__body">
          <?php foreach ($privacy as [$name, $label, $hint, $on]): ?>
            <div class="switch-row">
              <div class="switch-row__text">
                <strong><?= e($label) ?></strong>
                <small><?= e($hint) ?></small>
              </div>
              <label class="switch">
                <input type="checkbox" name="privacy[<?= e($name) ?>]" <?= $on ? 'checked' : '' ?>>
                <span></span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="panel">
        <div class="panel__head"><?= icon('globe') ?><h2>Language</h2></div>
        <div class="panel__body">
          <div class="field">
            <label class="field__label" for="locale">Interface language</label>
            <select class="field__select" id="locale" name="locale">
              <?php foreach ($locales as $code => $label): ?>
                <option value="<?= e($code) ?>" <?= ($user['locale'] ?? 'en') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="field__hint">
              Switching immediately without saving:
              <?php foreach ($locales as $code => $label): ?>
                <a href="<?= url('/language/' . $code) ?>"><?= e(strtoupper($code)) ?></a>
              <?php endforeach; ?>
            </span>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('lock') ?><h2>Account</h2></div>
        <div class="panel__body stack">
          <a class="btn btn--outline btn--block" href="<?= url('/profile') ?>">Edit my profile</a>
          <a class="btn btn--outline btn--block" href="<?= url('/forgot-password') ?>">Change my password</a>
          <a class="btn btn--outline btn--block" href="<?= url('/support') ?>">Support the platform</a>
          <a class="btn btn--outline btn--block" href="<?= url('/report') ?>">Report a problem</a>
        </div>
      </section>

      <section class="panel panel--warn">
        <div class="panel__head"><?= icon('ban') ?><h2>Close account</h2></div>
        <div class="panel__body">
          <p class="muted">
            Closing your account hides your listings and cancels open requests. Completed
            donations stay on record so recipients keep their history.
          </p>
          <button class="btn btn--danger" type="button" style="margin-top:var(--s-4)" disabled>
            Close my account
          </button>
        </div>
      </section>
    </div>
  </div>

  <div class="page-head__actions" style="margin-top:var(--s-5)">
    <a class="btn btn--outline" href="<?= url(Core\Auth::homeFor()) ?>">Cancel</a>
    <button class="btn btn--primary" type="submit">Save Settings</button>
  </div>
</form>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  There are no columns for these preferences in the frozen schema, so nothing is stored yet.
  The language switcher above works — it goes through <code>/language/{code}</code>.
</p>
