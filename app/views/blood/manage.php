<?php
/**
 * "Your Posted Blood Donation Campaigns" — organiser's own list, from
 * UI/Blood. Figma mixes campaigns and critical requests in one table;
 * critical requests are CriticalRequestController's own entity, not this
 * CRUD, so this view is scoped to blood_campaigns rows only — flagged to the
 * team lead to confirm whether the two should be merged here later.
 *
 * The Figma's Type and Date filter dropdowns are left as visual only for now;
 * only the Status filter is wired to a real query.
 */
require_once APP_PATH . '/views/partials/icons.php';

$statuses = \Models\Blood\BloodCampaign::STATUSES;

$badgeClass = fn (string $status) => match ($status) {
    'published', 'ongoing' => 'badge--success',
    'pending'               => 'badge--pending',
    'rejected', 'cancelled' => 'badge--danger',
    default                 => 'badge--neutral',
};
?>

<div class="page-wrap">

  <div class="page-header">
    <div>
      <h1>Your Posted Blood Donation Campaigns</h1>
      <p class="page-header__sub">Manage and monitor all blood donation campaigns you have posted.</p>
    </div>
    <div class="page-header__spacer"></div>
    <a class="btn btn--blood" href="<?= url('/my/blood/create') ?>">Create New Campaign</a>
  </div>

  <div class="field" style="max-width:220px;margin-bottom:var(--s-4)">
    <label class="field__label" for="status">Status</label>
    <select class="field__select" id="status" name="status" onchange="location.href=this.value">
      <?php
        $current = (string) (\Core\Request::get('status') ?? '');
        $opt = fn (string $val, string $label) =>
            '<option value="' . e(url('/my/blood') . ($val ? '?status=' . $val : '')) . '" '
            . ($current === $val ? 'selected' : '') . '>' . e($label) . '</option>';
      ?>
      <?= $opt('', 'All Status') ?>
      <?php foreach ($statuses as $value => $label): ?>
        <?= $opt($value, $label) ?>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if (!$rows): ?>
    <div class="card empty-state">
      <strong>You have not posted any blood campaigns yet</strong>
      <p>Create a campaign to start attracting donors.</p>
      <p><a class="btn btn--blood" href="<?= url('/my/blood/create') ?>">Create New Campaign</a></p>
    </div>
  <?php else: ?>
    <div class="card">
      <table class="camp-table">
        <thead>
          <tr>
            <th>Campaign Details</th><th>Status</th><th>Period</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td>
              <div class="camp-cell">
                <div class="camp-cell__body">
                  <h3><?= e($row['title']) ?></h3>
                  <span class="camp-cell__where">
                    <?= icon('map-pin') ?><?= e($row['venue']) ?> · <?= e($row['city']) ?>
                  </span>
                  <p class="camp-cell__blurb"><?= e(str_limit($row['description'], 90)) ?></p>
                </div>
              </div>
            </td>
            <td>
              <span class="badge <?= $badgeClass($row['status']) ?>">
                <?= e($statuses[$row['status']] ?? $row['status']) ?>
              </span>
            </td>
            <td>
              <div class="period-list">
                <div><dt>Date</dt><dd><?= e(format_date($row['campaign_date'])) ?></dd></div>
                <div><dt>Time</dt><dd><?= e($row['start_time']) ?> – <?= e($row['end_time']) ?></dd></div>
              </div>
            </td>
            <td>
              <a class="btn btn--outline btn--sm" href="<?= url('/my/blood/' . $row['id']) ?>">View Details</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>