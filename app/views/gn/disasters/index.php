<?php
/**
 * Disaster reports - the officer's own division, or every report on the
 * platform when an administrator opens the same screen ($mine says which).
 *
 * Real `disaster_reports` rows since 2026-08-27 - it drew Services\SampleData
 * until then, which meant the GN dashboard's counter and this list disagreed
 * with each other and with the database.
 */
require_once APP_PATH . '/views/partials/icons.php';

$tone = [
    MOD_APPROVED => 'active',
    MOD_PENDING  => 'pending',
    MOD_REJECTED => 'rejected',
];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Disaster Reports</h1>
    <p>
      <?= $mine
            ? 'Every report your division has filed, and where each one stands.'
            : 'Every disaster report filed on the platform, and where each one stands.' ?>
    </p>
  </div>
  <div class="page-head__actions">
    <a class="btn btn--primary" href="<?= url('/gn/disasters/create') ?>">Report a Disaster</a>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/gn/disasters') ?>">
  <select name="status" onchange="this.form.submit()">
    <option value="">All Status</option>
    <?php foreach ($statuses as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select name="severity" onchange="this.form.submit()">
    <option value="">All Severities</option>
    <?php foreach ($severities as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= ($filters['severity'] ?? '') === $value ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<div class="panel">
  <?php if (!$reports): ?>
    <div class="panel__body">
      <div class="empty-state">
        <?= $filters
              ? 'No report matches those filters.'
              : ($mine
                  ? 'Your division has not filed a disaster report yet.'
                  : 'No disaster report has been filed yet.') ?>
      </div>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Status</th><th>Report</th>
          <?php if (!$mine): ?><th>Filed by</th><?php endif; ?>
          <th>Location</th><th>Severity</th><th>Reported On</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
          <tr>
            <td>
              <span class="pill pill--<?= e($tone[$r['status']] ?? 'neutral') ?>">
                <?= e($statuses[$r['status']] ?? ucfirst($r['status'])) ?>
              </span>
            </td>
            <td>
              <div class="cell-title"><?= e($r['title']) ?></div>
              <div class="cell-sub">
                DR-<?= e(str_pad((string) $r['id'], 4, '0', STR_PAD_LEFT)) ?> ·
                <?= e($types[$r['disaster_type']] ?? $r['disaster_type']) ?>
              </div>
            </td>
            <?php if (!$mine): ?>
              <td><?= e($r['reporter']) ?></td>
            <?php endif; ?>
            <td>
              <div><?= e($r['location']) ?></div>
              <div class="cell-sub"><?= e($r['district']) ?></div>
            </td>
            <td>
              <span class="pill pill--<?= in_array($r['severity'], ['high', 'critical'], true) ? 'rejected' : 'neutral' ?>">
                <?= e($severities[$r['severity']] ?? ucfirst($r['severity'])) ?>
              </span>
            </td>
            <td>
              <?= e(format_date($r['created_at'])) ?>
              <div class="cell-sub"><?= e(time_ago($r['created_at'])) ?></div>
            </td>
            <td>
              <div class="cell-actions">
                <a class="btn-mini btn-mini--view" href="<?= url('/gn/disasters/' . $r['id']) ?>">View Details</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
