<?php
/**
 * "Cleanup : Join Campaign" - frame 3 of Cleanup-User.svg.
 *
 * `event_registrations` holds the emergency contact, the skills and the status;
 * the volunteer's own name, NIC, phone and email are already on `users`, so the
 * four fields at the top are shown pre-filled and confirmed rather than stored
 * a second time.
 *
 * The form asks for exactly what it stores: who you are, how to reach you, one
 * emergency contact and the skills you can offer. Availability and t-shirt size
 * have no column anywhere and are not drawn.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Cleanup\CleanupEvent;

$ev        = $event;
$spotsLeft = max(0, $ev['volunteer_limit'] - $ev['registered']);
$me        = $user ?? [];
?>

<a class="back-link" href="<?= url('/cleanup/' . $ev['id']) ?>"><?= icon('arrow-left') ?>Back to Campaign</a>

<div class="cl-head">
  <div class="cl-head__text">
    <h1>Join Campaign</h1>
    <p>Register as a volunteer. The organiser confirms your place before the day.</p>
  </div>
</div>

<form method="post" action="<?= url('/cleanup/' . $ev['id'] . '/join') ?>">
  <?= csrf_field() ?>

  <div class="split">
    <div class="stack">

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">1</span>
          <div><strong>Your Details</strong><small>Used for attendance and your certificate</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field-row">
            <div class="field <?= has_error('full_name') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="full_name">Full Name *</label>
              <input class="field__input" id="full_name" name="full_name"
                     value="<?= old('full_name', $me['full_name'] ?? '') ?>">
              <span class="field__error"><?= error_for('full_name') ?></span>
            </div>
            <div class="field <?= has_error('nic') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="nic">NIC Number *</label>
              <input class="field__input" id="nic" name="nic" placeholder="200145601234"
                     value="<?= old('nic', $me['nic'] ?? '') ?>">
              <span class="field__error"><?= error_for('nic') ?></span>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('phone') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="phone">Phone Number *</label>
              <input class="field__input" id="phone" name="phone" placeholder="07X XXX XXXX"
                     value="<?= old('phone', $me['phone'] ?? '') ?>">
              <span class="field__error"><?= error_for('phone') ?></span>
            </div>
            <div class="field <?= has_error('email') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="email">Email Address *</label>
              <input class="field__input" id="email" name="email" type="email"
                     value="<?= old('email', $me['email'] ?? '') ?>">
              <span class="field__error"><?= error_for('email') ?></span>
            </div>
          </div>

          <label class="cl-check">
            <input type="checkbox" name="certificate_by_email" value="1" checked>
            Send my digital certificate to this email after the campaign
          </label>
        </div>
      </section>

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num cl-sec__num--amber">2</span>
          <div><strong>Emergency Contact</strong><small>Required for on-site safety</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field-row">
            <div class="field <?= has_error('emergency_contact_name') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="emergency_contact_name">Contact Name *</label>
              <input class="field__input" id="emergency_contact_name" name="emergency_contact_name"
                     value="<?= old('emergency_contact_name') ?>">
              <span class="field__error"><?= error_for('emergency_contact_name') ?></span>
            </div>
            <div class="field <?= has_error('emergency_contact_phone') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="emergency_contact_phone">Contact Number *</label>
              <input class="field__input" id="emergency_contact_phone" name="emergency_contact_phone"
                     placeholder="07X XXX XXXX" value="<?= old('emergency_contact_phone') ?>">
              <span class="field__error"><?= error_for('emergency_contact_phone') ?></span>
            </div>
          </div>
        </div>
      </section>

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num cl-sec__num--blue">3</span>
          <div><strong>Skills</strong><small>Helps organisers assign work areas</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field <?= has_error('skills') ? 'field--invalid' : '' ?>" style="margin-bottom:0">
            <label class="field__label" for="skills">Relevant Skills</label>
            <textarea class="field__textarea" id="skills" name="skills" maxlength="255"
                      placeholder="Tell the organiser what you can help with - first aid, driving, heavy lifting, leading a team…"><?= old('skills') ?></textarea>
            <span class="field__hint">Up to 255 characters. Optional.</span>
            <span class="field__error"><?= error_for('skills') ?></span>
          </div>
        </div>
      </section>

    </div>

    <div class="stack">
      <section class="panel">
        <div class="panel__head"><?= icon('leaf') ?><h2><?= e($ev['title']) ?></h2></div>
        <div class="panel__body">
          <small style="color:var(--muted-3)">Organised by <?= e($ev['organiser']) ?></small>

          <div class="chip-row" style="margin-top:var(--s-3)">
            <span class="cl-status cl-status--<?= e(CleanupEvent::statusTone($ev['status'])) ?>">
              <?= e(CleanupEvent::statusLabel($ev['status'])) ?>
            </span>
            <span class="cl-status cl-status--neutral"><?= e(CleanupEvent::categoryLabel($ev['category'])) ?></span>
          </div>

          <div class="mini-list">
            <div><span>Date</span><span><?= e($ev['event_date']) ?></span></div>
            <div><span>Time</span><span><?= e($ev['start_time']) ?> - <?= e($ev['end_time']) ?></span></div>
            <div><span>Meeting point</span><span><?= e($ev['meeting_point']) ?></span></div>
            <div><span>Location</span><span><?= e($ev['district']) ?></span></div>
            <div><span>Spots left</span><span><?= e($spotsLeft) ?> of <?= e($ev['volunteer_limit']) ?></span></div>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('clipboard') ?><h2>What to Bring</h2><small>Come prepared for the site</small></div>
        <div class="panel__body">
          <ul class="cl-bullets">
            <li>Gloves and closed footwear</li>
            <li>Reusable water bottle</li>
            <li>Sun protection / cap</li>
            <li>Personal medication if any</li>
          </ul>
        </div>
      </section>

      <section class="panel panel--warn">
        <div class="panel__head"><?= icon('shield-check') ?><h2>Safety Notice</h2></div>
        <div class="panel__body">
          <p class="muted">
            You will receive safety guidelines, weather alerts and emergency instructions before and
            during the campaign.
          </p>
        </div>
      </section>
    </div>
  </div>

  <div class="cl-form-foot">
    <p>Registration closes once the volunteer limit is reached.</p>
    <a class="btn btn--outline" href="<?= url('/cleanup/' . $ev['id']) ?>">Cancel</a>
    <button class="btn btn--primary" type="submit">Confirm Registration</button>
  </div>
</form>
