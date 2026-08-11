<?php
/**
 * Organization dashboard.
 *
 * No frame exists for this screen — Cleanup-Org.svg and Donation-Org.svg both
 * start at their module list — so it follows the GN dashboard's shape, which is
 * the house pattern for a role landing page: counters, then one panel per thing
 * this role owns, each linking into the module that will own it for real.
 */
require_once APP_PATH . '/views/partials/icons.php';

$campaigns = [
    ['Beach Cleanup - Galle',   'Published', 'success', '27 Jun 2026', '42 / 50 volunteers'],
    ['Kelani River Cleanup',    'Published', 'success', '04 Jul 2026', '18 / 40 volunteers'],
    ['Dengue Prevention Drive', 'Draft',     'pending', '12 Jul 2026', 'Awaiting approval'],
];

$requests = [
    ['Flood Relief - Rathnapura', 'Open',      'success', 'Items', '135 / 300 received'],
    ['Shelter Materials',         'Pending',   'pending', 'Items', 'Awaiting approval'],
    ['Medical Fund - Kegalle',    'Fulfilled', 'neutral', 'Funds', 'LKR 240,000 raised'],
];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Welcome back, <?= e($user['full_name'] ?? '') ?>!</h1>
    <p>Here's how your organization is doing this month.</p>
  </div>
  <div class="page-head__actions">
    <a class="btn btn--outline" href="<?= url('/org/cleanup/create') ?>">New Campaign</a>
    <a class="btn btn--primary" href="<?= url('/my/donations/create') ?>">New Donation Request</a>
  </div>
</div>

<div class="stat-grid stat-grid--4">
  <a class="stat stat--green" href="<?= url('/org/cleanup') ?>">
    <div class="stat__label">Active Campaigns</div>
    <div class="stat__value">3</div>
    <div class="stat__hint">1 awaiting approval</div>
  </a>
  <a class="stat stat--blue" href="<?= url('/org/cleanup') ?>">
    <div class="stat__label">Volunteers Signed Up</div>
    <div class="stat__value">60</div>
    <div class="stat__hint">Across all campaigns</div>
  </a>
  <a class="stat stat--amber" href="<?= url('/my/donations') ?>">
    <div class="stat__label">Donation Requests</div>
    <div class="stat__value">3</div>
    <div class="stat__hint">1 pending approval</div>
  </a>
  <a class="stat" href="<?= url('/my/blood') ?>">
    <div class="stat__label">Blood Camps</div>
    <div class="stat__value">1</div>
    <div class="stat__hint">Next on 28 Jun</div>
  </a>
</div>

<div class="split">
  <div class="stack">
    <section class="panel">
      <div class="panel__head">
        <h2>My Cleanup Campaigns</h2>
        <a class="btn-mini" href="<?= url('/org/cleanup') ?>">Manage</a>
      </div>
      <div class="panel__body panel__body--flush">
        <table class="data-table">
          <thead><tr><th>Campaign</th><th>Status</th><th>Date</th><th>Volunteers</th></tr></thead>
          <tbody>
            <?php foreach ($campaigns as [$title, $status, $tone, $date, $vols]): ?>
              <tr>
                <td class="cell-title"><?= e($title) ?></td>
                <td><span class="pill pill--<?= $tone === 'success' ? 'active' : 'pending' ?>"><?= e($status) ?></span></td>
                <td><?= e($date) ?></td>
                <td><?= e($vols) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head">
        <h2>My Donation Requests</h2>
        <a class="btn-mini" href="<?= url('/my/donations') ?>">Manage</a>
      </div>
      <div class="panel__body panel__body--flush">
        <table class="data-table">
          <thead><tr><th>Request</th><th>Status</th><th>Type</th><th>Progress</th></tr></thead>
          <tbody>
            <?php foreach ($requests as [$title, $status, $tone, $type, $progress]): ?>
              <tr>
                <td class="cell-title"><?= e($title) ?></td>
                <td><span class="pill pill--<?= $tone === 'success' ? 'active' : ($tone === 'pending' ? 'pending' : 'neutral') ?>"><?= e($status) ?></span></td>
                <td><span class="badge badge--neutral"><?= e($type) ?></span></td>
                <td><?= e($progress) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><h2>Quick Actions</h2></div>
      <div class="panel__body stack">
        <a class="btn btn--outline btn--block" href="<?= url('/org/cleanup/create') ?>">Create a cleanup campaign</a>
        <a class="btn btn--outline btn--block" href="<?= url('/my/donations/create') ?>">Raise a donation request</a>
        <a class="btn btn--outline btn--block" href="<?= url('/my/blood/create') ?>">Schedule a blood camp</a>
        <a class="btn btn--outline btn--block" href="<?= url('/dengue') ?>">Raise a dengue alert</a>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><h2>Account</h2></div>
      <div class="panel__body">
        <dl class="kv kv--single">
          <div><dt>Organization</dt><dd><?= e($user['full_name'] ?? '') ?></dd></div>
          <div><dt>Verification</dt><dd><span class="badge badge--success">Verified</span></dd></div>
          <div><dt>Contact email</dt><dd><?= e($user['email'] ?? '') ?></dd></div>
        </dl>
        <a class="btn btn--outline btn--block" style="margin-top:var(--s-4)" href="<?= url('/profile') ?>">Edit profile</a>
      </div>
    </section>
  </div>
</div>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  Campaign and request figures are interim samples. They become real as Members 1, 2 and 3
  finish their modules in weeks 4–5.
</p>
