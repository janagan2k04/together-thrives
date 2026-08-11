<?php
/**
 * "GN Dashboard" from GN.svg — the two counters, the disaster report table and
 * the donation request table, in that order.
 *
 * The disaster rows come from the same sample set the /gn/disasters screens
 * read, so the two agree. Donation requests belong to Member 2's module.
 */
require_once APP_PATH . '/views/partials/icons.php';

$reports = array_slice(\Services\SampleData::disasterReports(), 0, 3);

$donations = [
    ['status' => 'Pending Approval', 'tone' => 'pending', 'title' => 'Request for Food & Water',
     'note' => 'Urgently need dry food packs, drinking water and milk powder.',
     'type' => 'Items', 'need' => 'Food packs, water, milk powder',
     'period' => '26 May – 02 Jun 2025', 'progress' => '45%', 'sub' => '135 / 300 items received'],
    ['status' => 'Approved', 'tone' => 'success', 'title' => 'Request for Financial Support',
     'note' => 'Financial support for temporary shelter and medical needs.',
     'type' => 'Funds', 'need' => 'LKR 150,000.00',
     'period' => '22 May – 31 May 2025', 'progress' => '60%', 'sub' => 'LKR 90,000.00 received'],
];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Welcome back, <?= e($user['full_name'] ?? '') ?>!</h1>
    <p>Here's what's happening in your area.</p>
  </div>
</div>

<div class="stat-grid stat-grid--2" style="max-width:640px">
  <a class="stat stat--red" href="<?= url('/gn/disasters') ?>">
    <div class="stat__label">Active Disasters</div>
    <div class="stat__value">2</div>
    <div class="stat__hint">1 new report pending</div>
  </a>
  <a class="stat stat--blue" href="<?= url('/my/donations') ?>">
    <div class="stat__label">My Requests</div>
    <div class="stat__value">3</div>
    <div class="stat__hint">2 pending approval</div>
  </a>
</div>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__head">
    <h2>My Disaster Reports</h2>
    <a class="btn btn--primary" href="<?= url('/gn/disasters/create') ?>">Report a Disaster</a>
  </div>
  <div class="panel__body panel__body--flush">
    <table class="data-table">
      <thead>
        <tr><th>Status</th><th>Disaster Details</th><th>Location</th><th>Severity</th><th>Reported On</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
          <tr>
            <td>
              <span class="pill pill--<?= $r['status'] === 'Verified' ? 'active' : ($r['status'] === 'Rejected' ? 'rejected' : 'pending') ?>">
                <?= e($r['status']) ?>
              </span>
            </td>
            <td>
              <div class="cell-title"><?= e($r['title']) ?></div>
              <div class="cell-sub"><?= e(str_limit($r['body'], 60)) ?></div>
            </td>
            <td>
              <div><?= e($r['place']) ?></div>
              <div class="cell-sub"><?= e($r['district']) ?></div>
            </td>
            <td><span class="pill pill--<?= $r['severity'] === 'High' ? 'rejected' : 'neutral' ?>"><?= e($r['severity']) ?></span></td>
            <td><?= e($r['on']) ?><div class="cell-sub"><?= e($r['at']) ?></div></td>
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
  <a class="panel__foot" href="<?= url('/gn/disasters') ?>">View all reports</a>
</section>

<section class="panel">
  <div class="panel__head">
    <h2>My Donation Requests</h2>
    <a class="btn btn--primary" href="<?= url('/my/donations/create') ?>">Create Donation Request</a>
  </div>
  <div class="panel__body panel__body--flush">
    <table class="data-table">
      <thead>
        <tr><th>Status</th><th>Request Details</th><th>Type</th><th>Required</th><th>Collection Period</th><th>Progress</th></tr>
      </thead>
      <tbody>
        <?php foreach ($donations as $d): ?>
          <tr>
            <td><span class="pill pill--<?= $d['tone'] === 'success' ? 'active' : 'pending' ?>"><?= e($d['status']) ?></span></td>
            <td>
              <div class="cell-title"><?= e($d['title']) ?></div>
              <div class="cell-sub"><?= e($d['note']) ?></div>
            </td>
            <td><span class="badge badge--neutral"><?= e($d['type']) ?></span></td>
            <td><?= e($d['need']) ?></td>
            <td><?= e($d['period']) ?></td>
            <td>
              <div class="cell-title"><?= e($d['progress']) ?></div>
              <div class="cell-sub"><?= e($d['sub']) ?></div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <a class="panel__foot" href="<?= url('/my/donations') ?>">View all requests</a>
</section>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  Sample content for the interim. Disaster reports save once the disaster module lands;
  donation requests are Member 2's weeks 4–5 work.
</p>
