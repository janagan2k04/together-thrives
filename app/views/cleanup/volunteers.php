<?php
/**
 * "Org - Registered Volunteers & Attendance" - frame 5 of Cleanup-Org.svg.
 *
 * Every row is a real `event_registrations` row joined with `attendance` and
 * `work_areas`. The row buttons differ by state, which is the whole point of the
 * screen: pending rows get Approve, confirmed-but-absent rows get Check In, and
 * rows already checked in get neither.
 *
 * The Figma's Team Leader column is not here: `work_areas.team_leader_id` is
 * nullable and no screen names one, so the column could only ever print a dash.
 *
 * One form wraps the table so the checkboxes, "Assign to Work Area" and "Mark
 * Selected as Checked In" all submit the same selection. The per-row Approve and
 * Check In buttons are their own small forms placed after it - a form cannot be
 * nested inside another, so they use the `form` attribute to stay outside it.
 */
require_once APP_PATH . '/views/partials/icons.php';

$ev    = $event;
$notIn = max(0, (int) $ev['registered'] - (int) $ev['checked_in']);

/** Two letters for the avatar, from whatever the name gives us. */
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};

$base = url('/org/cleanup/' . $ev['id'] . '/volunteers');
?>

<a class="back-link" href="<?= url('/org/cleanup/' . $ev['id']) ?>">
  <?= icon('arrow-left') ?>Back to <?= e($ev['title']) ?>
</a>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__body">
    <div class="row-between">
      <div>
        <h1 style="font-size:var(--fs-2xl);font-weight:700;color:var(--ink)">Registered Volunteers &amp; Attendance</h1>
        <p class="muted" style="margin-top:4px">
          <?= e($ev['title']) ?> · <?= e($ev['event_date']) ?> ·
          <?= e($ev['start_time']) ?> - <?= e($ev['end_time']) ?> · <?= e($ev['meeting_point']) ?>
        </p>
      </div>
    </div>

    <form class="cl-area-form" method="post" action="<?= url('/org/cleanup/' . $ev['id'] . '/work-areas') ?>">
      <?= csrf_field() ?>
      <div class="field <?= has_error('name') ? 'field--invalid' : '' ?>" style="margin:0">
        <input class="field__input" name="name" value="<?= old('name') ?>"
               placeholder="Add a work area, e.g. Zone A - Riverbank" maxlength="120">
        <span class="field__error"><?= error_for('name') ?></span>
      </div>
      <button class="btn btn--outline" type="submit">Add Work Area</button>
    </form>
  </div>
</section>

<div class="stat-grid stat-grid--5">
  <div class="stat"><div class="stat__value"><?= e($ev['registered']) ?></div><div class="stat__label">Registered</div></div>
  <div class="stat stat--amber"><div class="stat__value"><?= e($ev['pending']) ?></div><div class="stat__label">Pending Approval</div></div>
  <div class="stat stat--green"><div class="stat__value"><?= e($ev['checked_in']) ?></div><div class="stat__label">Checked In</div></div>
  <div class="stat stat--red"><div class="stat__value"><?= e($notIn) ?></div><div class="stat__label">Not Checked In</div></div>
  <div class="stat"><div class="stat__value"><?= e($ev['volunteer_limit']) ?></div><div class="stat__label">Volunteer Limit</div></div>
</div>

<form class="cl-filters" method="get" action="<?= $base ?>">
  <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>"
         placeholder="Search volunteer name, NIC or phone…" style="min-width:280px">

  <?php
  $states = [
      ''            => 'All (' . (int) $ev['registered'] . ')',
      'confirmed'   => 'Confirmed',
      'pending'     => 'Pending Approval',
      'checked_in'  => 'Checked in',
      'not_checked' => 'Not checked in',
  ];
  foreach ($states as $key => $label): ?>
    <a class="chip <?= ($filters['state'] ?? '') === $key ? 'is-active' : '' ?>"
       href="<?= $base . ($key ? '?state=' . $key : '') ?>">
      <?= e($label) ?>
    </a>
  <?php endforeach; ?>

  <span class="cl-filters__spacer"></span>

  <select name="work_area" onchange="this.form.submit()">
    <option value="">Work Area: All Zones</option>
    <?php foreach ($workAreas as $zone): ?>
      <option value="<?= e($zone['id']) ?>" <?= (string) ($filters['work_area'] ?? '') === (string) $zone['id'] ? 'selected' : '' ?>>
        <?= e($zone['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<div class="panel">
  <div class="panel__head">
    <h2>Volunteer List</h2>
    <small>Volunteers must complete check-in before participating. Registration closes once the limit of <?= e($ev['volunteer_limit']) ?> is reached.</small>
  </div>

  <?php if (!$volunteers): ?>
    <div class="panel__body">
      <div class="empty-state">
        <?php if (($filters['q'] ?? '') !== '' || ($filters['state'] ?? '') !== '' || ($filters['work_area'] ?? '') !== ''): ?>
          <p>No volunteer matches that filter.</p>
          <a class="btn btn--outline" href="<?= $base ?>">Clear filters</a>
        <?php else: ?>
          <p>No one has signed up yet. Volunteers appear here as soon as they join, and you confirm them before the day.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="panel__body panel__body--flush">
      <form id="vol-bulk" method="post" action="<?= url('/org/cleanup/' . $ev['id'] . '/check-in') ?>">
        <?= csrf_field() ?>
      </form>

      <table class="cl-table">
        <thead>
          <tr>
            <th></th><th>Volunteer</th><th>Contact</th><th>Skills</th>
            <th>Work Area</th><th>Check-in</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($volunteers as $v): ?>
            <tr>
              <td>
                <input type="checkbox" form="vol-bulk" name="registration_id[]" value="<?= e($v['id']) ?>"
                       <?= $v['status'] === 'confirmed' ? '' : 'disabled' ?>>
              </td>
              <td>
                <div class="cl-vol">
                  <span class="cl-avatar"><?= e($initials((string) $v['full_name'])) ?></span>
                  <span><strong><?= e($v['full_name']) ?></strong><small><?= e($v['city'] ?? '') ?></small></span>
                </div>
              </td>
              <td><?= e($v['phone'] ?? '-') ?></td>
              <td><?= e($v['skills'] ?: '-') ?></td>
              <td><?= e($v['work_area'] ?? 'Not assigned') ?></td>
              <td>
                <?php if ($v['checked_in_at']): ?>
                  <span class="cl-checkin">Checked in<small><?= e(date('h:i A', strtotime($v['checked_in_at']))) ?></small></span>
                <?php else: ?>
                  <span class="cl-checkin cl-checkin--off">Not checked in<small>-</small></span>
                <?php endif; ?>
              </td>
              <td>
                <span class="cl-status cl-status--<?= $v['status'] === 'confirmed' ? 'green' : ($v['status'] === 'cancelled' ? 'red' : 'amber') ?>">
                  <?= e(\Models\Cleanup\EventRegistration::STATUSES[$v['status']] ?? $v['status']) ?>
                </span>
              </td>
              <td>
                <?php if ($v['status'] === 'pending'): ?>
                  <form method="post" class="inline"
                        action="<?= url('/org/cleanup/' . $ev['id'] . '/volunteers/' . $v['id'] . '/approve') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--outline" type="submit">Approve</button>
                  </form>
                <?php elseif ($v['status'] === 'confirmed' && !$v['checked_in_at']): ?>
                  <form method="post" class="inline"
                        action="<?= url('/org/cleanup/' . $ev['id'] . '/check-in') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="registration_id[]" value="<?= e($v['id']) ?>">
                    <button class="btn btn--primary" type="submit">Check In</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="cl-select-bar">
        <div class="cl-select-bar__text">
          <small>Showing <?= count($volunteers) ?> of <?= e($ev['registered']) ?> volunteers</small>
        </div>

        <select name="work_area_id" form="vol-bulk" <?= $workAreas ? '' : 'disabled' ?>>
          <option value="">Unassign</option>
          <?php foreach ($workAreas as $zone): ?>
            <option value="<?= e($zone['id']) ?>"><?= e($zone['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <?php /*
          Both buttons submit the same ticked rows. `formaction` is what lets one
          form have two destinations - the alternative is a second set of
          checkboxes kept in step with the first, which is a bug waiting to
          happen.
        */ ?>
        <button class="btn btn--outline" type="submit" form="vol-bulk"
                formaction="<?= url('/org/cleanup/' . $ev['id'] . '/assign-area') ?>">
          Assign to Work Area
        </button>
        <button class="btn btn--primary" type="submit" form="vol-bulk">Mark Selected as Checked In</button>
      </div>
    </div>
  <?php endif; ?>
</div>
