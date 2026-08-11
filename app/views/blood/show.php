<?php
/** "Blood - Camp Details (View Details)" — the screen behind the View Details button. */
require_once APP_PATH . '/views/partials/icons.php';

$pct       = $camp['target'] > 0 ? min(100, (int) round($camp['registered'] / $camp['target'] * 100)) : 0;
$remaining = max(0, $camp['target'] - $camp['registered']);

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
          <span class="sec__ico"></span>
          <div><strong>About This Camp</strong><small>What to expect at this camp</small></div>
        </div>
        <div class="sec__body">
          <div class="chip-row" style="margin-bottom:var(--s-4)">
            <span class="countdown countdown--<?= e($camp['tone']) ?>"><?= e($camp['countdown']) ?></span>
            <span class="countdown countdown--blue">Verified Organiser</span>
            <span class="countdown countdown--green">Open for Registration</span>
          </div>
          <p><?= e($camp['description']) ?></p>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <span class="sec__ico"></span>
          <div><strong>Schedule &amp; Location</strong><small>When and where to arrive</small></div>
        </div>
        <div class="sec__body">
          <div class="readout-grid readout-grid--4" style="margin-bottom:var(--s-4)">
            <div class="readout"><span class="readout__label">Date</span>
              <span class="readout__value"><?= e($camp['date']) ?></span></div>
            <div class="readout"><span class="readout__label">Time</span>
              <span class="readout__value"><?= e($camp['time']) ?></span></div>
            <div class="readout"><span class="readout__label">City</span>
              <span class="readout__value"><?= e($camp['city']) ?></span></div>
            <div class="readout"><span class="readout__label">Camp Type</span>
              <span class="readout__value"><?= e($camp['campType']) ?></span></div>
          </div>
          <div class="readout-grid">
            <div class="readout"><span class="readout__label">Venue</span>
              <span class="readout__value"><?= e($camp['venue']) ?></span></div>
            <div class="readout"><span class="readout__label">Contact</span>
              <span class="readout__value"><?= e($camp['contact']) ?></span></div>
          </div>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <span class="sec__ico sec__ico--green"></span>
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
          <span class="sec__ico"></span>
          <div><strong>Registration</strong><small>Secure your slot</small></div>
        </div>
        <div class="sec__body">
          <p style="font-size:var(--fs-2xl);font-weight:700;color:var(--ink)">
            <?= e($camp['registered']) ?>
            <span style="font-size:var(--fs-sm);font-weight:400;color:var(--muted)">
              of <?= e($camp['target']) ?> registered
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
          <button class="btn btn--outline btn--block" style="margin-top:10px" type="button" disabled>Share Camp</button>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <span class="sec__ico sec__ico--blue"></span>
          <div><strong>Organiser</strong></div>
        </div>
        <div class="sec__body">
          <div class="donor-cell">
            <span class="avatar"><?= e($camp['initials']) ?></span>
            <div>
              <strong><?= e($camp['organiser']) ?></strong>
              <small><?= e($camp['organiserSub']) ?></small>
            </div>
          </div>
          <p class="muted" style="margin-top:var(--s-3)">
            <?= e($camp['organised']) ?> camps organised · <?= e(number_format($camp['reached'])) ?> donors reached
          </p>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <span class="sec__ico sec__ico--amber"></span>
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
