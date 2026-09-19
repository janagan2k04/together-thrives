<?php
/**
 * Administrator review of blood donation campaigns.
 *
 * No frame in admin.svg covers this, so it follows its sibling screens -
 * views/admin/verification/index.php and views/reuse/admin-letters.php - down to
 * the filter control, because an administrator moving between the three should
 * not have to learn a third table.
 */
require_once APP_PATH . '/views/partials/icons.php';

$tabs = [
    MOD_PENDING     => 'Awaiting review',
    EVENT_PUBLISHED => 'Approved',
    MOD_REJECTED    => 'Rejected',
];

$pillFor = [
    MOD_PENDING     => 'pill--pending',
    EVENT_PUBLISHED => 'pill--active',
    MOD_REJECTED    => 'pill--rejected',
];

$clock = static fn(?string $t): string => $t ? date('g:i A', strtotime($t)) : '-';

/**
 * "12 Sep 2026" for a one-day camp, "12 Sep 2026 - 14 Sep 2026" for a range.
 * `campaign_end_date` arrived with migration 2026_08_18, so a row written
 * before it exists reads as a single day.
 */
$dates = static function (array $r): string {
    $end = $r['campaign_end_date'] ?? null;
    return (!$end || $end === $r['campaign_date'])
        ? format_date($r['campaign_date'])
        : format_date($r['campaign_date']) . ' - ' . format_date($end);
};
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Blood Campaign Review</h1>
    <p>
      Camps posted by organisations and blood banks. Donors are only shown a camp
      once it is approved here, so check the venue, date and contact number
      before letting one through.
    </p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/blood/campaigns') ?>">
  <select name="status" onchange="this.form.submit()">
    <?php foreach ($tabs as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>>
        <?= e($label) ?><?= $value === MOD_PENDING && $pending > 0 ? ' (' . (int) $pending . ')' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<div class="data-card">
  <?php if (!$campaigns): ?>
    <div class="admin-empty">
      <span class="admin-empty__icon"><?= icon('check-circle') ?></span>
      <?php if ($status === MOD_PENDING): ?>
        <strong>No campaigns waiting for review</strong>
        <p>New ones appear here when an organisation or blood bank posts a camp.</p>
      <?php else: ?>
        <strong>Nothing in this state</strong>
        <p>No campaign has been <?= e(strtolower($tabs[$status])) ?> yet.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Campaign</th>
          <th>Organiser</th>
          <th>When</th>
          <th>Contact</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($campaigns as $c): ?>
          <tr>
            <td>
              <div class="cell-title"><?= e($c['title']) ?></div>
              <div class="cell-sub"><?= e($c['venue']) ?><?= $c['address'] ? ', ' . e($c['address']) : '' ?>, <?= e($c['city']) ?></div>
              <div class="cell-sub"><?= e(str_limit($c['description'], 90)) ?></div>
            </td>
            <td>
              <div class="cell-title"><?= e($c['organiser']) ?></div>
              <div class="cell-sub"><?= e($c['organiser_email']) ?></div>
            </td>
            <td>
              <div class="cell-title"><?= e($dates($c)) ?></div>
              <div class="cell-sub">
                <?= e($clock($c['start_time'])) ?> - <?= e($clock($c['end_time'])) ?>
              </div>
              <div class="cell-sub"><?= e($c['target_donors']) ?> donors targeted</div>
            </td>
            <td><?= e($c['contact_phone'] ?: '-') ?></td>
            <td>
              <?php if ($c['status'] === MOD_PENDING): ?>
                <div class="cell-actions">
                  <form method="post" action="<?= url('/admin/blood/campaigns/' . $c['id'] . '/approve') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--approve"
                            data-confirm="Approve &quot;<?= e($c['title']) ?>&quot;? Donors will see it on the blood donation hub.">
                      Approve
                    </button>
                  </form>
                  <form method="post" action="<?= url('/admin/blood/campaigns/' . $c['id'] . '/reject') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--danger"
                            data-confirm="Reject this campaign? The organiser can edit it and resubmit.">
                      Reject
                    </button>
                  </form>
                </div>
              <?php else: ?>
                <span class="pill <?= e($pillFor[$c['status']] ?? 'pill--neutral') ?>">
                  <?= e($tabs[$c['status']] ?? $statuses[$c['status']]) ?>
                </span>
                <?php if ($c['reviewed_at']): ?>
                  <div class="cell-sub">Reviewed <?= e(format_date($c['reviewed_at'])) ?></div>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
