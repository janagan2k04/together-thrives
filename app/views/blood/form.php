<?php
/**
 * "Create New Blood Campaign" - frame 2 of Blood-Org.svg. Create and edit share
 * this file; `$row` is null when creating.
 *
 * The Campaign Type pair at the top is two links rather than a radio, because
 * the two things it switches between are genuinely different screens: a camp is
 * a `blood_campaigns` row created here, an urgent need is a
 * `critical_blood_requests` row created at /blood/critical/create.
 *
 * End Date and Full Address were added on 2026-08-18 with migration
 * 2026_08_18. End Date used to be drawn by the Figma, collected and thrown
 * away; it has a column now.
 */
require_once APP_PATH . '/views/partials/icons.php';

$editing = $row !== null;

/** Time columns come back as 'HH:MM:SS'; <input type="time"> wants 'HH:MM'. */
$timeValue = static fn(?string $t): string => $t ? substr($t, 0, 5) : '';

// A checkbox posts nothing when it is off, so old input cannot distinguish
// "unchecked" from "first render". The stored row decides when editing.
$autoChat = $editing ? (bool) $row['auto_group_chat'] : true;

$checks = [
    'Campaign title and description are filled in.',
    'Venue and district are correct - donors are matched by district.',
    'The date and times are right; editing an approved campaign sends it back for review.',
    'Contact number is reachable during the campaign.',
];
?>

<a class="blood-back" href="<?= url('/my/blood') ?>"><?= icon('arrow-left') ?>Back to Blood Campaigns</a>

<div class="blood-head">
  <div class="blood-head__text">
    <h1><?= $editing ? 'Edit Blood Campaign' : 'Create New Blood Campaign' ?></h1>
    <p>Publish a blood donation camp or an urgent blood request. An admin fact-checks it before it goes live.</p>
  </div>
</div>

<?php
/* The Campaign Type switch is hidden for a Grama Niladhari officer: the group
   opened blood CAMPS to the role on 2026-08-27 and nothing else, so the only
   type on offer is the one this form already builds. routes/blood.php blocks
   /blood/critical/create for the role too - a hidden link is still a URL. */
?>
<?php if (!$editing && !role_is(ROLE_GN)): ?>
  <p class="field__label">Campaign Type</p>
  <div class="type-pick type-pick--blood">
    <label>
      <input type="radio" name="campaign_type_preview" checked>
      <span>
        <span class="group-dot" style="width:34px;height:34px"><?= icon('droplet') ?></span>
        <span>
          <strong>Blood Donation Campaign</strong>
          <small>Scheduled camp with a date range and venue</small>
        </span>
      </span>
    </label>
    <a href="<?= url('/blood/critical/create') ?>">
      <span style="display:flex;align-items:center;gap:var(--s-3);padding:var(--s-4);
                   border:1px solid var(--field-border);border-radius:var(--r-lg);background:var(--surface)">
        <span class="group-dot" style="width:34px;height:34px"><?= icon('bell') ?></span>
        <span>
          <strong style="display:block;font-size:var(--fs-base);font-weight:700;color:var(--ink)">Critical Blood Request</strong>
          <small style="display:block;margin-top:2px;font-size:var(--fs-xs);color:var(--muted-3)">
            Urgent need - eligible donor contacts shared instantly
          </small>
        </span>
      </span>
    </a>
  </div>
<?php endif; ?>

<form method="post" action="<?= url($action) ?>">
  <?= csrf_field() ?>
  <?php if ($method === 'PUT'): ?><?= method_field('PUT') ?><?php endif; ?>

  <div class="split">
    <div class="stack">

      <section class="sec">
        <div class="sec__head">
          <div><strong>Campaign Details</strong><small>Shown to donors on the campaign post.</small></div>
        </div>
        <div class="sec__body">
          <div class="field <?= has_error('title') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="title">Campaign Title *</label>
            <input class="field__input" id="title" name="title"
                   placeholder="e.g. World Blood Donor Day Drive"
                   value="<?= old('title', $row['title'] ?? '') ?>">
            <span class="field__error"><?= error_for('title') ?></span>
          </div>

          <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="description">Description *</label>
            <textarea class="field__textarea" id="description" name="description"
                      placeholder="Describe the purpose of the campaign and who it helps…"><?= old('description', $row['description'] ?? '') ?></textarea>
            <span class="field__error"><?= error_for('description') ?></span>
          </div>

          <div class="field">
            <span class="field__label">Blood Types Needed</span>
            <div class="group-pick">
              <?php foreach ($groups as $g): ?>
                <label>
                  <input type="checkbox" name="blood_types[]" value="<?= e($g) ?>">
                  <span><?= e($g) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('target_donors') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="target_donors">Units / Donors Required *</label>
              <input class="field__input" id="target_donors" name="target_donors" type="number" min="1"
                     placeholder="e.g. 150" value="<?= old('target_donors', $row['target_donors'] ?? '') ?>">
              <span class="field__error"><?= error_for('target_donors') ?></span>
            </div>
            <div class="field <?= has_error('contact_phone') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="contact_phone">Contact Number *</label>
              <input class="field__input" id="contact_phone" name="contact_phone"
                     placeholder="07X XXX XXXX" value="<?= old('contact_phone', $row['contact_phone'] ?? '') ?>">
              <span class="field__error"><?= error_for('contact_phone') ?></span>
            </div>
          </div>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Location &amp; Schedule</strong><small>The district is used to notify nearby donors.</small></div>
        </div>
        <div class="sec__body">
          <div class="field-row">
            <div class="field <?= has_error('venue') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="venue">Venue / Organizer *</label>
              <input class="field__input" id="venue" name="venue"
                     placeholder="e.g. Sri Lanka Red Cross Society"
                     value="<?= old('venue', $row['venue'] ?? '') ?>">
              <span class="field__error"><?= error_for('venue') ?></span>
            </div>
            <div class="field <?= has_error('city_id') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="city_id">District *</label>
              <?php $cityId = old('city_id', $row['city_id'] ?? ''); ?>
              <select class="field__select" id="city_id" name="city_id">
                <option value="">Select district</option>
                <?php foreach ($cities as $c): ?>
                  <option value="<?= e($c['id']) ?>" <?= $cityId === (string) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="field__error"><?= error_for('city_id') ?></span>
            </div>
          </div>

          <div class="field <?= has_error('address') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="address">Full Address *</label>
            <input class="field__input" id="address" name="address"
                   placeholder="e.g. 106 Dharmapala Mawatha, Colombo 07"
                   value="<?= old('address', $row['address'] ?? '') ?>">
            <span class="field__error"><?= error_for('address') ?></span>
            <span class="field__hint">Street address donors navigate to. Venue is the place name, this is the door.</span>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('campaign_date') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="campaign_date">Start Date *</label>
              <input class="field__input" id="campaign_date" name="campaign_date" type="date"
                     value="<?= old('campaign_date', $row['campaign_date'] ?? '') ?>">
              <span class="field__error"><?= error_for('campaign_date') ?></span>
            </div>
            <div class="field <?= has_error('campaign_end_date') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="campaign_end_date">End Date *</label>
              <input class="field__input" id="campaign_end_date" name="campaign_end_date" type="date"
                     value="<?= old('campaign_end_date', $row['campaign_end_date'] ?? '') ?>">
              <span class="field__error"><?= error_for('campaign_end_date') ?></span>
              <span class="field__hint">Same as the start date for a one-day camp.</span>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('start_time') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="start_time">Time From *</label>
              <input class="field__input" id="start_time" name="start_time" type="time"
                     value="<?= old('start_time', $timeValue($row['start_time'] ?? null)) ?>">
              <span class="field__error"><?= error_for('start_time') ?></span>
            </div>
            <div class="field <?= has_error('end_time') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="end_time">Time To *</label>
              <input class="field__input" id="end_time" name="end_time" type="time"
                     value="<?= old('end_time', $timeValue($row['end_time'] ?? null)) ?>">
              <span class="field__error"><?= error_for('end_time') ?></span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="sec">
        <div class="sec__head">
          <div><strong>Donor Screening &amp; Communication</strong><small>Donors complete this form when they tap Join.</small></div>
        </div>
        <div class="sec__body">
          <div class="field">
            <label class="field__label" for="screening_form">Medical Screening Form</label>
            <select class="field__select" id="screening_form" name="screening_form">
              <option>Standard Screening Form (14 questions)</option>
            </select>
            <span class="field__hint">
              The fourteen defaults live in <code>screening_questions</code>. Per-campaign question
              sets are a later job.
            </span>
          </div>

          <?php /*
            These two describe what the platform does, not settings an organiser
            chooses - there are no columns for them and no choice to make. They
            were drawn as switches, one permanently on and one permanently off,
            which read as options that could not be operated.
          */ ?>
          <div class="switch-row">
            <div class="switch-row__text">
              <strong>Automatic eligibility evaluation</strong>
              <small>Screening answers are scored as they are submitted, and the donor is told the result</small>
            </div>
          </div>

          <div class="switch-row">
            <div class="switch-row__text">
              <strong>Auto-create campaign group chat</strong>
              <small>Eligible donors are added so you can post updates</small>
            </div>
            <label class="switch">
              <input type="checkbox" name="auto_group_chat" value="1" <?= $autoChat ? 'checked' : '' ?>><span></span>
            </label>
          </div>

          <div class="switch-row">
            <div class="switch-row__text">
              <strong>Eligible donor contacts are shared with you</strong>
              <small>A donor's phone number is shown once the screening clears them, and not before</small>
            </div>
          </div>

        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Before you submit</strong></div>
        </div>
        <div class="sec__body">
          <ul class="tick-list">
            <?php foreach ($checks as $c): ?><li><?= e($c) ?></li><?php endforeach; ?>
          </ul>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Who gets notified</strong></div>
        </div>
        <div class="sec__body">
          <p class="muted">
            Donors whose registered district matches this campaign get an in-app notification as soon
            as an administrator approves it.
          </p>
        </div>
      </section>
    </div>
  </div>

  <div class="form-foot">
    <p>
      <?= $editing
            ? 'Editing an approved campaign returns it to the admin queue before it is shown again.'
            : 'Your campaign is submitted to an admin for fact-checking before it is published.' ?>
    </p>
    <a class="btn btn--outline" href="<?= url($editing ? '/my/blood/' . $row['id'] : '/my/blood') ?>">Cancel</a>
    <button class="btn btn--brand" type="submit">
      <?= $editing ? 'Save Changes' : 'Submit for Verification' ?>
    </button>
  </div>
</form>
