<?php
/**
 * "Admin - Analytics & Reports" from admin.svg.
 *
 * The frame shows four headline figures over an empty canvas. These four are
 * real counts, and the module table below says plainly which modules have data
 * to report on yet and which do not — an analytics screen full of invented
 * totals would be worse than one that admits three modules are unbuilt.
 */
require_once APP_PATH . '/views/partials/icons.php';

$max = 0;
foreach ($registrations as $r) { $max = max($max, (int) $r['total']); }
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Analytics &amp; Reports</h1>
    <p>Platform-wide performance across all disaster response modules.</p>
  </div>
  <button class="btn btn--brand" type="button" disabled>Export</button>
</div>

<div class="stat-grid stat-grid--4">
  <?php foreach ($headline as [$label, $value]): ?>
    <div class="stat">
      <div class="stat__label"><?= e($label) ?></div>
      <div class="stat__value"><?= e($value) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="split">
  <section class="panel">
    <div class="panel__head"><h2>By module</h2><small>Rows currently stored</small></div>
    <div class="panel__body panel__body--flush">
      <table class="data-table">
        <thead><tr><th>Module</th><th>Backend</th><th>Records</th><th>Counting</th></tr></thead>
        <tbody>
          <?php foreach ($modules as [$name, $state, $count, $what]): ?>
            <tr>
              <td class="cell-title"><?= e($name) ?></td>
              <td>
                <span class="pill pill--<?= $state === 'live' ? 'active' : 'pending' ?>">
                  <?= $state === 'live' ? 'Live' : 'Weeks 4–5' ?>
                </span>
              </td>
              <td><?= e($count) ?></td>
              <td class="cell-sub"><?= e($what) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <div class="panel__head"><?= icon('bar-chart') ?><h2>Registrations per day</h2></div>
    <div class="panel__body">
      <?php if (!$registrations): ?>
        <div class="empty-state">No accounts yet.</div>
      <?php else: ?>
        <div class="mini-list">
          <?php foreach ($registrations as $r): ?>
            <div>
              <span><?= format_date($r['day']) ?></span>
              <span>
                <?php
                  // A bar chart drawn with a repeated block character. It scales to the
                  // busiest day so the shape stays readable whatever the numbers are.
                  $width = $max > 0 ? max(1, (int) round(((int) $r['total'] / $max) * 12)) : 0;
                ?>
                <span style="color:var(--brand);letter-spacing:-1px"><?= str_repeat('▉', $width) ?></span>
                <?= e($r['total']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  Every figure here is a live count. Volunteer hours, funds raised and campaign totals appear
  once the other three modules store records.
</p>
