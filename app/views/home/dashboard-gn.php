<?php
/**
 * "GN Dashboard" from GN.svg - counters first, then one panel per thing this
 * officer owns.
 *
 * Rewritten 2026-08-27. The counters read "2 active disasters" and "3 requests"
 * on an account with none, and both tables drew arrays from Services\SampleData.
 * Everything here is a query against the signed-in officer's own rows now.
 *
 * Two panels are new the same day: a Grama Niladhari hosts cleanup and blood
 * campaigns as of this change, so the dashboard has to show them the way the
 * organisation dashboard does.
 */
require_once APP_PATH . '/views/partials/icons.php';

$reportTypes      = Controllers\GnDisasterController::TYPES;
$reportSeverities = Controllers\GnDisasterController::SEVERITIES;
$campaignStatuses = Models\Cleanup\CleanupEvent::STATUSES;
$requestStatuses  = Models\Donation\DonationRequest::STATUSES;

$tone = static fn(string $s): string => match ($s) {
    EVENT_PUBLISHED, EVENT_ONGOING, EVENT_COMPLETED,
    REQUEST_OPEN, REQUEST_FULFILLED, MOD_APPROVED                  => 'active',
    EVENT_CANCELLED, REQUEST_CANCELLED, REQUEST_EXPIRED,
    MOD_REJECTED                                                   => 'rejected',
    default                                                        => 'pending',
};
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Welcome back, <?= e($user['full_name'] ?? '') ?>!</h1>
    <p>Here's what's happening in your area.</p>
  </div>
</div>

<div class="stat-grid stat-grid--4">
  <a class="stat stat--red" href="<?= url('/gn/disasters') ?>">
    <div class="stat__label">Disaster Reports</div>
    <div class="stat__value"><?= (int) $counts['reports'] ?></div>
    <div class="stat__hint"><?= (int) $counts['reportsPending'] ?> awaiting verification</div>
  </a>
  <a class="stat stat--blue" href="<?= url('/my/donations') ?>">
    <div class="stat__label">My Requests</div>
    <div class="stat__value"><?= (int) $counts['requests'] ?></div>
    <div class="stat__hint"><?= (int) $counts['requestsPending'] ?> pending approval</div>
  </a>
  <a class="stat stat--green" href="<?= url('/org/cleanup') ?>">
    <div class="stat__label">My Cleanup Campaigns</div>
    <div class="stat__value"><?= (int) $counts['campaigns'] ?></div>
    <div class="stat__hint"><?= (int) $counts['campaignsPending'] ?> awaiting approval</div>
  </a>
  <a class="stat" href="<?= url('/my/blood') ?>">
    <div class="stat__label">My Blood Campaigns</div>
    <div class="stat__value"><?= (int) $counts['camps'] ?></div>
    <div class="stat__hint"><?= (int) $counts['campsPending'] ?> pending approval</div>
  </a>
</div>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__head">
    <h2>My Disaster Reports</h2>
    <a class="btn btn--primary" href="<?= url('/gn/disasters/create') ?>">Report a Disaster</a>
  </div>
  <div class="panel__body<?= $reports ? ' panel__body--flush' : '' ?>">
    <?php if (!$reports): ?>
      <div class="empty-state">You have not filed a disaster report yet.</div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr><th>Status</th><th>Disaster Details</th><th>District</th><th>Severity</th><th>Reported On</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $r): ?>
            <tr>
              <td><span class="pill pill--<?= e($tone($r['status'])) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
              <td>
                <div class="cell-title"><?= e($r['title']) ?></div>
                <div class="cell-sub"><?= e($reportTypes[$r['disaster_type']] ?? $r['disaster_type']) ?></div>
              </td>
              <td><?= e($r['district']) ?></td>
              <td>
                <span class="pill pill--<?= in_array($r['severity'], ['high', 'critical'], true) ? 'rejected' : 'neutral' ?>">
                  <?= e($reportSeverities[$r['severity']] ?? ucfirst($r['severity'])) ?>
                </span>
              </td>
              <td><?= e(format_date($r['created_at'])) ?></td>
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
  <a class="panel__foot" href="<?= url('/gn/disasters') ?>">View all reports</a>
</section>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__head">
    <h2>My Cleanup Campaigns</h2>
    <a class="btn btn--primary" href="<?= url('/org/cleanup/create') ?>">Create Campaign</a>
  </div>
  <div class="panel__body<?= $campaigns ? ' panel__body--flush' : '' ?>">
    <?php if (!$campaigns): ?>
      <div class="empty-state">You have not posted a cleanup campaign yet.</div>
    <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Campaign</th><th>Status</th><th>Date</th><th>Volunteers</th></tr></thead>
        <tbody>
          <?php foreach ($campaigns as $c): ?>
            <tr>
              <td class="cell-title">
                <a href="<?= url('/org/cleanup/' . $c['id']) ?>"><?= e($c['title']) ?></a>
              </td>
              <td>
                <span class="pill pill--<?= e($tone($c['status'])) ?>">
                  <?= e($campaignStatuses[$c['status']] ?? ucfirst($c['status'])) ?>
                </span>
              </td>
              <td><?= e(format_date($c['event_date'])) ?></td>
              <td><?= (int) $c['volunteers'] ?> / <?= (int) $c['volunteer_limit'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <a class="panel__foot" href="<?= url('/org/cleanup') ?>">View all campaigns</a>
</section>

<section class="panel">
  <div class="panel__head">
    <h2>My Donation Requests</h2>
    <a class="btn btn--primary" href="<?= url('/my/donations/create') ?>">Create Donation Request</a>
  </div>
  <div class="panel__body<?= $requests ? ' panel__body--flush' : '' ?>">
    <?php if (!$requests): ?>
      <div class="empty-state">You have not created a donation request yet.</div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr><th>Status</th><th>Request Details</th><th>Type</th><th>Progress</th></tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $d): ?>
            <tr>
              <td>
                <span class="pill pill--<?= e($tone($d['status'])) ?>">
                  <?= e($requestStatuses[$d['status']] ?? ucfirst($d['status'])) ?>
                </span>
              </td>
              <td class="cell-title">
                <a href="<?= url('/my/donations/' . $d['id']) ?>"><?= e($d['title']) ?></a>
              </td>
              <td><span class="badge badge--neutral"><?= e(ucfirst($d['request_type'])) ?></span></td>
              <td>
                <?php if ($d['request_type'] === 'funds' && (float) $d['target_amount'] > 0): ?>
                  <?= e(format_money((float) $d['collected_amount'])) ?>
                  of <?= e(format_money((float) $d['target_amount'])) ?>
                <?php else: ?>
                  <span class="muted">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <a class="panel__foot" href="<?= url('/my/donations') ?>">View all requests</a>
</section>
