<?php
/** Disaster reports raised by this Grama Niladhari division. */
require_once APP_PATH . '/views/partials/icons.php';

$tone = [
    'Verified'             => 'active',
    'Pending Verification' => 'pending',
    'Rejected'             => 'rejected',
];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Disaster Reports</h1>
    <p>Every report your division has filed, and where each one stands.</p>
  </div>
  <div class="page-head__actions">
    <a class="btn btn--primary" href="<?= url('/gn/disasters/create') ?>">Report a Disaster</a>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/gn/disasters') ?>">
  <select name="status">
    <option>All Status</option>
    <option>Pending Verification</option>
    <option>Verified</option>
    <option>Rejected</option>
  </select>
  <select name="severity">
    <option>All Severities</option>
    <option>Low</option><option>Medium</option><option>High</option><option>Critical</option>
  </select>
</form>

<div class="panel">
  <table class="data-table">
    <thead>
      <tr><th>Status</th><th>Report</th><th>Location</th><th>Severity</th><th>Reported On</th><th>Impact</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($reports as $r): ?>
        <tr>
          <td><span class="pill pill--<?= e($tone[$r['status']] ?? 'neutral') ?>"><?= e($r['status']) ?></span></td>
          <td>
            <div class="cell-title"><?= e($r['title']) ?></div>
            <div class="cell-sub"><?= e($r['id']) ?> · <?= e($r['type']) ?></div>
          </td>
          <td>
            <div><?= e($r['place']) ?></div>
            <div class="cell-sub"><?= e($r['district']) ?></div>
          </td>
          <td><span class="pill pill--<?= $r['severity'] === 'High' ? 'rejected' : 'neutral' ?>"><?= e($r['severity']) ?></span></td>
          <td><?= e($r['on']) ?><div class="cell-sub"><?= e($r['at']) ?></div></td>
          <td>
            <?= e($r['families']) ?> families
            <div class="cell-sub"><?= e($r['displaced']) ?> displaced</div>
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
</div>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  Sample reports for the interim. <code>disaster_reports</code> is in the frozen schema; the
  create form validates against its real ENUMs, but nothing is written yet.
</p>
