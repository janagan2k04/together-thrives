<?php
/**
 * "Cleanup : Create Campaign" - frame 2 of Cleanup-User.svg. The community
 * member's proposal form, reached from Create Campaign on /cleanup.
 *
 * SETTLED IN WEEKS 4-5 - `cleanup_events.organization_id` is NOT NULL and points
 * at `organizations`, so a community member cannot own a row in the frozen
 * schema. The group's ruling was to keep campaigns organisation-owned rather
 * than change the frozen table or invent a proxy organisation, so this screen
 * checks a proposal and passes it on; it deliberately stores nothing, and the
 * note at the foot of the page tells the member exactly that. An organisation
 * creates the real row at /org/cleanup/create.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Cleanup\CleanupEvent;
?>

<a class="back-link" href="<?= url('/cleanup') ?>"><?= icon('arrow-left') ?>Back to Campaigns</a>

<div class="cl-head">
  <div class="cl-head__text">
    <h1>Create New Cleanup/ Volunteering Campaign</h1>
    <p>Propose a cleanup or volunteering campaign for your area. An administrator reviews it before it is published.</p>
  </div>
</div>

<form method="post" action="<?= url('/cleanup') ?>">
  <?= csrf_field() ?>

  <div class="split">
    <div class="stack">

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">1</span>
          <div><strong>Campaign Details</strong><small>What the campaign is about</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field-row">
            <div class="field <?= has_error('category') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="category">Category *</label>
              <select class="field__select" id="category" name="category">
                <option value="">Select category</option>
                <?php foreach (CleanupEvent::CATEGORIES as $key => $label): ?>
                  <option value="<?= e($key) ?>" <?= old('category') === $key ? 'selected' : '' ?>>
                    <?= e($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="field__error"><?= error_for('category') ?></span>
            </div>
          </div>

          <div class="field <?= has_error('title') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="title">Campaign Title *</label>
            <input class="field__input" id="title" name="title"
                   placeholder="e.g. Kelani River Bank Cleanup" value="<?= old('title') ?>">
            <span class="field__error"><?= error_for('title') ?></span>
          </div>

          <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="description">Short Description *</label>
            <textarea class="field__textarea" id="description" name="description"
                      placeholder="What needs cleaning, and why it matters…"><?= old('description') ?></textarea>
            <span class="field__error"><?= error_for('description') ?></span>
          </div>
        </div>
      </section>

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">2</span>
          <div><strong>Location &amp; Schedule</strong><small>Where and when volunteers meet</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field-row">
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
              <span class="field__error"><?= error_for('city_id') ?></span>
            </div>
            <div class="field <?= has_error('meeting_point') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="meeting_point">Meeting Point *</label>
              <input class="field__input" id="meeting_point" name="meeting_point"
                     placeholder="e.g. Kelani Bridge, Gate 2" value="<?= old('meeting_point') ?>">
              <span class="field__error"><?= error_for('meeting_point') ?></span>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('event_date') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="event_date">Date *</label>
              <input class="field__input" id="event_date" name="event_date" type="date" value="<?= old('event_date') ?>">
              <span class="field__error"><?= error_for('event_date') ?></span>
            </div>
            <div class="field <?= has_error('start_time') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="start_time">Start Time *</label>
              <input class="field__input" id="start_time" name="start_time" type="time" value="<?= old('start_time') ?>">
              <span class="field__error"><?= error_for('start_time') ?></span>
            </div>
            <div class="field <?= has_error('end_time') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="end_time">End Time *</label>
              <input class="field__input" id="end_time" name="end_time" type="time" value="<?= old('end_time') ?>">
              <span class="field__error"><?= error_for('end_time') ?></span>
            </div>
          </div>
        </div>
      </section>

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">3</span>
          <div><strong>Volunteers &amp; Safety</strong><small>Capacity, equipment and guidelines</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field-row">
            <div class="field <?= has_error('volunteer_limit') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="volunteer_limit">Volunteer Limit *</label>
              <input class="field__input" id="volunteer_limit" name="volunteer_limit" type="number" min="1"
                     placeholder="50" value="<?= old('volunteer_limit') ?>">
              <span class="field__error"><?= error_for('volunteer_limit') ?></span>
            </div>
          </div>

          <div class="field-row">
            <div class="field">
              <label class="field__label" for="equipment_provided">Required Equipment *</label>
              <input class="field__input" id="equipment_provided" name="equipment_provided"
                     placeholder="Gloves, garbage bags, boots, reusable water bottle"
                     value="<?= old('equipment_provided') ?>">
            </div>
            <div class="field <?= has_error('safety_guidelines') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="safety_guidelines">Safety Guidelines *</label>
              <input class="field__input" id="safety_guidelines" name="safety_guidelines"
                     placeholder="Wear covered footwear. Follow team leader instructions."
                     value="<?= old('safety_guidelines') ?>">
              <span class="field__error"><?= error_for('safety_guidelines') ?></span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="panel">
        <div class="panel__head"><?= icon('file-text') ?><h2>Campaign Summary</h2><small>Review before submitting</small></div>
        <div class="panel__body">
          <div class="mini-list">
            <div><span>Type</span><span>Cleanup</span></div>
            <div><span>Category</span><span>Chosen above</span></div>
            <div><span>Location</span><span>Chosen above</span></div>
            <div><span>Date</span><span>Chosen above</span></div>
            <div><span>Volunteer limit</span><span>Chosen above</span></div>
          </div>
        </div>
      </section>

      <section class="panel panel--warn">
        <div class="panel__head"><?= icon('shield-check') ?><h2>An organisation has to host it</h2></div>
        <div class="panel__body">
          <div class="cl-note cl-note--amber">
            <strong>This is a proposal, not a campaign</strong>
            <p>
              Every cleanup campaign on the platform is run by a verified organisation - they are
              accountable for the site, the equipment and volunteer safety. Your proposal is checked
              and passed to an administrator, who finds an organisation covering your area to run it.
              Nothing is published under your own account.
            </p>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('check-circle') ?><h2>Before you submit</h2></div>
        <div class="panel__body">
          <ul class="cl-ticks">
            <li>Give an accurate meeting point volunteers can find.</li>
            <li>Set a realistic volunteer limit for the site.</li>
            <li>List all equipment volunteers must bring.</li>
            <li>Add clear safety guidelines for the location.</li>
            <li>Publish an impact update once the cleanup ends.</li>
          </ul>
        </div>
      </section>
    </div>
  </div>

  <div class="cl-form-foot">
    <p>Campaigns must include a meeting point, schedule, volunteer limit and safety guidelines.</p>
    <a class="btn btn--outline" href="<?= url('/cleanup') ?>">Cancel</a>
    <button class="btn btn--primary" type="submit">Submit for Approval</button>
  </div>
</form>

