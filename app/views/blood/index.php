<?php
/**
 * "Blood Donation" - the community hub, from Blood-User.svg.
 *
 * One screen, five tabs. All / Upcoming Camps / Critical Requests show the two
 * public lists; My Donations and My Registrations are the donor's own history.
 * The tab is a query string so each is a shareable URL.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Blood\ScreeningResponse;

$showCamps    = in_array($tab, ['all', 'camps'], true);
$showCritical = in_array($tab, ['all', 'critical'], true);
$signedIn     = is_logged_in();

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

/** Days until a camp, for the chip on its card. */
$chip = static function (string $date): array {
    $days = (int) floor((strtotime($date) - strtotime('today')) / 86400);
    return match (true) {
        $days <= 0  => ['Today', 'red'],
        $days === 1 => ['Tomorrow', 'green'],
        $days <= 3  => [$days . ' Days Left', 'amber'],
        default     => [$days . ' Days Left', 'blue'],
    };
};
?>
<div class="page-wrap">

  <div class="blood-head">
    <span class="blood-head__icon"><?= icon('droplet') ?></span>
    <div class="blood-head__text">
      <h1>Blood Donation</h1>
      <p>Donate blood, save lives. Your small act can make a big difference.</p>
    </div>
  </div>

  <?php if ($signedIn && $impact): ?>
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
          <small><?= e($campTotal) ?> approved camp<?= $campTotal === 1 ? '' : 's' ?>, soonest first</small>
        </div>
        <?php if ($tab === 'all' && $campTotal > count($camps)): ?>
          <a class="btn-mini" href="<?= url('/blood?tab=camps') ?>">View All</a>
        <?php endif; ?>
      </div>
      <div class="sec__body <?= $camps ? 'sec__body--flush' : '' ?>">
        <?php if (!$camps): ?>
          <div class="empty-state">
            <p>No upcoming camps have been approved yet.</p>
            <p class="muted">
              Camps appear here once an organisation or blood bank posts one and an administrator
              approves it.
            </p>
          </div>
        <?php endif; ?>

        <?php foreach ($camps as $c): ?>
          <?php [$countdown, $tone] = $chip($c['campaign_date']); ?>
          <article class="camp-row">
            <span class="camp-row__thumb"></span>
            <span class="countdown countdown--<?= e($tone) ?>"><?= e($countdown) ?></span>

            <div class="camp-row__body">
              <h3><?= e($c['title']) ?></h3>
              <div class="camp-row__meta">
                <span><?= icon('building') ?><?= e($c['organiser']) ?></span>
                <span><?= icon('map-pin') ?><?= e($c['venue']) ?><?= $c['address'] ? ', ' . e($c['address']) : '' ?>, <?= e($c['city']) ?></span>
              </div>
              <div class="camp-row__meta">
                <span><?= icon('calendar') ?><?= e($dates($c)) ?></span>
                <span><?= icon('clock') ?><?= e($clock($c['start_time'])) ?> - <?= e($clock($c['end_time'])) ?></span>
                <span><?= icon('users') ?><?= e($c['registered']) ?> / <?= e($c['target_donors']) ?> Registered</span>
              </div>
            </div>

            <div class="camp-row__side">
              <a class="btn btn--blood" href="<?= url('/blood/' . $c['id']) ?>"><?= icon('eye') ?>View Details</a>
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
    <?php if ($nextDonation): ?>
      <div class="alert alert--success alert--stack" style="margin-bottom:var(--s-5)">
        <?php if ($nextDonation > date('Y-m-d')): ?>
          <strong>You are eligible to donate again on <?= e(format_date($nextDonation)) ?>.</strong>
        <?php else: ?>
          <strong>You are eligible to donate again.</strong>
        <?php endif; ?>
        A minimum gap of four months is required between whole-blood donations.
      </div>
    <?php endif; ?>

    <section class="sec" style="margin-bottom:var(--s-5)">
      <div class="sec__head">
        <div class="sec__spacer">
          <strong>Donation History</strong>
          <small>Every completed donation recorded against your account</small>
        </div>
      </div>
      <?php if (!$donations): ?>
        <div class="sec__body">
          <div class="empty-state">
            <p>No donations recorded yet. A camp organiser records your donation on the day,
               and it appears here with your certificate.</p>
            <a class="btn btn--blood" href="<?= url('/blood?tab=camps') ?>">Find a camp</a>
          </div>
        </div>
      <?php else: ?>
        <div class="sec__body sec__body--flush">
          <table class="donor-table">
            <thead>
              <tr><th>Camp / Organiser</th><th>Date</th><th>Units</th><th>Certificate</th></tr>
            </thead>
            <tbody>
              <?php foreach ($donations as $d): ?>
                <tr>
                  <td>
                    <strong style="display:block;color:var(--ink)"><?= e($d['camp'] ?? 'Critical blood request') ?></strong>
                    <small style="color:var(--muted-3)"><?= e($d['organiser'] ?? '') ?></small>
                  </td>
                  <td><?= e(format_date($d['donated_at'])) ?></td>
                  <td><?= e(rtrim(rtrim(number_format((float) $d['units'], 2), '0'), '.')) ?></td>
                  <td>
                    <?php if ($d['campaign_id']): ?>
                      <a class="btn btn--outline" href="<?= url('/blood/certificate/' . $d['campaign_id']) ?>">Open</a>
                    <?php else: ?>
                      <span class="muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <?php $certs = array_values(array_filter($donations, fn($d) => (bool) $d['campaign_id'])); ?>
    <?php if ($certs): ?>
      <section class="sec">
        <div class="sec__head">
          <div class="sec__spacer">
            <strong>Appreciation Certificates</strong>
            <small>Issued automatically after each completed donation</small>
          </div>
        </div>
        <div class="sec__body">
          <div class="crit-grid">
            <?php foreach (array_slice($certs, 0, 3) as $d): ?>
              <div class="crit-card">
                <strong style="font-size:var(--fs-sm);color:var(--ink)"><?= e($d['camp']) ?></strong>
                <small style="color:var(--muted-3);font-size:var(--fs-xs)">
                  <?= e(format_date($d['donated_at'])) ?>
                </small>
                <a class="btn btn--outline" href="<?= url('/blood/certificate/' . $d['campaign_id']) ?>">View</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>

  <?php /* ---------------- My Registrations ---------------- */ ?>
  <?php if ($tab === 'registrations'): ?>
    <section class="sec" style="margin-bottom:var(--s-5)">
      <div class="sec__head">
        <div class="sec__spacer">
          <strong>My Registrations</strong>
          <small>Camps you have joined. Your screening answers are scored when you submit them.</small>
        </div>
      </div>
      <?php if (!$registrations): ?>
        <div class="sec__body">
          <div class="empty-state">
            <p>You have not joined a camp yet. Completing a camp's screening form registers you for it.</p>
            <a class="btn btn--blood" href="<?= url('/blood?tab=camps') ?>">Browse camps</a>
          </div>
        </div>
      <?php else: ?>
      <div class="sec__body sec__body--flush">
        <?php foreach ($registrations as $reg): ?>
          <article class="camp-row">
            <span class="camp-row__thumb" style="background:var(--red-tint)"></span>
            <div class="camp-row__body">
              <h3><?= e($reg['title']) ?></h3>
              <div class="camp-row__meta">
                <span><?= e($reg['venue']) ?><?= $reg['city'] ? ' - ' . e($reg['city']) : '' ?></span>
              </div>
              <div class="camp-row__meta">
                <span><?= e(format_date($reg['campaign_date'])) ?> - <?= e(substr((string) $reg['start_time'], 0, 5)) ?>
                      to <?= e(substr((string) $reg['end_time'], 0, 5)) ?></span>
              </div>
              <p class="muted" style="margin-top:6px"><?= e($reg['reason']) ?></p>
            </div>
            <div style="text-align:right;flex:none">
              <span class="countdown countdown--<?= e(ScreeningResponse::TONES[$reg['result']]) ?>">
                <?= e(ScreeningResponse::RESULTS[$reg['result']]) ?>
              </span>
              <div class="muted" style="margin-top:6px;font-size:var(--fs-xs)">
                Registered <?= e(format_date($reg['created_at'])) ?>
              </div>
            </div>
            <div class="camp-row__side">
              <a class="btn btn--blood" href="<?= url('/blood/' . $reg['campaign_id']) ?>">View Details</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <?php if ($responses): ?>
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
                  <strong style="display:block;color:var(--ink)"><?= e($rsp['blood_group']) ?> requirement</strong>
                  <small style="color:var(--muted-3)"><?= e($rsp['hospital']) ?><?= $rsp['city'] ? ', ' . e($rsp['city']) : '' ?></small>
                </td>
                <td><span class="group-big" style="font-size:var(--fs-base)"><?= e($rsp['blood_group']) ?></span></td>
                <td><?= e(time_ago($rsp['created_at'])) ?></td>
                <td>
                  <span class="pill pill--<?= $rsp['result'] === 'eligible' ? 'active' : 'rejected' ?>">
                    <?= e(ScreeningResponse::RESULTS[$rsp['result']]) ?>
                  </span>
                </td>
                <td style="<?= $rsp['result'] === 'eligible' ? 'color:var(--green-dark);font-weight:600' : 'color:var(--muted-3)' ?>">
                  <?= $rsp['result'] === 'eligible' ? 'Shared' : 'Not shared' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>
  <?php endif; ?>


</div>
