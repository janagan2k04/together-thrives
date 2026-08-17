<?php
/**
 * "Create New Blood Campaign" / edit — from UI/Blood.
 *
 * Scoped to the BloodCampaign entity only: the "Critical Blood Request"
 * campaign-type option, the screening-form dropdown, eligibility toggles and
 * "Share eligible donor contacts" belong to CriticalRequestController and
 * ScreeningController, not this CRUD, so they are left out here — flagged to
 * the team lead to confirm before adding. Same for "Full Address" and
 * "End Date": there is no column for either in blood_campaigns.
 */
require_once APP_PATH . '/views/partials/icons.php';

$isEdit = $row !== null;
$value  = function (string $field, string $default = '') use ($row) {
    $old = old($field);
    if ($old !== '') return $old;
    return $row[$field] ?? $default;
};

// blood_types has no column to save to — collected for the admin's context
// only, same call the team made on the week 2 registration forms.
$checkedGroups = old('blood_types') ?: ($row['blood_types'] ?? []);
?>

<div class="page-wrap">

  <a class="blood-back" href="<?= url($isEdit ? '/my/blood' : '/blood') ?>">
    <?= icon('arrow-left') ?>Back to Blood Campaigns
  </a>

  <div class="page-header">
    <div>
      <h1><?= e($title) ?></h1>
      <p class="page-header__sub">
        Publish a blood donation camp. An admin fact-checks it before it goes live.
      </p>
    </div>
  </div>

  <form method="post" action="<?= e($action) ?>">
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
              <label class="field__label" for="title">Campaign Title <span class="req">*</span></label>
              <input class="field__input" id="title" name="title" maxlength="160"
                     placeholder="e.g. World Blood Donor Day Drive" value="<?= e($value('title')) ?>">
              <span class="field__error"><?= error_for('title') ?></span>
            </div>

            <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="description">Description <span class="req">*</span></label>
              <textarea class="field__textarea" id="description" name="description" maxlength="2000"
                        placeholder="Describe the purpose of the campaign and who it helps..."><?= e($value('description')) ?></textarea>
              <span class="field__error"><?= error_for('description') ?></span>
            </div>

            <div class="field">
              <label class="field__label">Blood Types Needed <span class="req">*</span></label>
              <div class="group-pick">
                <?php foreach ($groups as $g): ?>
                  <label>
                    <input type="checkbox" name="blood_types[]" value="<?= e($g) ?>"
                           <?= in_array($g, $checkedGroups, true) ? 'checked' : '' ?>>
                    <span><?= e($g) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <span class="field__hint">Collected for donor matching context; not stored on the campaign record.</span>
            </div>

            <div class="field-row">
              <div class="field <?= has_error('target_donors') ? 'field--invalid' : '' ?>">
                <label class="field__label" for="target_donors">Units / Donors Required <span class="req">*</span></label>
                <input class="field__input" id="target_donors" name="target_donors" type="number" min="1"
                       placeholder="e.g. 150" value="<?= e($value('target_donors')) ?>">
                <span class="field__error"><?= error_for('target_donors') ?></span>
              </div>

              <div class="field <?= has_error('contact_phone') ? 'field--invalid' : '' ?>">
                <label class="field__label" for="contact_phone">Contact Number <span class="req">*</span></label>
                <input class="field__input" id="contact_phone" name="contact_phone"
                       placeholder="+94 7X XXX XXXX" value="<?= e($value('contact_phone')) ?>">
                <span class="field__error"><?= error_for('contact_phone') ?></span>
              </div>
            </div>
          </div>
        </section>

        <section class="sec">
          <div class="sec__head">
            <div><strong>Location &amp; Schedule</strong>
              <small>City is used to notify nearby donors (city-based matching).</small></div>
          </div>
          <div class="sec__body">

            <div class="field-row">
              <div class="field <?= has_error('venue') ? 'field--invalid' : '' ?>">
                <label class="field__label" for="venue">Venue / Organizer <span class="req">*</span></label>
                <input class="field__input" id="venue" name="venue" maxlength="255"
                       placeholder="e.g. Sri Lanka Red Cross Society" value="<?= e($value('venue')) ?>">
                <span class="field__error"><?= error_for('venue') ?></span>
              </div>

              <div class="field <?= has_error('city_id') ? 'field--invalid' : '' ?>">
                <label class="field__label" for="city_id">City <span class="req">*</span></label>
                <select class="field__select" id="city_id" name="city_id">
                  <option value="">Select city</option>
                  <?php foreach ($cities as $city): ?>
                    <option value="<?= e($city['id']) ?>" <?= (string) $value('city_id') === (string) $city['id'] ? 'selected' : '' ?>>
                      <?= e($city['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <span class="field__error"><?= error_for('city_id') ?></span>
              </div>
            </div>

            <div class="field-row">
              <div class="field <?= has_error('campaign_date') ? 'field--invalid' : '' ?>">
                <label class="field__label" for="campaign_date">Date <span class="req">*</span></label>
                <input class="field__input" id="campaign_date" name="campaign_date" type="date"
                       value="<?= e($value('campaign_date')) ?>">
                <span class="field__error"><?= error_for('campaign_date') ?></span>
              </div>
            </div>

            <div class="field-row">
              <div class="field <?= has_error('start_time') ? 'field--invalid' : '' ?>">
                <label class="field__label" for="start_time">Time From <span class="req">*</span></label>
                <input class="field__input" id="start_time" name="start_time" type="time"
                       value="<?= e($value('start_time')) ?>">
                <span class="field__error"><?= error_for('start_time') ?></span>
              </div>

              <div class="field <?= has_error('end_time') ? 'field--invalid' : '' ?>">
                <label class="field__label" for="end_time">Time To <span class="req">*</span></label>
                <input class="field__input" id="end_time" name="end_time" type="time"
                       value="<?= e($value('end_time')) ?>">
                <span class="field__error"><?= error_for('end_time') ?></span>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div class="stack">
        <section class="sec">
          <div class="sec__head">
            <div><strong>Communication</strong></div>
          </div>
          <div class="sec__body">
            <label class="field" style="display:flex;align-items:center;gap:10px;cursor:pointer">
              <input type="checkbox" name="auto_group_chat" value="1"
                     <?= (string) $value('auto_group_chat', '1') === '1' ? 'checked' : '' ?>>
              <span>
                <strong style="display:block;font-size:var(--fs-sm);color:var(--ink)">
                  Auto-create campaign group chat
                </strong>
                <small style="color:var(--muted-3)">
                  Eligible donors are added so you can post updates.
                </small>
              </span>
            </label>
          </div>
        </section>
      </div>
    </div>

    <div class="form-foot">
      <p>Your campaign is submitted to an admin for fact-checking before it is published.</p>
      <a class="btn btn--outline" href="<?= url($isEdit ? '/my/blood' : '/blood') ?>">Cancel</a>
      <button class="btn btn--blood" type="submit">
        <?= $isEdit ? 'Save Changes' : 'Submit for Verification' ?>
      </button>
    </div>
  </form>
</div>