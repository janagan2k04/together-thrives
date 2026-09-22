<?php
/**
 * "Donation - Create Donation Request (Org)" and "… · Funds (Org)" - frames 2
 * and 5 of Donation-Org.svg, plus the edit form. One file for all of them:
 * `$type` picks which half of the design renders, `$request` is null when
 * creating.
 *
 * Relief Items and Monetary Funds are TWO RADIO BUTTONS over two panels drawn
 * into the same form, not two URLs. They used to be links carrying ?type=, which
 * meant switching reloaded the page and threw away everything already typed into
 * section 1 - a requester who filled in the disaster, the district and the
 * description and then changed their mind about the kind of appeal lost all
 * three. The panel that does not apply is `hidden`; the one that does is
 * submitted, and `request_type` says which. See `[data-type-switch]` in
 * assets/js/core/app.js.
 *
 * `$type` still decides which panel opens - from ?type= when creating and from
 * the stored row when editing - so the form works with the query string as
 * before and simply no longer needs it to change.
 *
 * Submitting writes a `donation_requests` row, and each item line writes a
 * `requested_items` row.
 *
 * Fields the Figma draws that have no column anywhere in the frozen schema -
 * number of affected people, urgency per request and per item, disaster type,
 * minimum donation, additional instructions, payout bank and account, and the
 * funds breakdown - were removed on 2026-09-04. They used to be drawn with a
 * hint admitting the input was thrown away, which is a worse answer than not
 * asking: a requester who fills in a payout account has every reason to think
 * the platform has it. Storing any of them needs a schema change and a group
 * decision.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Donation\DonationRequest;


$editing = $request !== null;
$r       = $request ?? [];
$isItems = $type === 'items';

/** A DECIMAL column comes back as "1000000.00"; the number input wants a plain integer. */
$amount = isset($r['target_amount']) && $r['target_amount'] !== null
    ? (string) (int) $r['target_amount']
    : '';

/*
 * After a failed submit the item table has to come back as the requester left
 * it. `old()` only returns scalars, so the three parallel arrays are read
 * straight out of the flashed input and rebuilt into rows; falling back to the
 * stored lines, and then to the blank rows the controller supplies.
 */
$oldInput = form_state()['old'];
$spare    = 3;   // blank rows to name items in; the create form is all spare
if (!empty($oldInput['item_name']) && is_array($oldInput['item_name'])) {
    $rows = [];
    foreach ($oldInput['item_name'] as $i => $name) {
        $rows[] = [
            'item_name'    => (string) $name,
            'unit'         => (string) ($oldInput['unit'][$i] ?? ''),
            'qty_required' => (string) ($oldInput['qty_required'][$i] ?? ''),
        ];
    }
    // The posted set already carries last time's spare rows; adding more would
    // grow the table by three on every failed submit.
    $spare = 0;
}
?>

<a class="back-link" href="<?= url('/my/donations') ?>"><?= icon('arrow-left') ?>Back to Donation Requests</a>

<div class="don-head">
  <div class="don-head__text">
    <h1><?= $editing ? 'Edit Donation Request' : 'Create Donation Request' ?></h1>
    <p data-type-panel="items" <?= $isItems ? '' : 'hidden' ?>>
      Request relief items for a disaster-affected area. An admin approves it before it is published.
    </p>
    <p data-type-panel="funds" <?= $isItems ? 'hidden' : '' ?>>
      Request monetary donations for a disaster-affected area. An admin approves it before it is published.
    </p>
  </div>
</div>

<?php if ($editing && $r['status'] === REQUEST_OPEN): ?>
  <div class="don-note don-note--amber" style="margin-bottom:var(--s-4)">
    <?= icon('shield-check') ?>
    This request is live. Saving changes returns it to the admin queue before donors see it again.
  </div>
<?php endif; ?>

<?php if (has_error('items')): ?>
  <div class="alert alert--error" style="margin-bottom:var(--s-4)"><?= error_for('items') ?></div>
<?php endif; ?>

<form method="post" action="<?= url($action) ?>">
  <?= csrf_field() ?>
  <?php if ($method === 'PUT'): ?><?= method_field('PUT') ?><?php endif; ?>

  <div class="split">
    <div class="stack">

      <!-- 1 ................................................. -->
      <section class="don-sec">
        <div class="don-sec__head">
          <span class="don-sec__num">1.</span>
          <strong>Disaster &amp; Area Information</strong>
          <small>Where the relief is needed</small>
        </div>
        <div class="don-sec__body">

          <div class="field <?= has_error('title') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="title">Request Title *</label>
            <input class="field__input" id="title" name="title" maxlength="160"
                   placeholder="e.g. Flood Relief - Ratnapura"
                   value="<?= old('title', $r['title'] ?? '') ?>">
            <span class="field__error"><?= error_for('title') ?></span>
            <span class="field__hint">The name donors see on every list and card.</span>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('city_id') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="city_id">District *</label>
              <?php $cityId = old('city_id', $r['city_id'] ?? ''); ?>
              <select class="field__select" id="city_id" name="city_id">
                <option value="">Select district</option>
                <?php foreach ($cities as $c): ?>
                  <option value="<?= e($c['id']) ?>" <?= $cityId === (string) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="field__error"><?= error_for('city_id') ?></span>
              <span class="field__hint">The district is the only location stored, so it is not asked twice.</span>
            </div>
          </div>

          <div class="field-row">
            <div class="field <?= has_error('affected_area') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="affected_area">Affected Area / Location *</label>
              <input class="field__input" id="affected_area" name="affected_area" maxlength="255"
                     placeholder="Enter affected area"
                     value="<?= old('affected_area', $r['affected_area'] ?? '') ?>">
              <span class="field__error"><?= error_for('affected_area') ?></span>
            </div>
          </div>

          <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="description">Description of the Situation *</label>
            <textarea class="field__textarea" id="description" name="description" maxlength="500"
                      placeholder="Briefly describe the disaster and the current situation…"><?= old('description', $r['description'] ?? '') ?></textarea>
            <span class="field__error"><?= error_for('description') ?></span>
            <span class="field__hint">Up to 500 characters. This is the summary donors see on the card.</span>
          </div>

          <div class="field <?= has_error('disaster_details') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="disaster_details">Detailed Situation Report</label>
            <textarea class="field__textarea" id="disaster_details" name="disaster_details" maxlength="2000"
                      style="min-height:120px"
                      placeholder="The fuller account donors read on the request page…"><?= old('disaster_details', $r['disaster_details'] ?? '') ?></textarea>
            <span class="field__error"><?= error_for('disaster_details') ?></span>
            <span class="field__hint">Optional. Printed in full on the request page.</span>
          </div>
        </div>
      </section>

      <!-- 2 ................................................. -->
      <section class="don-sec">
        <div class="don-sec__head">
          <span class="don-sec__num">2.</span>
          <strong data-type-panel="items" <?= $isItems ? '' : 'hidden' ?>>What You Need</strong>
          <small  data-type-panel="items" <?= $isItems ? '' : 'hidden' ?>>Items and quantities</small>
          <strong data-type-panel="funds" <?= $isItems ? 'hidden' : '' ?>>Funding Requirement</strong>
          <small  data-type-panel="funds" <?= $isItems ? 'hidden' : '' ?>>Target and contact</small>
        </div>
        <div class="don-sec__body">

          <div class="field">
            <span class="field__label">Request Type *</span>
            <?php /* Radios, not links: switching must not reload the page and
                      throw away everything already typed into section 1. app.js
                      shows the matching [data-type-panel] blocks. */ ?>
            <div class="don-toggle">
              <label class="<?= $isItems ? 'is-active' : '' ?>">
                <input type="radio" name="request_type" value="items"
                       data-type-switch <?= $isItems ? 'checked' : '' ?>>
                <?= icon('inbox') ?>Relief Items
              </label>
              <label class="<?= $isItems ? '' : 'is-active' ?>">
                <input type="radio" name="request_type" value="funds"
                       data-type-switch <?= $isItems ? '' : 'checked' ?>>
                <?= icon('gift') ?>Monetary Funds
              </label>
            </div>
            <span class="field__hint">
              Only the section for the type you choose is saved - a fund request has no pickup
              point, an item request has no target amount.
            </span>
          </div>

          <div data-type-panel="items" <?= $isItems ? '' : 'hidden' ?>>
            <table class="don-rows">
              <thead>
                <tr>
                  <th>Item Needed</th>
                  <th style="width:150px">Quantity Needed</th>
                  <th style="width:140px">Unit</th>
                </tr>
              </thead>
              <tbody>
                <?php /* The item name is free text. `requested_items.item_name`
                          is a VARCHAR and no fixed list covers what an appeal can
                          actually ask for - the same call the cleanup module made
                          on volunteer skills. */ ?>
                <?php foreach ($rows as $row): ?>
                  <tr>
                    <td>
                      <input name="item_name[]" maxlength="120" placeholder="e.g. Dry rations"
                             value="<?= e($row['item_name']) ?>">
                    </td>
                    <td>
                      <input name="qty_required[]" type="number" min="0"
                             value="<?= e($row['qty_required']) ?>">
                    </td>
                    <td>
                      <input name="unit[]" maxlength="30" placeholder="packs"
                             value="<?= e($row['unit'] ?? '') ?>">
                    </td>
                  </tr>
                <?php endforeach; ?>

                <?php /* Spare rows, so a requester can add items without JavaScript.
                          A row with no quantity is ignored on submit. */ ?>
                <?php for ($i = 0; $i < $spare; $i++): ?>
                  <tr>
                    <td><input name="item_name[]" maxlength="120" placeholder="e.g. Dry rations"></td>
                    <td><input name="qty_required[]" type="number" min="0" placeholder="0"></td>
                    <td><input name="unit[]" maxlength="30" placeholder="packs"></td>
                  </tr>
                <?php endfor; ?>
              </tbody>
            </table>

            <p class="field__hint" style="margin-top:var(--s-3)">
              Name each thing you need and how much of it. Leave a row's quantity at zero to skip
              it. Donors reserve against each line, and you confirm each delivery as it arrives.
            </p>
          </div>

          <div data-type-panel="funds" <?= $isItems ? 'hidden' : '' ?>>
            <div class="field-row">
              <div class="field <?= has_error('target_amount') ? 'field--invalid' : '' ?>">
                <label class="field__label" for="target_amount">Target Amount (LKR) *</label>
                <input class="field__input" id="target_amount" name="target_amount" type="number" min="1000"
                       placeholder="1000000" value="<?= old('target_amount', $amount) ?>">
                <span class="field__error"><?= error_for('target_amount') ?></span>
              </div>
            </div>

            <div class="field-row">
              <div class="field">
                <label class="field__label" for="contact_number">Contact Number</label>
                <input class="field__input" id="contact_number" name="contact_number"
                       placeholder="+94 __ ___ ____" value="<?= old('contact_number') ?>">
                <span class="field__hint">Donors are shown the phone on your account, not this field.</span>
              </div>
            </div>

            <p class="don-note don-note--blue">
              <?= icon('help-circle') ?>Donations are collected through the platform's secure payment
              gateway. A donation is recorded only after payment is confirmed.
            </p>
          </div>
        </div>
      </section>
    </div>

    <div class="stack">

      <!-- 3 ................................................. -->
      <section class="don-sec">
        <div class="don-sec__head">
          <span class="don-sec__num">3.</span>
          <strong data-type-panel="items" <?= $isItems ? '' : 'hidden' ?>>Collection &amp; Logistics</strong>
          <strong data-type-panel="funds" <?= $isItems ? 'hidden' : '' ?>>Collection Period &amp; Payout</strong>
        </div>
        <div class="don-sec__body">
          <div class="field-row">
            <div class="field <?= has_error('starts_on') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="starts_on">Start Date *</label>
              <input class="field__input" id="starts_on" name="starts_on" type="date"
                     value="<?= old('starts_on', $r['starts_on'] ?? '') ?>">
              <span class="field__error"><?= error_for('starts_on') ?></span>
            </div>
            <div class="field <?= has_error('ends_on') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="ends_on">End Date *</label>
              <input class="field__input" id="ends_on" name="ends_on" type="date"
                     value="<?= old('ends_on', $r['ends_on'] ?? '') ?>">
              <span class="field__error"><?= error_for('ends_on') ?></span>
            </div>
          </div>

          <div data-type-panel="items" <?= $isItems ? '' : 'hidden' ?>>
            <div class="field <?= has_error('pickup_point') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="pickup_point">Pickup Point / Location *</label>
              <input class="field__input" id="pickup_point" name="pickup_point" maxlength="255"
                     placeholder="Enter pickup point"
                     value="<?= old('pickup_point', $r['pickup_point'] ?? '') ?>">
              <span class="field__error"><?= error_for('pickup_point') ?></span>
            </div>
          </div>

          <div data-type-panel="funds" <?= $isItems ? 'hidden' : '' ?>>
            <p class="don-note don-note--amber" style="margin-top:var(--s-3)">
              <?= icon('shield-check') ?>Funds are transferred only to a verified organisation
              account after admin approval.
            </p>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('help-circle') ?><h2>What happens next</h2></div>
        <div class="panel__body">
          <div class="don-next">
            <div class="don-next__row">
              <span class="don-next__icon"><?= icon('shield-check') ?></span>
              <span><strong>Admin review</strong><small>An administrator verifies your request before publication.</small></span>
            </div>
            <div class="don-next__row">
              <span class="don-next__icon"><?= icon('megaphone') ?></span>
              <span><strong>Published &amp; notified</strong><small>Approved requests appear to donors in the affected district.</small></span>
            </div>
            <div class="don-next__row" data-type-panel="items" <?= $isItems ? '' : 'hidden' ?>>
              <span class="don-next__icon"><?= icon('inbox') ?></span>
              <span><strong>Donors reserve items</strong><small>Quantities update automatically and close when fulfilled.</small></span>
            </div>
            <div class="don-next__row" data-type-panel="items" <?= $isItems ? '' : 'hidden' ?>>
              <span class="don-next__icon"><?= icon('check-circle') ?></span>
              <span><strong>Confirm receipt</strong><small>You confirm each delivery as it arrives.</small></span>
            </div>
            <div class="don-next__row" data-type-panel="funds" <?= $isItems ? 'hidden' : '' ?>>
              <span class="don-next__icon"><?= icon('gift') ?></span>
              <span><strong>Donors contribute</strong><small>Paid via the secure sandbox gateway.</small></span>
            </div>
            <div class="don-next__row" data-type-panel="funds" <?= $isItems ? 'hidden' : '' ?>>
              <span class="don-next__icon"><?= icon('check-circle') ?></span>
              <span><strong>Funds transferred</strong><small>Released to your verified account.</small></span>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>

  <div class="don-form-foot">
    <p>Your request is reviewed by an admin before it becomes visible to donors.</p>
    <a class="btn btn--outline" href="<?= url($editing ? '/my/donations/' . $r['id'] : '/my/donations') ?>">Cancel</a>
    <button class="btn btn--brand"><?= $editing ? 'Save Changes' : 'Submit' ?></button>
  </div>
</form>
