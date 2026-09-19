<?php
/**
 * Shared by Donate an Item and Edit Listing - "Donate an Item" frame from
 * UI/Reuse/Reuse.svg. Expects $item (null when creating), $categories,
 * $cities, $conditions and $images.
 *
 * old() wins over the stored row, so a rejected submission comes back with
 * what the donor typed rather than what is in the database.
 */
$isEdit  = $item !== null;
$images  = $images ?? [];
$value   = function (string $field, string $default = '') use ($item) {
    $old = old($field);
    if ($old !== '') return $old;
    return $item[$field] ?? $default;
};
?>

<div class="card">
  <h2 class="form-section">Item Details</h2>

  <div class="field <?= has_error('title') ? 'field--invalid' : '' ?>">
    <label class="field__label" for="title">Item Name <span class="req">*</span></label>
    <input class="field__input" id="title" name="title" maxlength="160"
           placeholder="e.g. Wooden Dining Table" value="<?= e($value('title')) ?>">
    <span class="field__error"><?= error_for('title') ?></span>
  </div>

  <div class="field-row">
    <div class="field <?= has_error('category_id') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="category_id">Category <span class="req">*</span></label>
      <select class="field__select" id="category_id" name="category_id">
        <option value="">Select category</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= e($c['id']) ?>" <?= (string) $value('category_id') === (string) $c['id'] ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <span class="field__error"><?= error_for('category_id') ?></span>
    </div>

    <div class="field <?= has_error('item_condition') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="item_condition">Condition <span class="req">*</span></label>
      <select class="field__select" id="item_condition" name="item_condition">
        <option value="">Select condition</option>
        <?php foreach ($conditions as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= (string) $value('item_condition') === (string) $key ? 'selected' : '' ?>>
            <?= e($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <span class="field__error"><?= error_for('item_condition') ?></span>
    </div>
  </div>

  <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
    <label class="field__label" for="description">Description <span class="req">*</span></label>
    <textarea class="field__textarea" id="description" name="description" maxlength="2000"
              placeholder="Describe the item - its size, colour, working condition and any defects."><?= e($value('description')) ?></textarea>
    <span class="field__error"><?= error_for('description') ?></span>
  </div>

  <div class="field <?= has_error('quantity') ? 'field--invalid' : '' ?>">
    <label class="field__label" for="quantity">Quantity <span class="req">*</span></label>
    <input class="field__input" id="quantity" name="quantity" type="number" min="1"
           value="<?= e($value('quantity', '1')) ?>">
    <span class="field__hint">How many of this item you are giving away.</span>
    <span class="field__error"><?= error_for('quantity') ?></span>
  </div>

  <div class="field <?= has_error('photos') ? 'field--invalid' : '' ?>">
    <label class="field__label">Photos <?= $isEdit ? '' : '<span class="req">*</span>' ?></label>

    <?php if ($images): ?>
      <div class="photo-existing">
        <?php foreach ($images as $img): ?>
          <div class="photo-existing__item">
            <img src="<?= url('/reuse/image/' . $img['id']) ?>" alt="">
            <label>
              <input type="checkbox" name="remove_images[]" value="<?= e($img['id']) ?>">
              Remove
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <label class="photo-drop" for="photos">
      <?= icon('upload-cloud') ?>
      <p class="photo-drop__text">Drag photos here or <b>click to browse</b></p>
      <p class="photo-drop__hint">PNG or JPG · up to <?= \Services\ReuseService::MAX_PHOTOS ?> images · max 5MB each</p>
      <input type="file" id="photos" name="photos[]" accept="image/png,image/jpeg,image/webp" multiple>
    </label>
    <span class="field__error"><?= error_for('photos') ?></span>
  </div>

  <h2 class="form-section form-section--spaced">Pickup &amp; Location</h2>

  <div class="field-row">
    <div class="field <?= has_error('city_id') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="city_id">District <span class="req">*</span></label>
      <select class="field__select" id="city_id" name="city_id">
        <option value="">Select your district</option>
        <?php foreach ($cities as $city): ?>
          <option value="<?= e($city['id']) ?>" <?= (string) $value('city_id') === (string) $city['id'] ? 'selected' : '' ?>>
            <?= e($city['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <span class="field__error"><?= error_for('city_id') ?></span>
    </div>

    <div class="field <?= has_error('pickup_note') ? 'field--invalid' : '' ?>">
      <label class="field__label" for="pickup_note">Pickup Point / Area</label>
      <input class="field__input" id="pickup_note" name="pickup_note" maxlength="255"
             placeholder="e.g. Nugegoda, near the market" value="<?= e($value('pickup_note')) ?>">
      <span class="field__error"><?= error_for('pickup_note') ?></span>
    </div>
  </div>

  <div class="form-actions">
    <a class="btn btn--outline" href="<?= url($isEdit ? '/my/reuse' : '/reuse') ?>">Cancel</a>
    <button class="btn btn--primary" type="submit">
      <?= $isEdit ? 'Save Changes' : 'Donate an Item' ?>
    </button>
  </div>
</div>
