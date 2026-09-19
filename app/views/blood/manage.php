<?php
/**
 * "Blood Campaigns (Org and blood bank)" - frame 1 of Blood-Org.svg.
 *
 * Weeks 4-5: real rows from `blood_campaigns`, owned by the signed-in organiser.
 *
 * The row was sized up on 2026-09-18: every cell in it was --fs-xs or --fs-sm,
 * footnote size for the screen an organiser runs their camps from, and the
 * columns were re-measured per row so nothing lined up down the table. Widths
 * are fixed in blood.css now and the labels sit in columns of their own.
 *
 * The frame draws one table for both things an organiser posts, camps and
 * urgent requests. Only camps are rows here - an urgent need is a
 * `critical_blood_requests` row, a different table with its own screens - so the
 * Type column is gone rather than showing "Blood Donation" on every line.
 */
require_once APP_PATH . '/views/partials/icons.php';

/** 'HH:MM:SS' from a TIME column, as the design writes it. */
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

<div class="blood-head">
  <div class="blood-head__text">
    <h1>Your Posted Blood Donation Campaigns</h1>
    <p>Manage and monitor all blood donation campaigns you have posted.</p>
  </div>
  <a class="btn btn--blood" href="<?= url('/my/blood/create') ?>">Create New Campaign</a>
</div>

<form class="filters" method="get" action="<?= url('/my/blood') ?>">
  <select name="status" onchange="this.form.submit()">
    <option value="">All Status</option>
    <?php foreach ($statuses as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>>
        <?= e($label) ?> (<?= e($counts[$key]) ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn btn--outline" type="submit">Filter</button></noscript>
</form>

<?php if (!$rows): ?>
  <div class="sec empty-state">
    <p>
      <?= $status !== ''
            ? 'No campaigns with that status yet.'
            : 'You have not posted a blood donation campaign yet.' ?>
    </p>
    <a class="btn btn--blood" style="margin-top:var(--s-4)" href="<?= url('/my/blood/create') ?>">
      Create your first campaign
    </a>
  </div>
<?php else: ?>
  <div class="sec">
    <table class="camp-table">
      <colgroup>
        <col class="camp-col--details"><col class="camp-col--status">
        <col class="camp-col--schedule"><col class="camp-col--reach">
        <col class="camp-col--actions">
      </colgroup>
      <thead>
        <tr>
          <th>Campaign Details</th><th>Status</th>
          <th>Schedule</th><th>Reach &amp; Engagement</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <div class="camp-cell">
                <div class="camp-cell__body">
                  <h3>
                    <?= e($r['title']) ?>
                    <span class="countdown countdown--blue">BLOOD DONATION</span>
                  </h3>
                  <span class="camp-cell__where">
                    <?= icon('map-pin') ?><?= e($r['venue']) ?><?= $r['address'] ? ', ' . e($r['address']) : '' ?>, <?= e($r['city']) ?>
                  </span>
                  <p class="camp-cell__blurb"><?= e(str_limit($r['description'], 110)) ?></p>
                </div>
              </div>
            </td>

            <td><span class="pill pill--<?= e($tones[$r['status']]) ?>"><?= e($statuses[$r['status']]) ?></span></td>

            <td>
              <dl class="period-list">
                <div><dt>Date</dt><dd><?= e($dates($r)) ?></dd></div>
                <div><dt>Time</dt><dd><?= e($clock($r['start_time'])) ?> - <?= e($clock($r['end_time'])) ?></dd></div>
                <div><dt>Target</dt><dd><?= e($r['target_donors']) ?> donors</dd></div>
              </dl>
            </td>

            <td>
              <?php /* No icon before each figure - 2026-09-13, same call as the
                        campaign detail's count tiles. The label says what it is.
                        Figure then label, each in its own grid column since
                        2026-09-18 - the <br> that used to separate them left the
                        three numbers at three different indents. */ ?>
              <div class="reach">
                <div><strong><?= e($r['registered']) ?></strong><small>Registered</small></div>
                <div><strong><?= e($r['eligible']) ?></strong><small>Eligible</small></div>
                <div><strong><?= e($r['ineligible']) ?></strong><small>Not eligible</small></div>
              </div>
            </td>

            <td>
              <a class="btn btn--outline" href="<?= url('/my/blood/' . $r['id']) ?>">View Details</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
