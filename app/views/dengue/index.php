<?php
/**
 * "Dengue Risk Alerts" — the same screen for every role. `Dengue - Admin` and
 * `Dengue - Grama Niladhari` are the same frame in two shells, so one view
 * serves both; `$isAdmin` only decides where the form posts and whether the
 * moderation column is drawn.
 */
require_once APP_PATH . '/views/partials/icons.php';

$action = $isAdmin ? '/admin/dengue' : '/dengue';
$head   = $isAdmin ? 'admin-head' : 'page-head';
?>

<div class="<?= e($head) ?>">
  <div class="<?= e($head) ?>__text">
    <h1>Dengue Risk Alerts</h1>
    <p>Report dengue-prone areas in your locality and see alerts raised by others.</p>
  </div>
  <a class="btn btn--brand" href="#raise">Raise Dengue Alert</a>
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
    <div class="panel__body panel__body--flush">
      <?php foreach ($alerts as $a): ?>
        <article class="alert-item">
          <div class="alert-item__top">
            <div style="flex:1">
              <h3><?= e($a['area']) ?></h3>
              <span class="alert-item__where"><?= icon('map-pin') ?><?= e($a['district']) ?></span>
            </div>
            <?php if ($a['mine']): ?>
              <span class="badge badge--neutral">Your alert</span>
            <?php endif; ?>
          </div>

          <p class="alert-item__body"><?= e($a['body']) ?></p>

          <div class="alert-item__by">
            <span class="avatar"><?= e($a['initials']) ?></span>
            <div style="flex:1">
              <strong><?= e($a['by']) ?></strong>
              <small><?= e($a['byRole']) ?> · <?= e($a['when']) ?></small>
            </div>
            <?php if ($isAdmin): ?>
              <button class="btn-mini btn-mini--danger" type="button" disabled>Remove</button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <span class="panel__foot" style="color:var(--muted-3)">No older alerts</span>
  </section>

  <div class="stack">
    <section class="panel" id="raise">
      <div class="panel__head"><?= icon('bug') ?><h2>Seen a dengue risk?</h2></div>
      <div class="panel__body">
        <p class="muted" style="margin-bottom:var(--s-4)">
          Report standing water, blocked drains or a cluster of cases. Name the area and pick
          the district it belongs to.
        </p>

        <form method="post" action="<?= url($action) ?>">
          <?= csrf_field() ?>

          <div class="field <?= has_error('area') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="area">Area</label>
            <input class="field__input" id="area" name="area"
                   placeholder="e.g. Kelaniya &amp; Peliyagoda" value="<?= old('area') ?>">
            <span class="field__error"><?= error_for('area') ?></span>
          </div>

          <div class="field <?= has_error('district_id') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="district_id">District</label>
            <select class="field__select" id="district_id" name="district_id">
              <option value="">Select a district</option>
              <?php foreach ($districts as $d): ?>
                <option value="<?= e($d['id']) ?>" <?= old('district_id') === (string) $d['id'] ? 'selected' : '' ?>>
                  <?= e($d['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="field__error"><?= error_for('district_id') ?></span>
          </div>

          <div class="field <?= has_error('details') ? 'field--invalid' : '' ?>">
            <label class="field__label" for="details">What did you see?</label>
            <textarea class="field__textarea" id="details" name="details"
                      placeholder="Standing water, blocked drains, confirmed cases…"><?= old('details') ?></textarea>
            <span class="field__error"><?= error_for('details') ?></span>
          </div>

          <button class="btn btn--brand btn--block" type="submit">Raise Dengue Alert</button>
        </form>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><h2>Most Reported Districts</h2></div>
      <div class="panel__body">
        <div class="mini-list">
          <?php foreach ($reported as $name => $count): ?>
            <div><span><?= e($name) ?></span><span><?= e($count) ?> alerts</span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

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

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  Alerts are interim sample content. <code>dengue_risk_districts</code> is in the schema and
  the form validates against the real district list, but nothing is written yet.
</p>
