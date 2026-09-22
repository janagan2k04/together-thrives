<?php
/**
 * "Org - Create Cleanup Campaign" - frames 2 and 3 of Cleanup-Org.svg, and the
 * edit form. One file for all four screens: `$step` picks the page, `$event` is
 * null when creating.
 *
 * The step is a query string rather than JavaScript, so Back is a normal link
 * and each page is a URL you can reload. **Next is a submit, not a link** - the
 * form spans two URLs, so page 1's answers are validated, remembered on the
 * server and re-posted from page 2 as hidden inputs. Without that the final
 * submit would carry only the safety guidelines and every required field would
 * come back empty.
 *
 * Only the nine fields listed in the controller's rules() map to
 * `cleanup_events` columns. The waste-management and communication sections
 * belong to tables filled after the event runs (`waste_records`,
 * `event_photos`, `cleanup_impacts`); they are planning aids on this form and
 * are not written when the campaign is saved.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Cleanup\CleanupEvent;

$editing = $event !== null;
$ev      = $event ?? [];
$other   = $step === 1 ? 2 : 1;

/**
 * Field values: what was typed on page 1 (the carried draft) beats what is
 * stored, and a failed submit's old input beats both - that last one is
 * old()'s own job, so it is passed as the fallback here.
 */
$base = [
    'title'              => (string) ($ev['title'] ?? ''),
    'description'        => (string) ($ev['description'] ?? ''),
    'category'           => (string) ($ev['category'] ?? ''),
    'event_date'         => (string) ($ev['event_date'] ?? ''),
    'start_time'         => substr((string) ($ev['start_time'] ?? ''), 0, 5),
    'end_time'           => substr((string) ($ev['end_time'] ?? ''), 0, 5),
    'meeting_point'      => (string) ($ev['meeting_point'] ?? ''),
    'city_id'            => (string) ($ev['city_id'] ?? ''),
    'volunteer_limit'    => (string) ($ev['volunteer_limit'] ?? ''),
    'safety_guidelines'  => (string) ($ev['safety_guidelines'] ?? ''),
];
foreach ($draft as $key => $value) {
    if (array_key_exists($key, $base) && is_scalar($value)) {
        $base[$key] = (string) $value;
    }
}

/** The ticked equipment boxes - a new campaign starts with all four ticked. */
$allEquipment = ['Gloves', 'Garbage Bags', 'Safety Boots', 'Drinking Water'];
if (isset($draft['equipment']) && is_array($draft['equipment'])) {
    $equipmentOn = $draft['equipment'];
} elseif ($editing) {
    $equipmentOn = array_filter(array_map('trim', explode(',', (string) ($ev['equipment_provided'] ?? ''))));
} else {
    $equipmentOn = $allEquipment;
}

/** Errors raised against a page 1 field while the organiser is looking at page 2. */
$step1Errors = array_filter($step1, 'has_error');
?>

<div class="crumbs">
  <a href="<?= url('/org/dashboard') ?>">Dashboard</a> ›
  <a href="<?= url('/org/cleanup') ?>">My Cleanup Campaigns</a> ›
  <span class="is-current"><?= $editing ? 'Edit Campaign' : 'Create New Campaign' ?></span>
</div>

<div class="cl-head">
  <div class="cl-head__text">
    <h1><?= $editing ? 'Edit Cleanup/ Volunteering Campaign' : 'Create New Cleanup/ Volunteering Campaign' ?></h1>
    <p>
      <?= $step === 1
          ? 'Fill in the details below to create a new campaign.'
          : 'Step 2 - safety, equipment, waste management and publishing settings.' ?>
    </p>
  </div>
  <div class="cl-head__actions">
    <span class="cl-step">Page <?= e($step) ?> of 2</span>
  </div>
</div>

<form id="cl-form" method="post" action="<?= url($action) ?>">
  <?= csrf_field() ?>
  <?php if ($method === 'PUT'): ?><?= method_field('PUT') ?><?php endif; ?>

<?php if ($step === 1): ?>

  <div class="split split--even">
    <div class="stack">

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">1</span>
          <div><strong>Campaign Information</strong><small>Provide basic details about the campaign</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field-row">
            <div class="field <?= has_error('title') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="title">Campaign Title *</label>
              <input class="field__input" id="title" name="title"
                     placeholder="e.g. Kelani River Cleanup Campaign"
                     value="<?= old('title', $base['title']) ?>">
              <span class="field__error"><?= error_for('title') ?></span>
            </div>
            <div class="field <?= has_error('category') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="category">Campaign Category *</label>
              <select class="field__select" id="category" name="category">
                <option value="">Select category</option>
                <?php foreach (CleanupEvent::CATEGORIES as $key => $label): ?>
                  <option value="<?= e($key) ?>"
                    <?= old('category', $base['category']) === $key ? 'selected' : '' ?>>
                    <?= e($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="field__error"><?= error_for('category') ?></span>
            </div>
          </div>

          <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="description">Campaign Description *</label>
            <textarea class="field__textarea" id="description" name="description"
                      placeholder="What the campaign does and who it helps…"><?= old('description', $base['description']) ?></textarea>
            <span class="field__error"><?= error_for('description') ?></span>
          </div>

          <div class="field-row">
            <div class="field">
              <span class="field__label">Event Status</span>
              <span class="cl-status cl-status--<?= e(CleanupEvent::statusTone($ev['status'] ?? EVENT_DRAFT)) ?>">
                <?= e(CleanupEvent::statusLabel($ev['status'] ?? EVENT_DRAFT)) ?>
              </span>
              <span class="field__hint">
                Campaigns go live only after an administrator approves them, and saving an approved
                campaign sends it back for approval.
              </span>
            </div>
          </div>
        </div>
      </section>

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">2</span>
          <div><strong>Event Schedule</strong><small>Set the date, time and meeting point</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field-row">
            <div class="field <?= has_error('event_date') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="event_date">Event Date *</label>
              <input class="field__input" id="event_date" name="event_date" type="date"
                     value="<?= old('event_date', $base['event_date']) ?>">
              <span class="field__error"><?= error_for('event_date') ?></span>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('start_time') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="start_time">Start Time *</label>
              <input class="field__input" id="start_time" name="start_time" type="time"
                     value="<?= old('start_time', $base['start_time']) ?>">
              <span class="field__error"><?= error_for('start_time') ?></span>
            </div>
            <div class="field <?= has_error('end_time') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="end_time">End Time *</label>
              <input class="field__input" id="end_time" name="end_time" type="time"
                     value="<?= old('end_time', $base['end_time']) ?>">
              <span class="field__error"><?= error_for('end_time') ?></span>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('meeting_point') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="meeting_point">Meeting Point *</label>
              <input class="field__input" id="meeting_point" name="meeting_point"
                     placeholder="e.g. Ruwanwella Town Hall Parking Area"
                     value="<?= old('meeting_point', $base['meeting_point']) ?>">
              <span class="field__error"><?= error_for('meeting_point') ?></span>
            </div>
            <div class="field <?= has_error('city_id') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="city_id">District *</label>
              <select class="field__select" id="city_id" name="city_id">
                <option value="">Select district</option>
                <?php foreach ($cities as $c): ?>
                  <option value="<?= e($c['id']) ?>"
                    <?= old('city_id', $base['city_id']) === (string) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="field__error"><?= error_for('city_id') ?></span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="stack">

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">3</span>
          <div><strong>Volunteer Requirements</strong><small>Define the number of volunteers and requirements</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field-row">
            <div class="field <?= has_error('volunteer_limit') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="volunteer_limit">Maximum Volunteers *</label>
              <input class="field__input" id="volunteer_limit" name="volunteer_limit" type="number" min="1"
                     placeholder="150" value="<?= old('volunteer_limit', $base['volunteer_limit']) ?>">
              <span class="field__error"><?= error_for('volunteer_limit') ?></span>
            </div>
          </div>


          <div class="field" style="margin-bottom:0">
            <span class="field__label">Safety Equipment Provided</span>
            <div class="cl-checks">
              <?php foreach ($allEquipment as $item): ?>
                <label class="cl-check">
                  <input type="checkbox" name="equipment[]" value="<?= e($item) ?>"
                         <?= in_array($item, $equipmentOn, true) ? 'checked' : '' ?>>
                  <?= e($item) ?>
                </label>
              <?php endforeach; ?>
            </div>
            <span class="field__hint">
              Volunteers are told what they do not need to bring.
            </span>
          </div>
        </div>
      </section>
    </div>
  </div>

  <div class="cl-form-foot">
    <p>Step 1 of 2 - campaign info, schedule and volunteer requirements.</p>
    <a class="btn btn--outline" href="<?= url('/org/cleanup') ?>">Cancel</a>
    <button class="btn btn--primary" type="submit" name="stage" value="next">Next</button>
  </div>

<?php else: ?>

  <?php
  // Page 1's answers, validated when Next was pressed and carried here so the
  // final submit posts the whole campaign in one request.
  foreach ($step1 as $field): ?>
    <input type="hidden" name="<?= e($field) ?>" value="<?= old($field, $base[$field] ?? '') ?>">
  <?php endforeach; ?>
  <?php foreach ($equipmentOn as $item): ?>
    <input type="hidden" name="equipment[]" value="<?= e($item) ?>">
  <?php endforeach; ?>

  <?php if ($step1Errors): ?>
    <div class="alert alert--error">
      Something on page 1 needs fixing:
      <?= e(implode(', ', array_map(fn($f) => ucwords(str_replace('_', ' ', $f)), $step1Errors))) ?>.
      <a href="<?= url($stepUrl . '?step=1') ?>">Go back to page 1</a>.
    </div>
  <?php endif; ?>

  <div class="split split--even">
    <div class="stack">

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">4</span>
          <div><strong>Safety Guidelines &amp; Instructions</strong><small>Safety information automatically sent to all registered volunteers</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field <?= has_error('safety_guidelines') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="safety_guidelines">Safety Guidelines *</label>
            <textarea class="field__textarea" id="safety_guidelines" name="safety_guidelines"
                      placeholder="Wear gloves and safety boots at all times…"><?= old('safety_guidelines', $base['safety_guidelines']) ?></textarea>
            <span class="field__error"><?= error_for('safety_guidelines') ?></span>
          </div>
        </div>
      </section>

    </div>

    <div class="stack">

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">5</span>
          <div><strong>Waste Management Plan</strong><small>Plan collection, separation and proper disposal</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="field">
            <span class="field__label">Expected Waste Types</span>
            <div class="cl-pills">
              <?php foreach (['Plastic', 'Polythene', 'Glass', 'Metal', 'Organic'] as $type): ?>
                <label class="cl-pill">
                  <input type="checkbox" name="waste_types[]" value="<?= e($type) ?>">
                  <span><?= e($type) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="field-row">
            <div class="field">
              <label class="field__label" for="disposal_method">Disposal Method</label>
              <select class="field__select" id="disposal_method" name="disposal_method">
                <option>Segregate &amp; hand over to LA</option>
                <option>Recycling partner collection</option>
                <option>Composting on site</option>
              </select>
            </div>
            <div class="field">
              <label class="field__label" for="disposal_partner">Disposal Partner</label>
              <input class="field__input" id="disposal_partner" name="disposal_partner"
                     placeholder="e.g. Ruwanwella Pradeshiya Sabha" value="<?= old('disposal_partner') ?>">
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="collection_point">Waste Collection Point</label>
            <input class="field__input" id="collection_point" name="collection_point"
                   placeholder="e.g. Ruwanwella Town Hall Parking Area" value="<?= old('collection_point') ?>">
          </div>

          <div class="cl-checks" style="grid-template-columns:1fr">
            <label class="cl-check"><input type="checkbox" checked> Upload before &amp; after photographs at event close</label>
            <label class="cl-check"><input type="checkbox" checked> Generate impact report automatically after completion</label>
          </div>
        </div>
      </section>

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">6</span>
          <div><strong>Communication &amp; Recognition</strong><small>Group chat, notifications and volunteer certificates</small></div>
        </div>
        <div class="cl-sec__body">
          <div class="cl-checks" style="grid-template-columns:1fr">
            <label class="cl-check"><input type="checkbox" checked> Automatically add volunteers to the campaign group chat</label>
            <label class="cl-check"><input type="checkbox" checked> Send confirmation email on successful registration</label>
            <label class="cl-check"><input type="checkbox" checked> Issue digital appreciation certificates on completion</label>
          </div>

          <div class="field-row" style="margin-top:var(--s-4)">
          </div>
        </div>
      </section>

      <section class="cl-sec">
        <div class="cl-sec__head">
          <span class="cl-sec__num">7</span>
          <div><strong>Publishing</strong><small>Every approved campaign is public to volunteers</small></div>
        </div>
        <div class="cl-sec__body">
          <label class="cl-check" style="margin-top:var(--s-4)">
            <input type="checkbox" name="confirm" value="1" checked>
            I confirm these details are accurate and comply with platform policies.
          </label>

          <div class="cl-note cl-note--green" style="margin-top:var(--s-4)">
            Saving stores this campaign as <strong>Pending Approval</strong>. It becomes visible to
            volunteers only after administrator verification, and editing an approved campaign sends
            it back for checking.
          </div>
        </div>
      </section>
    </div>
  </div>

  <div class="cl-form-foot">
    <p>Step 2 of 2 - safety, equipment, waste management and publishing.</p>
    <a class="btn btn--outline" href="<?= url($stepUrl . '?step=1') ?>">Back</a>
    <button class="btn btn--primary" type="submit">
      <?= $editing ? 'Save Changes' : 'Submit for Verification' ?>
    </button>
  </div>

<?php endif; ?>
</form>

