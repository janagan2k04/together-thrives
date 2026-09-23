<?php
/**
 * "Dengue Alert" - the same screen for every role. `Dengue - Admin` and
 * `Dengue - Grama Niladhari` are the same frame in two shells, so one view
 * serves both; `$isAdmin` only decides where the form posts.
 *
 * Real `dengue_risk_districts` rows. The table gained `address`, `description`
 * and the `dengue_alert_images` child table on 2026-09-18, so the Figma's
 * specific place, "what did you see?" and photographs are asked for and saved -
 * they used to be collected and dropped, and were then taken off the form.
 *
 * Photos are drawn from /dengue/image/{id}, by row id, not through
 * upload_url(): that route serves a file only to whoever uploaded it.
 */
require_once APP_PATH . '/views/partials/icons.php';

$action = $isAdmin ? '/admin/dengue' : '/dengue';
$head   = $isAdmin ? 'admin-head' : 'page-head';
$me     = is_logged_in() ? (int) auth()['id'] : 0;

/** Two letters for the avatar, from whatever the name gives us. */
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};
?>

<div class="<?= e($head) ?>">
  <div class="<?= e($head) ?>__text">
    <h1>Dengue Alert</h1>
    <p>Report dengue-prone districts and see alerts raised by others.</p>
  </div>
  <?php if ($me): ?>
    <a class="btn btn--brand" href="#raise">Raise Dengue Alert</a>
  <?php endif; ?>
</div>

<div class="stat-grid stat-grid--5">
  <?php foreach ($stats as [$value, $label, $tone]): ?>
    <div class="stat<?= $tone !== 'ink' ? ' stat--' . e($tone) : '' ?>">
      <div class="stat__value"><?= e($value) ?></div>
      <div class="stat__label"><?= e($label) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="split">
  <section class="panel">
    <div class="panel__head">
      <h2>Dengue Risk Alerts</h2>
      <small>Newest first</small>
    </div>

    <?php if (!$alerts): ?>
      <div class="panel__body">
        <div class="empty-state">
          <p>No alerts have been raised. If you have seen standing water or a cluster of cases,
             report the district and how many cases you know of.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="panel__body panel__body--flush">
        <?php foreach ($alerts as $a): ?>
          <article class="alert-item">
            <div class="alert-item__top">
              <div style="flex:1">
                <h3><?= e($a['district']) ?></h3>
                <span class="alert-item__where">
                  <?= icon('map-pin') ?>
                  <?= $a['address'] !== null && $a['address'] !== ''
                        ? e($a['address']) . ' · '
                        : '' ?>Reported <?= e(format_date($a['reported_on'])) ?>
                </span>
              </div>
              <span class="badge badge--<?= e($tones[$a['risk_level']] ?? 'neutral') ?>">
                <?= e($levels[$a['risk_level']] ?? $a['risk_level']) ?>
              </span>
              <?php if ((int) $a['added_by'] === $me): ?>
                <span class="badge badge--neutral">Your alert</span>
              <?php endif; ?>
            </div>

            <?php if ($a['description'] !== null && $a['description'] !== ''): ?>
              <p class="alert-item__body"><?= nl2br(e($a['description'])) ?></p>
            <?php endif; ?>

            <?php if ($a['case_count'] !== null): ?>
              <p class="alert-item__body">
                <?= e(number_format((int) $a['case_count'])) ?>
                case<?= (int) $a['case_count'] === 1 ? '' : 's' ?> reported in this district.
              </p>
            <?php endif; ?>

            <?php $shots = $photos[(int) $a['id']] ?? []; ?>
            <?php if ($shots): ?>
              <div class="alert-item__shots">
                <?php foreach ($shots as $shot): ?>
                  <a href="<?= url('/dengue/image/' . $shot['id']) ?>" target="_blank" rel="noopener">
                    <img src="<?= url('/dengue/image/' . $shot['id']) ?>"
                         alt="Photo of the reported site in <?= e($a['district']) ?>">
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="alert-item__by">
              <span class="avatar"><?= e($initials((string) $a['author'])) ?></span>
              <div style="flex:1">
                <strong><?= e($a['author']) ?></strong>
                <small>
                  <?= e(ucfirst(str_replace('_', ' ', (string) $a['author_role']))) ?> ·
                  <?= e(time_ago($a['created_at'])) ?>
                </small>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <div class="stack">
    <?php if ($me): ?>
      <section class="panel" id="raise">
        <div class="panel__head"><?= icon('bug') ?><h2>Seen a dengue risk?</h2></div>
        <div class="panel__body">
          <p class="muted" style="margin-bottom:var(--s-4)">
            Pick the district, say where exactly, describe what you saw, and add photos
            and a case count if you have them.
          </p>

          <form method="post" action="<?= url($action) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="field <?= has_error('district_id') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="district_id">District</label>
              <select class="field__select" id="district_id" name="district_id">
                <option value="">Select a district</option>
                <?php foreach ($districts as $d): ?>
                  <option value="<?= e($d['id']) ?>"
                          <?= old('district_id', (string) ($myDistrict ?? '')) === (string) $d['id'] ? 'selected' : '' ?>>
                    <?= e($d['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="field__error"><?= error_for('district_id') ?></span>
            </div>

            <div class="field <?= has_error('address') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="address">Specific address</label>
              <input class="field__input" id="address" name="address"
                     maxlength="<?= e(Models\DengueRiskDistrict::MAX_ADDRESS) ?>"
                     placeholder="Street, lane or landmark"
                     value="<?= old('address') ?>">
              <span class="field__hint">Where exactly - the district alone is too wide to act on.</span>
              <span class="field__error"><?= error_for('address') ?></span>
            </div>

            <div class="field <?= has_error('risk_level') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="risk_level">Risk level</label>
              <select class="field__select" id="risk_level" name="risk_level">
                <?php foreach ($levels as $value => $label): ?>
                  <option value="<?= e($value) ?>" <?= old('risk_level', 'high') === $value ? 'selected' : '' ?>>
                    <?= e($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="field__error"><?= error_for('risk_level') ?></span>
            </div>

            <div class="field <?= has_error('description') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="description">What did you see?</label>
              <textarea class="field__textarea" id="description" name="description"
                        maxlength="<?= e(Models\DengueRiskDistrict::MAX_DESCRIPTION) ?>"
                        placeholder="Standing water, discarded tyres, a cluster of fever cases"><?= old('description') ?></textarea>
              <span class="field__error"><?= error_for('description') ?></span>
            </div>

            <div class="field <?= has_error('photos') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="photos">Photos</label>
              <input class="field__input" id="photos" name="photos[]" type="file"
                     accept="image/jpeg,image/png,image/webp" multiple>
              <span class="field__hint">
                Up to <?= e(Models\DengueAlertImage::MAX_PHOTOS) ?> images, JPG, PNG or WebP, 5 MB each.
              </span>
              <span class="field__error"><?= error_for('photos') ?></span>
            </div>

            <div class="field <?= has_error('case_count') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="case_count">Cases known of</label>
              <input class="field__input" id="case_count" name="case_count" type="number" min="0"
                     placeholder="Leave blank if you do not know" value="<?= old('case_count') ?>">
              <span class="field__error"><?= error_for('case_count') ?></span>
            </div>

            <div class="field <?= has_error('reported_on') ? 'field--invalid' : '' ?>">
              <label class="field__label" for="reported_on">Reported on</label>
              <input class="field__input" id="reported_on" name="reported_on" type="date"
                     max="<?= e(date('Y-m-d')) ?>"
                     value="<?= old('reported_on', date('Y-m-d')) ?>">
              <span class="field__error"><?= error_for('reported_on') ?></span>
            </div>

            <button class="btn btn--brand btn--block" type="submit">Raise Dengue Alert</button>
          </form>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($reported): ?>
      <section class="panel">
        <div class="panel__head">
          <h2>Most Affected Districts</h2>
          <small>Latest case count per district</small>
        </div>
        <div class="panel__body">
          <div class="mini-list">
            <?php foreach ($reported as $name => $count): ?>
              <div><span><?= e($name) ?></span><span><?= e(number_format($count)) ?> cases</span></div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <section class="panel">
      <div class="panel__head"><h2>Prevention Reminders</h2></div>
      <div class="panel__body">
        <ul class="bullets">
          <?php foreach ($prevention as $p): ?>
            <li><?= e($p) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
  </div>
</div>
