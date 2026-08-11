<?php
/**
 * Blood bank dashboard.
 *
 * Blood-Org.svg starts at the campaign list, so like the organization dashboard
 * this follows the GN frame's shape. Every link here is a route a blood bank is
 * actually allowed to open — the week 2 sign-off found this role staring at a
 * sidebar of 403s, and that must not come back.
 */
require_once APP_PATH . '/views/partials/icons.php';

$camps = [
    ['Colombo General Hospital Camp', 'Published', 'active',  '28 Jun 2026', '86 / 120 donors'],
    ['Kandy Town Hall Drive',         'Published', 'active',  '05 Jul 2026', '31 / 80 donors'],
    ['Negombo Community Camp',        'Pending',   'pending', '19 Jul 2026', 'Awaiting approval'],
];

$critical = [
    ['O+',  'City General Hospital', '2 units', 'Today'],
    ['AB-', 'Teaching Hospital Karapitiya', '1 unit', 'Tomorrow'],
];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Welcome back, <?= e($user['full_name'] ?? '') ?>!</h1>
    <p>Campaigns, donors and critical requests at a glance.</p>
  </div>
  <div class="page-head__actions">
    <a class="btn btn--primary" href="<?= url('/my/blood/create') ?>">New Campaign</a>
  </div>
</div>

<div class="stat-grid stat-grid--4">
  <a class="stat stat--green" href="<?= url('/my/blood') ?>">
    <div class="stat__label">Active Campaigns</div>
    <div class="stat__value">2</div>
    <div class="stat__hint">1 awaiting approval</div>
  </a>
  <a class="stat stat--blue" href="<?= url('/my/blood') ?>">
    <div class="stat__label">Registered Donors</div>
    <div class="stat__value">117</div>
    <div class="stat__hint">Across all camps</div>
  </a>
  <a class="stat stat--red" href="<?= url('/blood') ?>">
    <div class="stat__label">Critical Requests</div>
    <div class="stat__value">2</div>
    <div class="stat__hint">Awaiting matched donors</div>
  </a>
  <a class="stat stat--amber" href="<?= url('/my/blood') ?>">
    <div class="stat__label">Units Collected</div>
    <div class="stat__value">64</div>
    <div class="stat__hint">This month</div>
  </a>
</div>

<div class="split">
  <section class="panel">
    <div class="panel__head">
      <h2>My Blood Campaigns</h2>
      <a class="btn-mini" href="<?= url('/my/blood') ?>">Manage</a>
    </div>
    <div class="panel__body panel__body--flush">
      <table class="data-table">
        <thead><tr><th>Campaign</th><th>Status</th><th>Date</th><th>Donors</th></tr></thead>
        <tbody>
          <?php foreach ($camps as [$title, $status, $tone, $date, $donors]): ?>
            <tr>
              <td class="cell-title"><?= e($title) ?></td>
              <td><span class="pill pill--<?= e($tone) ?>"><?= e($status) ?></span></td>
              <td><?= e($date) ?></td>
              <td><?= e($donors) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><h2 style="color:var(--red-dark)">Critical Requests</h2></div>
      <div class="panel__body stack">
        <?php foreach ($critical as [$group, $where, $units, $when]): ?>
          <div class="row-between">
            <div>
              <strong style="font-size:var(--fs-sm)"><?= e($group) ?> — <?= e($units) ?></strong>
              <div class="muted"><?= e($where) ?></div>
              <small class="muted" style="font-size:var(--fs-xs)">Needed <?= e($when) ?></small>
            </div>
            <a class="btn-mini btn-mini--danger" href="<?= url('/blood') ?>">Open</a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><h2>Quick Actions</h2></div>
      <div class="panel__body stack">
        <a class="btn btn--outline btn--block" href="<?= url('/my/blood/create') ?>">Schedule a camp</a>
        <a class="btn btn--outline btn--block" href="<?= url('/blood') ?>">Browse all campaigns</a>
        <a class="btn btn--outline btn--block" href="<?= url('/organizations') ?>">Find partner organizations</a>
        <a class="btn btn--outline btn--block" href="<?= url('/profile') ?>">Edit profile</a>
      </div>
    </section>
  </div>
</div>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  Campaign and donor figures are interim samples. They become real when Member 3's blood
  module lands in weeks 4–5.
</p>
