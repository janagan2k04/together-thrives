<?php
/**
 * "Blood - Camp Details (View Details)" - the screen behind the View Details
 * button. Weeks 4-5: a real `blood_campaigns` row.
 *
 * The Figma's "Camp Type" readout (walk-in vs registered) has no column in the
 * frozen schema, so it is left out rather than invented - the same call made on
 * the blood types and the multi-day date range on the create form.
 */
require_once APP_PATH . '/views/partials/icons.php';

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

$registered = (int) $camp['registered'];
$target     = (int) $camp['target_donors'];
$pct        = $target > 0 ? min(100, (int) round($registered / $target * 100)) : 0;
$remaining  = max(0, $target - $registered);

// Days until the camp, for the chip in the corner.
$days = (int) floor((strtotime($camp['campaign_date']) - strtotime('today')) / 86400);
[$countdown, $tone] = match (true) {
    $days <= 0 => ['Today', 'red'],
    $days === 1 => ['Tomorrow', 'green'],
    $days <= 3 => [$days . ' Days Left', 'amber'],
    default    => [$days . ' Days Left', 'blue'],
};

// Two letters from the organiser's name, for the avatar circle.
$initials = strtoupper(implode('', array_map(
    static fn(string $w): string => $w[0],
    array_slice(preg_split('/\s+/', trim($camp['organiser'])) ?: [], 0, 2)
)));

$organiserSub = $camp['organizer_type'] === ROLE_BLOOD_BANK
    ? 'Blood bank · Verified organiser'
    : 'Organization · Verified organiser';

$eligibility = [
    'Aged between 18 and 60 years',
    'Body weight above 50 kg',
    'No donation in the last 4 months',
    'Free of infection or fever',
    'Not on antibiotics currently',
    'Adequate haemoglobin level',
];

$before = [
    'Eat a proper meal 3 hours before',
    'Drink plenty of water',
    'Bring your NIC for verification',
    'Wear a loose-sleeved shirt',
];
?>
<div class="page-wrap">

  <a class="blood-back" href="<?= url('/blood') ?>"><?= icon('arrow-left') ?>Back to Blood Donation</a>

  <div class="blood-head">
    <div class="blood-head__text">
      <h1><?= e($camp['title']) ?></h1>
      <p>Organised by <?= e($camp['organiser']) ?> · Verified organiser</p>
    </div>
  </div>

  <div class="split">
    <div class="stack">

      <section class="sec">
        <div class="sec__head">
          <div><strong>About This Camp</strong><small>What to expect at this camp</small></div>
        </div>
        <div class="sec__body">
          <div class="chip-row" style="margin-bottom:var(--s-4)">
            <span class="countdown countdown--<?= e($tone) ?>"><?= e($countdown) ?></span>
            <span class="countdown countdown--blue">Verified Organiser</span>
            <span class="countdown countdown--green">Open for Registration</span>
          </div>
          <p><?= e($camp['description']) ?></p>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Schedule &amp; Location</strong><small>When and where to arrive</small></div>
        </div>
        <div class="sec__body">
          <?php /* City and District were two readouts until 2026-08-27, when the
                   lookup collapsed to one row per district and they started
                   printing the same word twice. */ ?>
          <div class="readout-grid readout-grid--3" style="margin-bottom:var(--s-4)">
            <div class="readout"><span class="readout__label">Date</span>
              <span class="readout__value"><?= e($dates($camp)) ?></span></div>
            <div class="readout"><span class="readout__label">Time</span>
              <span class="readout__value"><?= e($clock($camp['start_time'])) ?> - <?= e($clock($camp['end_time'])) ?></span></div>
            <div class="readout"><span class="readout__label">District</span>
              <span class="readout__value"><?= e($camp['district']) ?></span></div>
          </div>
          <div class="readout-grid">
            <div class="readout"><span class="readout__label">Venue</span>
              <span class="readout__value"><?= e($camp['venue']) ?></span></div>
            <div class="readout"><span class="readout__label">Address</span>
              <span class="readout__value"><?= e($camp['address'] ?: '-') ?></span></div>
            <div class="readout"><span class="readout__label">Contact</span>
              <span class="readout__value"><?= e($camp['contact_phone'] ?: '-') ?></span></div>
          </div>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Eligibility Criteria</strong><small>Check before you register</small></div>
        </div>
        <div class="sec__body">
          <ul class="tick-list tick-list--2">
            <?php foreach ($eligibility as $line): ?>
              <li><?= e($line) ?></li>
            <?php endforeach; ?>
          </ul>
          <p class="muted" style="margin-top:var(--s-4)">
            You will complete a short medical screening form when you register.
            Eligibility is evaluated automatically.
          </p>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="sec">
        <div class="sec__head">
          <div><strong>Registration</strong><small>Secure your slot</small></div>
        </div>
        <div class="sec__body">
          <p style="font-size:var(--fs-2xl);font-weight:700;color:var(--ink)">
            <?= e($registered) ?>
            <span style="font-size:var(--fs-sm);font-weight:400;color:var(--muted)">
              of <?= e($target) ?> registered
            </span>
          </p>
          <div class="progress" style="margin-top:10px"><span class="progress__bar" style="width:<?= e($pct) ?>%"></span></div>
          <p class="progress-note"><?= e($remaining) ?> slots remaining</p>

          <?php if (is_logged_in()): ?>
            <form method="post" action="<?= url('/blood/' . $camp['id'] . '/join') ?>" style="margin-top:var(--s-4)">
              <?= csrf_field() ?>
              <button class="btn btn--blood btn--block" type="submit">Join This Camp</button>
            </form>
          <?php else: ?>
            <a class="btn btn--blood btn--block" style="margin-top:var(--s-4)" href="<?= url('/login') ?>">
              Sign in to join
            </a>
          <?php endif; ?>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Organiser</strong></div>
        </div>
        <div class="sec__body">
          <div class="donor-cell">
            <span class="avatar"><?= e($initials) ?></span>
            <div>
              <strong><?= e($camp['organiser']) ?></strong>
              <small><?= e($organiserSub) ?></small>
            </div>
          </div>
          <p class="muted" style="margin-top:var(--s-3)">
            <?= e($organiser['organised']) ?> camp<?= $organiser['organised'] === 1 ? '' : 's' ?> organised
            · <?= e(number_format($organiser['reached'])) ?> donors reached
          </p>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Before You Donate</strong></div>
        </div>
        <div class="sec__body">
          <ul class="tick-list tick-list--amber">
            <?php foreach ($before as $line): ?>
              <li><?= e($line) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>
    </div>
  </div>

  <div class="form-foot">
    <p>You will receive a confirmation email and camp reminders after registering.</p>
    <a class="btn btn--outline" href="<?= url('/blood') ?>">Cancel</a>
    <?php if (is_logged_in()): ?>
      <form method="post" action="<?= url('/blood/' . $camp['id'] . '/join') ?>" class="inline">
        <?= csrf_field() ?>
        <button class="btn btn--blood" type="submit">Join This Camp</button>
      </form>
    <?php else: ?>
      <a class="btn btn--blood" href="<?= url('/login') ?>">Sign in to join</a>
    <?php endif; ?>
  </div>

</div>
