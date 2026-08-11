<?php
/**
 * "Community User Homepage" from Comm_User-Homepage.svg.
 *
 * Impact figures, registered campaigns and the two right-hand rails are week 3
 * sample content — the modules that would produce them are weeks 4–5.
 */
require_once APP_PATH . '/views/partials/icons.php';

$impact = [
    ['Joined Cleanup Camps', 14, 'green'],
    ['Items Donated',        23, 'amber'],
    ['Joined Blood Camps',    6, 'red'],
    ['Volunteer Hours',       8, 'blue'],
];

$campaigns = [
    ['title' => 'Beach Cleanup - Galle', 'by' => 'Ocean Guardians', 'state' => 'Active',
     'progress' => '60% Completed', 'meta' => '42 volunteers · 1 day left', 'tone' => 'green'],
    ['title' => 'Kelani River Cleanup', 'by' => 'Green Sri Lanka Foundation', 'state' => 'Active',
     'progress' => '35% Completed', 'meta' => '18 volunteers · 6 days left', 'tone' => 'green'],
    ['title' => 'Colombo Blood Camp', 'by' => 'National Blood Service', 'state' => 'Upcoming',
     'progress' => 'Screening open', 'meta' => 'Sat 27 Jun · 8:00 AM', 'tone' => 'blue'],
];

$requests = [
    ['title' => 'Blood Required - O+', 'state' => 'Critical', 'note' => 'Donors yet to match',
     'meta' => 'Urgent need · 2h ago', 'tone' => 'red'],
    ['title' => 'Dining Table with 4 Chairs', 'state' => 'Accepted', 'note' => 'Collection this weekend',
     'meta' => 'Reuse request · yesterday', 'tone' => 'green'],
];

$alerts = [
    ['High',   'Flood Situation in Rathnapura District', 'Heavy rainfall and flooding reported.', '2h ago'],
    ['Medium', 'Landslide Risk in Kegalle District',     'Stay alert and avoid risky areas.',     '4h ago'],
];

$nearby = [
    ['Cleanup Campaign', 'Wellawatte Beach Cleanup',  'Tomorrow · 7:00 AM',   '/cleanup'],
    ['Cleanup Campaign', 'Dengue Prevention Drive',   '26 Jun 2026 · 7:30 AM', '/cleanup'],
    ['Blood Camp',       'Colombo General Hospital',  '28 Jun 2026 · 8:00 AM', '/blood'],
];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Welcome back, <?= e($user['full_name'] ?? '') ?>!</h1>
    <p>Let's make a positive impact today.</p>
  </div>
  <div class="page-head__actions">
    <a class="btn btn--outline" href="<?= url('/my/activities') ?>">My Activities</a>
    <a class="btn btn--primary" href="<?= url('/my/reuse/create') ?>">Donate an Item</a>
  </div>
</div>

<div class="split">
  <div class="stack">

    <section class="panel">
      <div class="panel__head"><h2>Your Impact</h2></div>
      <div class="panel__body">
        <div class="impact-grid">
          <?php foreach ($impact as [$label, $value, $tone]): ?>
            <div class="impact impact--<?= e($tone) ?>">
              <div class="impact__label"><?= e($label) ?></div>
              <div class="impact__value"><?= e($value) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head">
        <h2>Registered Campaigns</h2>
        <a class="btn-mini" href="<?= url('/my/activities') ?>">View all</a>
      </div>
      <div class="panel__body">
        <div class="grid grid--3">
          <?php foreach ($campaigns as $c): ?>
            <article class="card">
              <span class="badge badge--<?= $c['tone'] === 'green' ? 'success' : 'neutral' ?>"><?= e($c['state']) ?></span>
              <h3 style="margin-top:var(--s-3);font-size:var(--fs-base);font-weight:700"><?= e($c['title']) ?></h3>
              <p class="muted" style="margin-top:2px">By <?= e($c['by']) ?></p>
              <p style="margin-top:8px;font-size:var(--fs-sm);color:var(--green-dark);font-weight:600"><?= e($c['progress']) ?></p>
              <p class="muted" style="margin-top:6px;font-size:var(--fs-xs)"><?= e($c['meta']) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head">
        <h2>Your Requests</h2>
        <a class="btn-mini" href="<?= url('/my/activities') ?>">View all</a>
      </div>
      <div class="panel__body">
        <div class="grid grid--2">
          <?php foreach ($requests as $q): ?>
            <article class="card">
              <span class="badge badge--<?= $q['tone'] === 'red' ? 'danger' : 'success' ?>"><?= e($q['state']) ?></span>
              <h3 style="margin-top:var(--s-3);font-size:var(--fs-base);font-weight:700"><?= e($q['title']) ?></h3>
              <p class="muted" style="margin-top:2px"><?= e($q['note']) ?></p>
              <p class="muted" style="margin-top:6px;font-size:var(--fs-xs)"><?= e($q['meta']) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><h2 style="color:var(--red-dark)">Critical Alerts</h2></div>
      <div class="panel__body stack">
        <?php foreach ($alerts as [$level, $title, $body, $when]): ?>
          <div class="card" style="background:var(--surface-2)">
            <span class="badge badge--<?= $level === 'High' ? 'danger' : 'pending' ?>"><?= e($level) ?></span>
            <strong style="display:block;margin-top:8px;font-size:var(--fs-sm)"><?= e($title) ?></strong>
            <p class="muted" style="margin-top:4px"><?= e($body) ?></p>
            <small style="color:var(--muted-3);font-size:var(--fs-xs)"><?= e($when) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><h2>Upcoming &amp; Active Nearby</h2></div>
      <div class="panel__body stack">
        <?php foreach ($nearby as [$kind, $title, $when, $link]): ?>
          <div class="row-between">
            <div>
              <small class="muted" style="font-size:var(--fs-xs)"><?= e($kind) ?></small>
              <strong style="display:block;font-size:var(--fs-sm)"><?= e($title) ?></strong>
              <small class="muted" style="font-size:var(--fs-xs)"><?= e($when) ?></small>
            </div>
            <a class="btn-mini btn-mini--approve" href="<?= url($link) ?>">Join</a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>
