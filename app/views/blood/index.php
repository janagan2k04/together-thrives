<?php
/**
 * "Blood Donation" — the community hub, from Blood-User.svg.
 *
 * One screen, five tabs. All / Upcoming Camps / Critical Requests show the two
 * public lists; My Donations and My Registrations are the donor's own history.
 * The tab is a query string so each is a shareable URL.
 */
require_once APP_PATH . '/views/partials/icons.php';

$showCamps    = in_array($tab, ['all', 'camps'], true);
$showCritical = in_array($tab, ['all', 'critical'], true);
$signedIn     = is_logged_in();
?>
<div class="page-wrap">

  <div class="blood-head">
    <span class="blood-head__icon"><?= icon('droplet') ?></span>
    <div class="blood-head__text">
      <h1>Blood Donation</h1>
      <p>Donate blood, save lives. Your small act can make a big difference.</p>
    </div>
  </div>

  <?php if ($signedIn): ?>
    <div class="impact-strip">
      <p class="impact-strip__label">Your Impact</p>
      <?php foreach ($impact as [$value, $label, $tone]): ?>
        <div class="impact-tile impact-tile--<?= e($tone) ?>">
          <strong><?= e($value) ?></strong>
          <span><?= e($label) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <nav class="blood-tabs">
    <?php foreach ($tabs as $key => $label): ?>
      <?php
        // My Donations and My Registrations are personal, so a signed-out
        // visitor is not offered them at all.
        if (!$signedIn && in_array($key, ['donations', 'registrations'], true)) continue;
      ?>
      <a class="<?= $tab === $key ? 'is-active' : '' ?>"
         href="<?= url('/blood' . ($key === 'all' ? '' : '?tab=' . $key)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <span class="blood-tabs__spacer"></span>
    <?php if ($signedIn): ?>
      <a class="btn btn--blood" href="<?= url('/blood/critical/create') ?>">
        <?= icon('droplet') ?>Critical Blood Request
      </a>
    <?php else: ?>
      <a class="btn btn--blood" href="<?= url('/login') ?>">Sign in to donate</a>
    <?php endif; ?>
  </nav>

  <?php /* ---------------- Upcoming camps ---------------- */ ?>
  <?php if ($showCamps): ?>
    <section class="sec" style="margin-bottom:var(--s-5)">
      <div class="sec__head">
        <div class="sec__spacer">
          <strong>Upcoming Blood Donation Camps</strong>
          <small>Camps near Colombo, soonest first</small>
        </div>
        <?php if ($tab === 'all'): ?>
          <a class="btn-mini" href="<?= url('/blood?tab=camps') ?>">View All</a>
        <?php endif; ?>
      </div>
      <div class="sec__body sec__body--flush">
        <?php foreach ($camps as $c): ?>
          <article class="camp-row">
            <span class="camp-row__thumb"></span>
            <span class="countdown countdown--<?= e($c['tone']) ?>"><?= e($c['countdown']) ?></span>

            <div class="camp-row__body">
              <h3><?= e($c['title']) ?></h3>
              <div class="camp-row__meta">
                <span><?= icon('building') ?><?= e($c['organiser']) ?></span>
              </div>
              <div class="camp-row__meta">
                <span><?= icon('calendar') ?><?= e($c['date']) ?></span>
                <span><?= icon('clock') ?><?= e($c['time']) ?></span>
                <span><?= icon('users') ?><?= e($c['registered']) ?> / <?= e($c['target']) ?> Registered</span>
              </div>
            </div>

            <div class="camp-row__side">
              <a class="btn btn--blood" href="<?= url('/blood/' . $c['id']) ?>"><?= icon('eye') ?>View Details</a>
              <a class="btn-mini" href="<?= url('/blood/' . $c['id']) ?>">Share</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php /* ---------------- Critical requests ---------------- */ ?>
  <?php if ($showCritical): ?>
    <section class="sec" style="margin-bottom:var(--s-5)">
      <div class="sec__head">
        <div class="sec__spacer">
          <strong>Critical Blood Requests</strong>
          <small>Verified by an administrator before donors are notified</small>
        </div>
        <?php if ($tab === 'all'): ?>
          <a class="btn-mini" href="<?= url('/blood?tab=critical') ?>">View All</a>
        <?php endif; ?>
      </div>
      <div class="sec__body">
        <div class="crit-grid">
          <?php foreach ($requests as $q): ?>
            <article class="crit-card">
              <span class="priority priority--<?= e($q['tone']) ?>" style="justify-self:start">
                <?= e($q['priority']) ?>
              </span>

              <div class="crit-card__top">
                <span class="group-dot"><?= icon('droplet') ?></span>
                <div>
                  <span class="group-big"><?= e($q['group']) ?></span>
                  <span class="crit-card__units"><?= e($q['units']) ?> Unit<?= $q['units'] === 1 ? '' : 's' ?> Needed</span>
                </div>
              </div>

              <div class="crit-card__meta">
                <span><?= icon('building') ?><?= e($q['hospitalShort']) ?></span>
                <span class="crit-card__due"><?= icon('clock') ?>Needed by: <?= e($q['neededBy']) ?></span>
              </div>

              <div class="crit-card__actions">
                <a class="btn <?= $q['tone'] === 'amber' ? 'btn--amber' : 'btn--blood' ?>"
                   href="<?= url($signedIn ? '/blood/critical/' . $q['id'] : '/login') ?>">View Request</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php /* ---------------- My Donations ---------------- */ ?>
  <?php if ($tab === 'donations'): ?>
    <div class="alert alert--success" style="margin-bottom:var(--s-5)">
      <strong>You are eligible to donate again on 02 Aug 2026.</strong>
      A minimum gap of four months is required between whole-blood donations.
      Last donation: 02 Apr 2026.
    </div>

    <section class="sec" style="margin-bottom:var(--s-5)">
      <div class="sec__head">
        <div class="sec__spacer">
          <strong>Donation History</strong>
          <small>Every completed donation recorded against your account</small>
        </div>
        <button class="btn-mini" type="button" disabled><?= icon('download') ?>Export</button>
      </div>
      <div class="sec__body sec__body--flush">
        <table class="donor-table">
          <thead>
            <tr><th>Camp / Organiser</th><th>Date</th><th>Type</th><th>Units</th><th>Status</th><th>Certificate</th></tr>
          </thead>
          <tbody>
            <?php foreach ($donations as $d): ?>
              <tr>
                <td>
                  <strong style="display:block;color:var(--ink)"><?= e($d['camp']) ?></strong>
                  <small style="color:var(--muted-3)"><?= e($d['organiser']) ?></small>
                </td>
                <td><?= e($d['date']) ?></td>
                <td><span class="group-tag"><?= e($d['group']) ?></span></td>
                <td><?= e($d['units']) ?></td>
                <td><span class="pill pill--active"><?= e($d['status']) ?></span></td>
                <td>
                  <a class="btn btn--outline" href="<?= url('/blood/certificate/' . $d['cert']) ?>">Download</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="sec">
      <div class="sec__head">
        <div class="sec__spacer">
          <strong>Appreciation Certificates</strong>
          <small>Issued automatically after each completed donation</small>
        </div>
      </div>
      <div class="sec__body">
        <div class="crit-grid">
          <?php foreach (array_slice($donations, 0, 3) as $d): ?>
            <div class="crit-card">
              <strong style="font-size:var(--fs-sm);color:var(--ink)"><?= e($d['camp']) ?></strong>
              <small style="color:var(--muted-3);font-size:var(--fs-xs)">
                <?= e($d['cert']) ?> · <?= e($d['date']) ?>
              </small>
              <a class="btn btn--outline" href="<?= url('/blood/certificate/' . $d['cert']) ?>">View</a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php /* ---------------- My Registrations ---------------- */ ?>
  <?php if ($tab === 'registrations'): ?>
    <section class="sec" style="margin-bottom:var(--s-5)">
      <div class="sec__head">
        <div class="sec__spacer">
          <strong>Upcoming Registrations</strong>
          <small>Camps you have joined. Screening is evaluated automatically when you join.</small>
        </div>
      </div>
      <div class="sec__body sec__body--flush">
        <?php foreach ($registrations as $reg): ?>
          <article class="camp-row">
            <span class="camp-row__thumb" style="background:var(--red-tint)"></span>
            <div class="camp-row__body">
              <h3><?= e($reg['camp']) ?></h3>
              <div class="camp-row__meta"><span><?= e($reg['where']) ?></span></div>
              <div class="camp-row__meta"><span><?= e($reg['when']) ?></span></div>
              <p class="muted" style="margin-top:6px"><?= e($reg['note']) ?></p>
            </div>
            <div style="text-align:right;flex:none">
              <span class="countdown countdown--<?= e($reg['tone']) ?>"><?= e($reg['screening']) ?></span>
              <div class="muted" style="margin-top:6px;font-size:var(--fs-xs)"><?= e($reg['on']) ?></div>
            </div>
            <div class="camp-row__side">
              <a class="btn btn--outline" href="<?= url('/messages') ?>">Group Chat</a>
              <a class="btn btn--blood" href="<?= url('/blood/' . $reg['campId']) ?>">View Details</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="sec">
      <div class="sec__head">
        <div class="sec__spacer">
          <strong>Critical Request Responses</strong>
          <small>Your contact details are shared with the requester only when eligible.</small>
        </div>
      </div>
      <div class="sec__body sec__body--flush">
        <table class="donor-table">
          <thead>
            <tr><th>Request / Hospital</th><th>Blood Type</th><th>Responded</th><th>Screening</th><th>Contact Shared</th></tr>
          </thead>
          <tbody>
            <?php foreach ($responses as $rsp): ?>
              <tr>
                <td>
                  <strong style="display:block;color:var(--ink)"><?= e($rsp['request']) ?></strong>
                  <small style="color:var(--muted-3)"><?= e($rsp['hospital']) ?></small>
                </td>
                <td><span class="group-big" style="font-size:var(--fs-base)"><?= e($rsp['group']) ?></span></td>
                <td><?= e($rsp['on']) ?></td>
                <td>
                  <span class="pill pill--<?= $rsp['screening'] === 'Eligible' ? 'active' : 'rejected' ?>">
                    <?= e($rsp['screening']) ?>
                  </span>
                </td>
                <td style="<?= $rsp['shared'] === 'Shared' ? 'color:var(--green-dark);font-weight:600' : 'color:var(--muted-3)' ?>">
                  <?= e($rsp['shared']) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <p class="sample-note" style="margin-top:var(--s-5)">
    <?= icon('help-circle') ?>
    Week 3 render. <code>blood_campaigns</code>, <code>critical_blood_requests</code> and
    <code>blood_donation_records</code> are in the frozen schema and nothing writes to them yet —
    the real queries land with Member 3's CRUD in weeks 4–5.
  </p>

</div>
