<?php
/**
 * Campaign detail for a volunteer - the screen behind a card title on /cleanup.
 *
 * Cleanup-User.svg has no standalone detail frame: the browse card joins
 * straight from the list, and everything a volunteer is shown about a campaign
 * lives in the right-hand column of the Join frame. So this page is that column
 * opened out, with the Join button the list would otherwise have given. Open to
 * guests, which is why the call to action drops to Sign in when logged out.
 *
 * The Figma's exact location, minimum age, visibility setting and "Viewed"
 * counter have no column in `cleanup_events`. They are collected on the create
 * form with a hint and left off this screen rather than printed blank - an empty
 * row or an invented number reads worse than an absent one.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Cleanup\CleanupEvent;
use Models\Organization;

$ev        = $event;
$spotsLeft = max(0, (int) $ev['volunteer_limit'] - (int) $ev['registered']);
$fill      = CleanupEvent::fillPercent($ev);
$hours     = rtrim(rtrim((string) $ev['duration_hours'], '0'), '.');

$orgType = Organization::TYPES[$ev['org_type']] ?? 'Organisation';
$orgSub  = $orgType . ($ev['organiser_verified'] ? ' · Verified' : '');
?>

<div class="cl-page">

<a class="back-link" href="<?= url('/cleanup') ?>"><?= icon('arrow-left') ?>Back to Campaigns</a>

<div class="cl-head">
  <div class="cl-head__text">
    <h1><?= e($ev['title']) ?></h1>
    <p>Organised by <?= e($ev['organiser']) ?> · <?= e($ev['district']) ?></p>
  </div>
  <div class="cl-head__actions">
    <span class="cl-status cl-status--<?= e(CleanupEvent::statusTone($ev['status'])) ?>">
      <?= e(CleanupEvent::statusLabel($ev['status'])) ?>
    </span>
    <span class="cl-status cl-status--neutral"><?= e(CleanupEvent::categoryLabel($ev['category'])) ?></span>
  </div>
</div>

<div class="split">
  <div class="stack">

    <section class="panel">
      <div class="panel__head"><?= icon('file-text') ?><h2>About this campaign</h2></div>
      <div class="panel__body">
        <p style="font-size:var(--fs-base);color:var(--ink-2)"><?= e($ev['description']) ?></p>

        <dl class="kv" style="margin-top:var(--s-5)">
          <div><dt>Date</dt><dd><?= e(format_date($ev['event_date'])) ?></dd></div>
          <div><dt>Time</dt>
            <dd><?= e(date('g:i A', strtotime($ev['start_time']))) ?> -
                <?= e(date('g:i A', strtotime($ev['end_time']))) ?></dd></div>
          <div><dt>Meeting point</dt><dd><?= e($ev['meeting_point']) ?></dd></div>
          <div><dt>Duration</dt><dd><?= e($hours) ?> hours</dd></div>
          <?php if ($ev['equipment_provided']): ?>
            <div><dt>Equipment provided</dt><dd><?= e($ev['equipment_provided']) ?></dd></div>
          <?php endif; ?>
        </dl>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('shield-check') ?><h2>Safety guidelines</h2></div>
      <div class="panel__body">
        <p style="font-size:var(--fs-sm);color:var(--ink-2)"><?= e($ev['safety_guidelines']) ?></p>
        <p class="muted" style="margin-top:var(--s-3)">
          You receive these guidelines, weather alerts and emergency instructions by notification
          before and during the campaign.
        </p>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('users') ?><h2>Volunteer places</h2></div>
      <div class="panel__body">
        <div class="cl-progress"><div class="cl-progress__bar" style="width:<?= e($fill) ?>%"></div></div>
        <p class="cl-progress-note">
          <?= e($ev['registered']) ?> of <?= e($ev['volunteer_limit']) ?> places taken ·
          <strong><?= e($spotsLeft) ?> left</strong>
        </p>
      </div>
    </section>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><?= icon('building') ?><h2>Organiser</h2></div>
      <div class="panel__body">
        <strong style="display:block;font-size:var(--fs-base);color:var(--ink)"><?= e($ev['organiser']) ?></strong>
        <small style="color:var(--muted-3)"><?= e($orgSub) ?></small>

        <?php if ($ev['disaster_report']): ?>
          <dl class="kv kv--single" style="margin-top:var(--s-4)">
            <div><dt>Related disaster report</dt><dd><?= e($ev['disaster_report']) ?></dd></div>
          </dl>
        <?php endif; ?>

        <div style="margin-top:var(--s-4);display:grid;gap:var(--s-2)">
          <?php if ($ev['status'] === EVENT_COMPLETED): ?>
            <a class="btn btn--outline btn--block" href="<?= url('/cleanup/' . $ev['id'] . '/feedback') ?>">
              Give Feedback
            </a>
          <?php elseif (!is_logged_in()): ?>
            <a class="btn btn--primary btn--block" href="<?= url('/login') ?>">Sign in to Join</a>
          <?php elseif ($joined): ?>
            <?php
              // Where this member's own sign-up stands. A disabled button on its
              // own never said whether the registration had gone anywhere.
              [$regText, $regTone] = match (true) {
                  $myAttendance && $myAttendance['checked_in_at'] !== null
                      => ['Checked in on the day', 'green'],
                  $myRegistration['status'] === 'confirmed'
                      => ['Confirmed by the organiser', 'green'],
                  default
                      => ['Waiting for the organiser to confirm your place', 'amber'],
              };
            ?>
            <div class="cl-note cl-note--<?= e($regTone) ?>">
              <strong>You are registered</strong>
              <p><?= e($regText) ?>. Registered <?= e(time_ago($myRegistration['created_at'])) ?>.</p>
            </div>
            <a class="btn btn--outline btn--block" href="<?= url('/my/activities') ?>">
              See my registrations
            </a>
            <?php /* Withdrawing. Closed once the organiser has checked the
                      volunteer in - they came, and the attendance record is the
                      organiser's to change. */ ?>
            <?php if (!$myAttendance || $myAttendance['checked_in_at'] === null): ?>
              <form method="post" action="<?= url('/cleanup/' . $ev['id'] . '/leave') ?>">
                <?= csrf_field() ?>
                <button class="btn btn--danger btn--block"
                        data-confirm="Leave this campaign? Your place is given back to the organiser.">
                  Leave Campaign
                </button>
              </form>
            <?php endif; ?>
          <?php elseif (role_is(ROLE_COMMUNITY)): ?>
            <a class="btn btn--primary btn--block" href="<?= url('/cleanup/' . $ev['id'] . '/join') ?>">Join Campaign</a>
          <?php else: ?>
            <button class="btn btn--outline btn--block" type="button" disabled>
              Volunteering is for community accounts
            </button>
          <?php endif; ?>

          <a class="btn btn--ghost btn--block" href="<?= url('/organizations') ?>">View Organiser Profile</a>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('clipboard') ?><h2>What to bring</h2></div>
      <div class="panel__body">
        <ul class="cl-bullets">
          <li>Gloves and closed footwear</li>
          <li>Reusable water bottle</li>
          <li>Sun protection / cap</li>
          <li>Personal medication if any</li>
        </ul>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('bar-chart') ?><h2>Campaign reach</h2></div>
      <div class="panel__body">
        <div class="mini-list">
          <div><span>Registered</span><span><?= e($ev['registered']) ?></span></div>
          <div><span>Checked in</span><span><?= e($ev['checked_in']) ?></span></div>
          <div><span>Feedback received</span><span><?= e($ev['feedback']) ?></span></div>
        </div>
      </div>
    </section>
  </div>
</div>

</div>
