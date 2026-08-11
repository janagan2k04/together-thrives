<?php
/** "Admin - Settings" from admin.svg — General, Notifications, Maintenance, Localization. */
require_once APP_PATH . '/views/partials/icons.php';

$notify = [
    ['location', 'Location-based alerts', 'Notify users of nearby events by city',    true],
    ['email',    'Email confirmations',   'Registration and certificate emails',      true],
    ['safety',   'Safety notifications',  'Weather and emergency updates',            true],
];

$languages = [
    ['si', 'Sinhala', 'content and interface', true],
    ['ta', 'Tamil',   'content and interface', true],
];
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Settings</h1>
    <p>Configure platform-wide preferences, languages and integrations.</p>
  </div>
</div>

<form method="post" action="<?= url('/admin/settings') ?>">
  <?= csrf_field() ?>

  <div class="split split--even">
    <div class="stack">
      <section class="panel">
        <div class="panel__head"><h2>General</h2><small>Platform identity and contact details</small></div>
        <div class="panel__body">
          <div class="field <?= has_error('support_email') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="support_email">Support Email</label>
            <input class="field__input" id="support_email" name="support_email" type="email"
                   value="<?= old('support_email', 'support@togetherthrives.lk') ?>">
            <span class="field__error"><?= error_for('support_email') ?></span>
          </div>
          <div class="field <?= has_error('contact_phone') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="contact_phone">Contact Number</label>
            <input class="field__input" id="contact_phone" name="contact_phone"
                   value="<?= old('contact_phone', '0112345678') ?>">
            <span class="field__error"><?= error_for('contact_phone') ?></span>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><h2>Notifications</h2><small>Control what the system sends to users</small></div>
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

      <section class="panel panel--warn">
        <div class="panel__head"><h2>Maintenance</h2>
          <small>Temporarily disable public access while performing updates</small></div>
        <div class="panel__body">
          <div class="switch-row">
            <div class="switch-row__text">
              <strong>Maintenance mode</strong>
              <small>Only administrators can sign in</small>
            </div>
            <label class="switch">
              <input type="checkbox" name="maintenance">
              <span></span>
            </label>
          </div>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="panel">
        <div class="panel__head"><?= icon('globe') ?><h2>Localization</h2><small>Languages available to users</small></div>
        <div class="panel__body">
          <?php foreach ($languages as [$code, $label, $hint, $on]): ?>
            <div class="switch-row">
              <div class="switch-row__text">
                <strong><?= e($label) ?></strong>
                <small><?= e($hint) ?></small>
              </div>
              <label class="switch">
                <input type="checkbox" name="lang[<?= e($code) ?>]" <?= $on ? 'checked' : '' ?>>
                <span></span>
              </label>
            </div>
          <?php endforeach; ?>
          <p class="muted" style="margin-top:var(--s-4)">
            English is always on. <code>users.locale</code> already accepts all three, and the
            switcher at <code>/language/{code}</code> works today.
          </p>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('lock') ?><h2>Integrations</h2></div>
        <div class="panel__body">
          <dl class="kv kv--single">
            <div><dt>Payment gateway</dt><dd>PayHere <span class="badge badge--pending">Sandbox</span></dd></div>
            <div><dt>Outgoing mail</dt><dd>Not configured — links are written to <code>storage/logs/app.log</code></dd></div>
            <div><dt>SMS alerts</dt><dd>Not configured</dd></div>
          </dl>
        </div>
      </section>
    </div>
  </div>

  <div class="page-head__actions" style="margin-top:var(--s-5)">
    <a class="btn btn--outline" href="<?= url('/admin/dashboard') ?>">Cancel</a>
    <button class="btn btn--brand" type="submit">Save</button>
  </div>
</form>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  There is no settings table in the frozen schema, so these values are not persisted. The form
  validates and returns — adding a table is a group decision.
</p>
