<?php
/**
 * Blood bank dashboard.
 *
 * Blood-Org.svg starts at the campaign list, so like the organization dashboard
 * this follows the GN frame's shape. Every link here is a route a blood bank is
 * actually allowed to open - the week 2 sign-off found this role staring at a
 * sidebar of 403s, and that must not come back.
 *
 * Every number below is counted from `blood_campaigns`, passed in by
 * HomeController::dashboardData(). It used to be a hard-coded list of three
 * camps, which disagreed with /my/blood - that page reads the table, so it said
 * "create your first campaign" while this one claimed three were posted.
 *
 * The Critical Requests panel is gone with it: those rows were sample data with
 * no model behind them, and /blood/critical/* is closed to this role anyway.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Blood\BloodCampaign;

$published = (int) ($counts[EVENT_PUBLISHED] ?? 0);
$pending   = (int) ($counts[MOD_PENDING] ?? 0);
$donors    = array_sum(array_map(static fn($c) => (int) $c['registered'], $camps));
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Welcome back, <?= e($user['full_name'] ?? '') ?>!</h1>
    <p>Campaigns and donors at a glance.</p>
  </div>
  <div class="page-head__actions">
    <a class="btn btn--blood" href="<?= url('/my/blood/create') ?>">New Campaign</a>
  </div>
</div>

<div class="stat-grid stat-grid--4">
  <a class="stat stat--green" href="<?= url('/my/blood') ?>">
    <div class="stat__label">Published Campaigns</div>
    <div class="stat__value"><?= e($published) ?></div>
    <div class="stat__hint">Visible to donors</div>
  </a>
  <a class="stat stat--amber" href="<?= url('/my/blood') ?>">
    <div class="stat__label">Awaiting Approval</div>
    <div class="stat__value"><?= e($pending) ?></div>
    <div class="stat__hint">With an administrator</div>
  </a>
  <a class="stat stat--blue" href="<?= url('/my/blood') ?>">
    <div class="stat__label">Registered Donors</div>
    <div class="stat__value"><?= e($donors) ?></div>
    <div class="stat__hint">Across all camps</div>
  </a>
  <a class="stat stat--red" href="<?= url('/my/blood') ?>">
    <div class="stat__label">Campaigns Organised</div>
    <div class="stat__value"><?= e($stats['organised'] ?? 0) ?></div>
    <div class="stat__hint">All time</div>
  </a>
</div>

<section class="panel" style="margin-top:var(--s-5)">
  <div class="panel__head">
    <h2>My Blood Campaigns</h2>
    <a class="btn-mini" href="<?= url('/my/blood') ?>">Manage</a>
  </div>

  <?php if (!$camps): ?>
    <div class="panel__body">
      <div class="empty-state">
        <p>You have not posted a blood donation campaign yet.</p>
        <a class="btn btn--blood" href="<?= url('/my/blood/create') ?>">Create your first campaign</a>
      </div>
    </div>
  <?php else: ?>
    <div class="panel__body panel__body--flush">
      <table class="data-table">
        <thead><tr><th>Campaign</th><th>Status</th><th>Date</th><th>Donors</th></tr></thead>
        <tbody>
          <?php foreach ($camps as $c): ?>
            <tr>
              <td class="cell-title">
                <a href="<?= url('/my/blood/' . $c['id']) ?>"><?= e($c['title']) ?></a>
              </td>
              <td>
                <span class="pill pill--<?= e(BloodCampaign::TONES[$c['status']] ?? 'pending') ?>">
                  <?= e(BloodCampaign::STATUSES[$c['status']] ?? $c['status']) ?>
                </span>
              </td>
              <td><?= e(format_date($c['campaign_date'])) ?></td>
              <td>
                <?php if ($c['status'] === MOD_PENDING): ?>
                  Awaiting approval
                <?php else: ?>
                  <?= e($c['registered']) ?> / <?= e($c['target_donors']) ?> donors
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
