<?php
/**
 * "Org - Cleanup Campaign View Details" - frame 4 of Cleanup-Org.svg.
 *
 * Every write for a campaign lives on this screen: Edit and Cancel Campaign at
 * the top, attendance below, impact and certificates on the right. Cancel is the
 * module's DELETE - a soft delete plus `status = cancelled`, the shape CLAUDE.md
 * sets out - so it is a real form with a method override, not a link.
 *
 * The Figma's "Viewed" tile has no column and is not printed; the four tiles that
 * remain are counted from real rows.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Cleanup\CleanupEvent;
use Models\Cleanup\EventRegistration;

$ev        = $event;
$cancelled = $ev['status'] === EVENT_CANCELLED;
$hours     = rtrim(rtrim((string) $ev['duration_hours'], '0'), '.');

/** Two letters for the avatar, from whatever the name gives us. */
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};
?>

<a class="back-link" href="<?= url('/org/cleanup') ?>"><?= icon('arrow-left') ?>Back to My Cleanup Campaigns</a>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__body">
    <div class="row-between">
      <h1 style="font-size:var(--fs-2xl);font-weight:700;color:var(--ink)"><?= e($ev['title']) ?></h1>
      <span class="cl-status cl-status--<?= e(CleanupEvent::statusTone($ev['status'])) ?>">
        <?= e(CleanupEvent::statusLabel($ev['status'])) ?>
      </span>
      <a class="btn btn--outline" href="<?= url('/org/cleanup/' . $ev['id'] . '/edit') ?>">Edit</a>
      <a class="btn btn--outline" href="<?= url('/messages') ?>">Post Update</a>
      <?php if (!$cancelled && $ev['status'] !== EVENT_COMPLETED): ?>
        <form method="post" action="<?= url('/org/cleanup/' . $ev['id']) ?>" style="display:inline">
          <?= csrf_field() ?><?= method_field('DELETE') ?>
          <button class="btn btn--danger" data-confirm="Cancel this campaign? It is removed from the public list and cannot be undone here.">
            Cancel Campaign
          </button>
        </form>
      <?php endif; ?>
    </div>
    <p class="muted" style="margin-top:var(--s-3)">
      <?= e($ev['organiser']) ?> · <?= e($ev['meeting_point']) ?> · <?= e($ev['district']) ?> ·
      <?= e(format_date($ev['event_date'])) ?> ·
      <?= e(date('g:i A', strtotime($ev['start_time']))) ?> - <?= e(date('g:i A', strtotime($ev['end_time']))) ?>
    </p>
  </div>
</section>

<div class="stat-grid stat-grid--4">
  <div class="stat stat--green"><div class="stat__value"><?= e($ev['registered']) ?></div><div class="stat__label">Registered</div></div>
  <div class="stat stat--amber"><div class="stat__value"><?= e($ev['pending']) ?></div><div class="stat__label">Pending Approval</div></div>
  <div class="stat stat--blue"><div class="stat__value"><?= e($ev['checked_in']) ?></div><div class="stat__label">Checked In</div></div>
  <div class="stat"><div class="stat__value"><?= e($ev['feedback']) ?></div><div class="stat__label">Feedback Received</div></div>
</div>

<div class="split">
  <div class="stack">

    <section class="panel">
      <div class="panel__head"><?= icon('file-text') ?><h2>Campaign Information</h2></div>
      <div class="panel__body">
        <p style="font-size:var(--fs-base);color:var(--ink-2)"><?= e($ev['description']) ?></p>

        <dl class="kv" style="margin-top:var(--s-5);grid-template-columns:repeat(3,minmax(0,1fr))">
          <div><dt>Disaster Report</dt><dd><?= e($ev['disaster_report'] ?? 'Not linked') ?></dd></div>
          <div><dt>Category</dt><dd><?= e(CleanupEvent::categoryLabel($ev['category'])) ?></dd></div>
          <div><dt>Duration</dt><dd><?= e($hours) ?> hours</dd></div>
          <div><dt>Meeting Point</dt><dd><?= e($ev['meeting_point']) ?></dd></div>
          <div><dt>Volunteer Limit</dt><dd><?= e($ev['volunteer_limit']) ?></dd></div>
          <div><dt>Equipment Provided</dt><dd><?= e($ev['equipment_provided'] ?: 'Not listed') ?></dd></div>
        </dl>

        <div class="cl-note cl-note--blue" style="margin-top:var(--s-4)">
          <strong>Safety guidelines</strong><br>
          <?= e($ev['safety_guidelines']) ?>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head">
        <?= icon('users') ?>
        <h2>Registered Volunteers</h2>
        <a class="btn btn--primary" href="<?= url('/org/cleanup/' . $ev['id'] . '/volunteers') ?>">Mark Attendance</a>
      </div>
      <?php if (!$volunteers): ?>
        <div class="panel__body">
          <div class="empty-state">
            <p>No one has signed up yet. Volunteers appear here as soon as they join.</p>
          </div>
        </div>
      <?php else: ?>
      <div class="panel__body panel__body--flush">
        <p class="muted" style="padding:0 var(--s-5) var(--s-3)">
          <?= e($ev['registered']) ?> registered - <?= e($ev['pending']) ?> pending approval -
          registration closes at <?= e($ev['volunteer_limit']) ?>
        </p>
        <table class="table">
          <thead>
            <tr><th>Volunteer</th><th>Contact</th><th>Work Area</th><th>Check-in</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($volunteers, 0, 5) as $v): ?>
              <tr>
                <td>
                  <div class="cl-vol">
                    <span class="cl-avatar"><?= e($initials((string) $v['full_name'])) ?></span>
                    <span><strong><?= e($v['full_name']) ?></strong><small><?= e($v['city'] ?? '') ?></small></span>
                  </div>
                </td>
                <td><?= e($v['phone'] ?? '-') ?></td>
                <td><?= e($v['work_area'] ?? 'Not assigned') ?></td>
                <td>
                  <?php if ($v['checked_in_at']): ?>
                    <span class="cl-checkin">Checked in</span>
                  <?php else: ?>
                    <span class="cl-checkin cl-checkin--off">Not checked in</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="cl-status cl-status--<?= $v['status'] === 'confirmed' ? 'green' : ($v['status'] === 'cancelled' ? 'red' : 'amber') ?>">
                    <?= e(EventRegistration::STATUSES[$v['status']] ?? $v['status']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="row-between" style="padding:var(--s-4) var(--s-5)">
          <span></span>
          <a href="<?= url('/org/cleanup/' . $ev['id'] . '/volunteers') ?>">View all volunteers</a>
        </div>
      </div>
      <?php endif; ?>
      </div>
    </section>
  </div>

  <div class="stack">

    <?php if ($ev['status'] === EVENT_DRAFT): ?>
      <section class="panel panel--warn">
        <div class="panel__head"><?= icon('clock') ?><h2>Pending Verification</h2></div>
        <div class="panel__body">
          <p class="muted">
            An administrator has not approved this campaign yet. It is not visible to volunteers and
            no notifications have been sent. You can keep editing it while it waits.
          </p>
        </div>
      </section>
    <?php elseif ($cancelled): ?>
      <section class="panel panel--warn">
        <div class="panel__head"><?= icon('ban') ?><h2>Cancelled</h2></div>
        <div class="panel__body">
          <p class="muted">
            <?php if ($ev['deleted_at']): ?>
              You cancelled this campaign on <?= e(format_date($ev['deleted_at'])) ?>. It stays in
              your list and is no longer public.
            <?php else: ?>
              An administrator sent this campaign back:
              <strong><?= e($ev['cancel_reason'] ?: 'no reason given') ?></strong><br>
              Correct it and save - saving resubmits it for verification.
            <?php endif; ?>
          </p>
        </div>
      </section>
    <?php else: ?>
      <section class="panel">
        <div class="panel__head"><?= icon('check-circle') ?><h2>Verified &amp; Published</h2></div>
        <div class="panel__body">
          <p class="muted">
            An administrator approved this campaign, so volunteers can see it on
            <a href="<?= url('/cleanup/' . $ev['id']) ?>">the public campaign page</a> and sign up.
            Editing it sends it back for verification.
          </p>
        </div>
      </section>
    <?php endif; ?>

    <section class="panel">
      <div class="panel__head"><?= icon('bar-chart') ?><h2>Waste Management &amp; Impact</h2></div>
      <div class="panel__body">
        <?php if (!$waste): ?>
          <p class="muted">Nothing recorded yet. Add what the campaign collected, by type.</p>
        <?php else: ?>
          <div class="mini-list">
            <?php foreach ($waste as $w): ?>
              <div>
                <span><?= e($w['waste_type']) ?></span>
                <span><?= e(rtrim(rtrim(number_format((float) $w['quantity_kg'], 2), '0'), '.')) ?> kg</span>
              </div>
            <?php endforeach; ?>
            <div>
              <span><strong>Total</strong></span>
              <span><strong><?= e(rtrim(rtrim(number_format((float) $wasteTotal, 2), '0'), '.')) ?> kg</strong></span>
            </div>
          </div>
        <?php endif; ?>

        <form class="cl-waste-form" method="post" action="<?= url('/org/cleanup/' . $ev['id'] . '/waste') ?>">
          <?= csrf_field() ?>
          <div class="field <?= has_error('waste_type') ? 'field--invalid' : '' ?>">
            <input class="field__input" name="waste_type" value="<?= old('waste_type') ?>"
                   placeholder="Waste type, e.g. Plastic" maxlength="80">
            <span class="field__error"><?= error_for('waste_type') ?></span>
          </div>
          <div class="field <?= has_error('quantity_kg') ? 'field--invalid' : '' ?>">
            <input class="field__input" name="quantity_kg" value="<?= old('quantity_kg') ?>"
                   type="number" step="0.01" min="0" placeholder="kg">
            <span class="field__error"><?= error_for('quantity_kg') ?></span>
          </div>
          <button class="btn btn--primary" type="submit">Record</button>
        </form>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('shield-check') ?><h2>Certificates &amp; Feedback</h2></div>
      <div class="panel__body">
        <p class="muted">
          Digital appreciation certificates are issued by email only to volunteers who registered,
          attended and completed the campaign.
        </p>
        <p class="muted" style="margin-top:var(--s-3)">
          <?= e($ev['feedback']) ?> feedback received.
        </p>
      </div>
    </section>
  </div>
</div>

