<?php
/**
 * Frame 3 of Blood-Org.svg - one campaign as its organiser sees it: reach
 * counters, the campaign's own details, and the donors who registered.
 *
 * Every figure is counted. The campaign is a `blood_campaigns` row, the reach
 * counters and the donor table both come from `screening_responses` - a donor
 * registers by completing the camp's screening form - and the post-campaign
 * impact from `blood_donation_records`, which the organiser writes from the
 * donor table on this screen.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Blood\ScreeningResponse;

$clock = static fn(?string $t): string => $t ? date('g:i A', strtotime($t)) : '-';

/** Two letters for the avatar, from whatever the name gives us. */
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};

/** Donor ids already recorded as having given blood at this camp. */
$donatedIds = array_map('intval', array_column($donated, 'donor_id'));

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

$cancelled = $row['deleted_at'] !== null;
$published = $row['status'] === EVENT_PUBLISHED || $row['status'] === EVENT_ONGOING;
?>

<a class="blood-back" href="<?= url('/my/blood') ?>"><?= icon('arrow-left') ?>Back to Blood Campaigns</a>

<div class="blood-head">
  <div class="blood-head__text">
    <h1>
      <?= e($row['title']) ?>
      <span class="countdown countdown--blue">BLOOD DONATION</span>
      <span class="pill pill--<?= e($tones[$row['status']]) ?>"><?= e($statuses[$row['status']]) ?></span>
    </h1>
    <p>
      <?= e($row['venue']) ?><?= $row['address'] ? ', ' . e($row['address']) : '' ?>, <?= e($row['city']) ?>
      · <?= e($dates($row)) ?>
      · <?= e($clock($row['start_time'])) ?> - <?= e($clock($row['end_time'])) ?>
    </p>
  </div>
  <div class="page-head__actions">
    <?php if (!$cancelled): ?>
      <a class="btn btn--outline" href="<?= url('/my/blood/' . $row['id'] . '/edit') ?>">Edit</a>
      <form method="post" action="<?= url('/my/blood/' . $row['id']) ?>" class="inline">
        <?= csrf_field() ?><?= method_field('DELETE') ?>
        <button class="btn btn--danger" data-confirm="Cancel this campaign?">Cancel Campaign</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($cancelled): ?>
  <div class="alert alert--info" style="margin-bottom:var(--s-5)">
    This campaign was cancelled. It stays here for your records and is no longer shown to donors.
  </div>
<?php endif; ?>

<div class="count-grid">
  <div class="count">
    <span><strong><?= e($row['registered']) ?></strong><span>Registered</span></span>
  </div>
  <div class="count">
    <span><strong><?= e($row['eligible']) ?></strong><span>Eligible to donate</span></span>
  </div>
  <div class="count">
    <span><strong><?= e($row['ineligible']) ?></strong><span>Not eligible</span></span>
  </div>
  <div class="count">
    <span><strong><?= e($row['target_donors']) ?></strong><span>Donors targeted</span></span>
  </div>
</div>

<div class="split">
  <div class="stack">

    <section class="sec">
      <div class="sec__head">
        <div><strong>Campaign Information</strong></div>
      </div>
      <div class="sec__body">
        <p><?= e($row['description']) ?></p>

        <div class="readout-grid readout-grid--4" style="margin-top:var(--s-4)">
          <div class="readout">
            <span class="readout__label">Donors required</span>
            <span class="readout__value"><?= e($row['target_donors']) ?> donors</span>
          </div>
          <div class="readout">
            <span class="readout__label">Contact</span>
            <span class="readout__value"><?= e($row['contact_phone'] ?: '-') ?></span>
          </div>
          <div class="readout">
            <span class="readout__label">Group chat</span>
            <span class="readout__value"><?= $row['auto_group_chat'] ? 'Automatic' : 'Off' ?></span>
          </div>
          <div class="readout">
            <span class="readout__label">Screening form</span>
            <span class="readout__value">Standard · 14 questions</span>
          </div>
        </div>
      </div>
    </section>

    <section class="sec">
      <div class="sec__head">
        <div class="sec__spacer">
          <strong>Registered Donors</strong>
          <small>Everyone who completed this camp's screening form</small>
        </div>
      </div>
      <?php if (!$donors): ?>
        <div class="sec__body">
          <div class="empty-state">
            <p>No donor has registered yet. A donor joins by completing the camp's screening form,
               and appears here with their result.</p>
          </div>
        </div>
      <?php else: ?>
      <div class="sec__body sec__body--flush">
        <table class="donor-table">
          <thead>
            <tr><th>Donor</th><th>Blood Type</th><th>Screening Result</th><th>Contact</th><th>Registered On</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($donors as $d): ?>
              <tr>
                <td>
                  <div class="donor-cell">
                    <span class="avatar"><?= e($initials((string) $d['full_name'])) ?></span>
                    <div><strong><?= e($d['full_name']) ?></strong><small><?= e($d['city'] ?? '') ?></small></div>
                  </div>
                </td>
                <td><span class="group-tag"><?= e($d['blood_group'] ?: '-') ?></span></td>
                <td>
                  <span class="pill pill--<?= $d['result'] === 'eligible' ? 'active' : 'rejected' ?>">
                    <?= e(ScreeningResponse::RESULTS[$d['result']]) ?>
                  </span>
                </td>
                <td>
                  <?php if ($d['phone']): ?>
                    <?= e($d['phone']) ?>
                  <?php else: ?>
                    <span class="donor-hidden">Hidden</span>
                  <?php endif; ?>
                </td>
                <td><?= e(format_date($d['created_at'])) ?></td>
                <td>
                  <?php if ($d['result'] !== 'eligible'): ?>
                    <span class="muted">-</span>
                  <?php elseif (in_array((int) $d['donor_id'], $donatedIds, true)): ?>
                    <span class="pill pill--active">Donated</span>
                  <?php else: ?>
                    <form method="post" class="inline"
                          action="<?= url('/my/blood/' . $row['id'] . '/donations') ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="donor_id" value="<?= e($d['donor_id']) ?>">
                      <button class="btn-mini btn-mini--view" type="submit">Record donation</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </section>
  </div>

  <div class="stack">
    <section class="sec">
      <div class="sec__body" style="padding-top:var(--s-5)">
        <div class="donor-cell">
          <div>
            <strong><?= $published ? 'Verified &amp; Published' : e($statuses[$row['status']]) ?></strong>
            <small>
              <?php if ($published && $row['reviewed_at']): ?>
                Approved on <?= e(format_date($row['reviewed_at'])) ?>
              <?php elseif ($published): ?>
                Visible to donors on the blood donation hub.
              <?php elseif ($row['status'] === MOD_REJECTED): ?>
                An administrator did not approve this campaign.
              <?php elseif ($cancelled): ?>
                Cancelled by you.
              <?php else: ?>
                An administrator fact-checks this before donors are notified.
              <?php endif; ?>
            </small>
          </div>
        </div>
      </div>
    </section>

    <section class="sec">
      <div class="sec__head">
        <div><strong>Post-Campaign Impact</strong><small>Publish outcomes once the campaign has ended.</small></div>
      </div>
      <div class="sec__body">
        <?php
        /*
         * Counted from `blood_donation_records`, so these are 0 until the
         * organiser records a donation on the table above. "Lives supported" is
         * three per unit - the figure a whole-blood donation splits into - which
         * is a stated multiple of something counted, not an invented number.
         */
        $units = array_sum(array_map(fn($d) => (float) $d['units'], $donated));
        ?>
        <div class="impact-boxes">
          <div>
            <strong><?= e(rtrim(rtrim(number_format($units, 2), '0'), '.')) ?></strong>
            <small>Units collected</small>
          </div>
          <div><strong><?= e(count($donated)) ?></strong><small>Donors attended</small></div>
          <div><strong><?= e((int) round($units * 3)) ?></strong><small>Lives supported</small></div>
        </div>

        <?php if (!$donated): ?>
          <div class="alert alert--info" style="margin-top:var(--s-4)">
            Record each donation against its donor on the Registered Donors table, and these
            figures follow.
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>

