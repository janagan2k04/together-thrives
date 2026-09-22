<?php
/**
 * Administrator review of cleanup campaigns.
 *
 * No frame in admin.svg covers this, so it follows its sibling screens -
 * views/admin/verification/index.php, views/reuse/admin-letters.php,
 * views/blood/admin-campaigns.php and views/donation/admin-requests.php - down to
 * the filter control, because an administrator moving between them should not
 * have to learn a fifth table.
 *
 * Rejecting asks for a reason inline. `cleanup_events` has no `reject_reason`,
 * so the note goes in `cancel_reason` and the organiser's own screens print it
 * back to them.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Cleanup\CleanupEvent;

$tabs = [
    EVENT_DRAFT     => 'Awaiting review',
    EVENT_PUBLISHED => 'Approved',
    EVENT_CANCELLED => 'Rejected',
];

$pillFor = [
    EVENT_DRAFT     => 'pill--pending',
    EVENT_PUBLISHED => 'pill--active',
    EVENT_CANCELLED => 'pill--rejected',
];

$clock = static fn(?string $t): string => $t ? date('g:i A', strtotime($t)) : '-';
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Cleanup Campaign Review</h1>
    <p>
      Campaigns posted by organisations. Volunteers are only shown a campaign
      once it is approved here, so check the date, the meeting point, the
      volunteer limit and the safety guidelines before letting one through -
      people turn up in person to these.
    </p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/cleanup') ?>">
  <select name="status" onchange="this.form.submit()">
    <?php foreach ($tabs as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>>
        <?= e($label) ?><?= $value === EVENT_DRAFT && $pending > 0 ? ' (' . (int) $pending . ')' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<div class="data-card">
  <?php if (!$events): ?>
    <div class="admin-empty">
      <span class="admin-empty__icon"><?= icon('check-circle') ?></span>
      <?php if ($status === EVENT_DRAFT): ?>
        <strong>No campaigns waiting for review</strong>
        <p>New ones appear here when an organisation posts a cleanup campaign.</p>
      <?php else: ?>
        <strong>Nothing in this state</strong>
        <p>No campaign has been <?= e(strtolower($tabs[$status])) ?> yet.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Campaign</th>
          <th>Organiser</th>
          <th>When</th>
          <th>Safety</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($events as $ev): ?>
          <tr>
            <td>
              <div class="cell-title"><?= e($ev['title']) ?></div>
              <div class="cell-sub">
                <?= e(CleanupEvent::categoryLabel($ev['category'])) ?> ·
                <?= e($ev['district']) ?>
              </div>
              <div class="cell-sub"><?= e(str_limit($ev['description'], 90)) ?></div>
              <?php if ($ev['disaster_report']): ?>
                <div class="cell-sub">Linked report: <?= e($ev['disaster_report']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="cell-title"><?= e($ev['organiser']) ?></div>
              <div class="cell-sub"><?= e($ev['organiser_email']) ?></div>
            </td>
            <td>
              <div class="cell-title"><?= e(format_date($ev['event_date'])) ?></div>
              <div class="cell-sub">
                <?= e($clock($ev['start_time'])) ?> - <?= e($clock($ev['end_time'])) ?>
              </div>
              <div class="cell-sub">
                Meeting point: <?= e($ev['meeting_point']) ?>
              </div>
              <div class="cell-sub"><?= e($ev['volunteer_limit']) ?> volunteer places</div>
            </td>
            <td>
              <div class="cell-sub" style="max-width:20rem">
                <?= e(str_limit((string) $ev['safety_guidelines'], 160)) ?>
              </div>
            </td>
            <td>
              <?php if ($ev['status'] === EVENT_DRAFT): ?>
                <div class="cell-actions">
                  <form method="post" action="<?= url('/admin/cleanup/' . $ev['id'] . '/approve') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--approve"
                            data-confirm="Approve &quot;<?= e($ev['title']) ?>&quot;? Volunteers will see it and can sign up.">
                      Approve
                    </button>
                  </form>
                </div>
                <form method="post" action="<?= url('/admin/cleanup/' . $ev['id'] . '/reject') ?>"
                      style="margin-top:var(--s-2)">
                  <?= csrf_field() ?>
                  <input class="field__input" name="cancel_reason" maxlength="255"
                         placeholder="Reason for rejecting" style="font-size:var(--fs-xs)">
                  <button class="btn-mini btn-mini--danger" style="margin-top:4px"
                          data-confirm="Reject this campaign? The organiser is shown your reason and can resubmit.">
                    Reject
                  </button>
                </form>
              <?php else: ?>
                <span class="pill <?= e($pillFor[$ev['status']] ?? 'pill--neutral') ?>">
                  <?= e($tabs[$ev['status']] ?? CleanupEvent::statusLabel($ev['status'])) ?>
                </span>
                <?php if (!empty($ev['cancel_reason'])): ?>
                  <div class="cell-sub"><?= e($ev['cancel_reason']) ?></div>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
