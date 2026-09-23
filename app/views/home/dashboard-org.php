<?php
/**
 * Organization dashboard.
 *
 * No frame exists for this screen - Cleanup-Org.svg and Donation-Org.svg both
 * start at their module list - so it follows the GN dashboard's shape: counters,
 * then one panel per thing this role owns, each linking into its module.
 *
 * Rewritten 2026-08-27. The counters and both tables were hard-coded arrays
 * naming three cleanup campaigns and three donation requests that were not in
 * the database; they read `organizations` and `donation_requests` for the
 * signed-in account now, so an organization that has posted nothing sees
 * nothing - the same answer /org/cleanup and /my/donations give.
 *
 * Removed the same day, by group decision: the two header buttons (New Campaign
 * and New Donation Request), the Quick Actions panel and the Account panel. All
 * three repeated links the sidebar already carries, and Account repeated the
 * profile page with a Verified badge that was drawn rather than checked.
 */
require_once APP_PATH . '/views/partials/icons.php';

$campaignStatuses = Models\Cleanup\CleanupEvent::STATUSES;
$requestStatuses  = Models\Donation\DonationRequest::STATUSES;

$tone = static fn(string $s): string => match ($s) {
    EVENT_PUBLISHED, EVENT_ONGOING, EVENT_COMPLETED, REQUEST_OPEN, REQUEST_FULFILLED => 'active',
    EVENT_CANCELLED, REQUEST_CANCELLED, REQUEST_EXPIRED, MOD_REJECTED               => 'rejected',
    default                                                                          => 'pending',
};
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Welcome back, <?= e($user['full_name'] ?? '') ?>!</h1>
    <p>Here's how your organization is doing this month.</p>
  </div>
</div>

<div class="stat-grid stat-grid--4">
  <a class="stat stat--green" href="<?= url('/org/cleanup') ?>">
    <div class="stat__label">Active Campaigns</div>
    <div class="stat__value"><?= (int) $counts['campaigns'] ?></div>
    <div class="stat__hint"><?= (int) $counts['campaignsPending'] ?> awaiting approval</div>
  </a>
  <a class="stat stat--blue" href="<?= url('/org/cleanup') ?>">
    <div class="stat__label">Volunteers Signed Up</div>
    <div class="stat__value"><?= (int) $counts['volunteers'] ?></div>
    <div class="stat__hint">Across all campaigns</div>
  </a>
  <a class="stat stat--amber" href="<?= url('/my/donations') ?>">
    <div class="stat__label">Donation Requests</div>
    <div class="stat__value"><?= (int) $counts['requests'] ?></div>
    <div class="stat__hint"><?= (int) $counts['requestsPending'] ?> pending approval</div>
  </a>
  <a class="stat" href="<?= url('/my/blood') ?>">
    <div class="stat__label">Blood Camps</div>
    <div class="stat__value"><?= (int) $counts['camps'] ?></div>
    <div class="stat__hint"><?= (int) $counts['campsPending'] ?> pending approval</div>
  </a>
</div>

<div class="stack">
  <section class="panel">
    <div class="panel__head">
      <h2>My Cleanup Campaigns</h2>
      <a class="btn-mini" href="<?= url('/org/cleanup') ?>">Manage</a>
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
  </section>

  <section class="panel">
    <div class="panel__head">
      <h2>My Donation Requests</h2>
      <a class="btn-mini" href="<?= url('/my/donations') ?>">Manage</a>
    </div>
    <div class="panel__body<?= $requests ? ' panel__body--flush' : '' ?>">
      <?php if (!$requests): ?>
        <div class="empty-state">You have not created a donation request yet.</div>
      <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Request</th><th>Status</th><th>Type</th><th>Progress</th></tr></thead>
          <tbody>
            <?php foreach ($requests as $r): ?>
              <tr>
                <td class="cell-title">
                  <a href="<?= url('/my/donations/' . $r['id']) ?>"><?= e($r['title']) ?></a>
                </td>
                <td>
                  <span class="pill pill--<?= e($tone($r['status'])) ?>">
                    <?= e($requestStatuses[$r['status']] ?? ucfirst($r['status'])) ?>
                  </span>
                </td>
                <td><span class="badge badge--neutral"><?= e(ucfirst($r['request_type'])) ?></span></td>
                <td>
                  <?php if ($r['request_type'] === 'funds' && (float) $r['target_amount'] > 0): ?>
                    <?= e(format_money((float) $r['collected_amount'])) ?>
                    of <?= e(format_money((float) $r['target_amount'])) ?>
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
  </section>
</div>
