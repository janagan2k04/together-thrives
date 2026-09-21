<?php
/**
 * "Cleanup Campaigns (Org)" - frame 1 of Cleanup-Org.svg.
 *
 * One table for everything this organisation has posted, including the ones it
 * cancelled and the ones an administrator sent back: the delete is soft, so the
 * organiser keeps its own history even though the public sees nothing.
 *
 * The Actions column is View Details rather than Edit/Cancel - the Figma keeps
 * every write behind the detail screen, so there is exactly one place a campaign
 * is changed.
 *
 * The Figma's "Viewed" column has no column in `cleanup_events` and is not
 * printed. Reach here is places filled and feedback received, both counted from
 * real rows.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Cleanup\CleanupEvent;
?>

<div class="cl-head">
  <div class="cl-head__text">
    <h1>Cleanup / Volunteering Campaigns</h1>
    <p>Manage all cleanup, volunteering and disaster-related campaigns you have posted.</p>
  </div>
  <a class="btn btn--primary" href="<?= url('/org/cleanup/create') ?>">Create New Campaign</a>
</div>

<form class="cl-filters" method="get" action="<?= url('/org/cleanup') ?>">
  <select name="status" onchange="this.form.submit()">
    <option value="">All Status</option>
    <?php foreach (CleanupEvent::STATUSES as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
        <?= e($label) ?><?= !empty($counts[$key]) ? ' (' . (int) $counts[$key] . ')' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="category" onchange="this.form.submit()">
    <option value="">All Types</option>
    <?php foreach (CleanupEvent::CATEGORIES as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= ($filters['category'] ?? '') === $key ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="city" onchange="this.form.submit()">
    <option value="">All Locations</option>
    <?php foreach ($cities as $c): ?>
      <option value="<?= e($c['id']) ?>" <?= (string) ($filters['city'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
        <?= e($c['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<?php if (!$events): ?>
  <div class="card empty-state">
    You have not posted a cleanup campaign yet.
    <a href="<?= url('/org/cleanup/create') ?>">Create your first one</a>.
  </div>
<?php else: ?>
  <div class="panel">
    <div class="panel__body panel__body--flush">
      <table class="cl-table">
        <thead>
          <tr>
            <th>Campaign Details</th><th>Status</th><th>Date / Period</th>
            <th>Location</th><th>Volunteers</th><th>Reach &amp; Engagement</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $ev): ?>
            <tr>
              <td>
                <div class="cl-cell">
                  <span class="cl-cell__thumb"><?= icon('leaf') ?></span>
                  <div class="cl-cell__body">
                    <h3>
                      <?= e($ev['title']) ?>
                      <span class="cl-tag cl-tag--green"><?= e(CleanupEvent::categoryLabel($ev['category'])) ?></span>
                    </h3>
                    <p><?= e(str_limit($ev['description'], 70)) ?></p>
                    <span class="cl-cell__by">Organized by <?= e($ev['organiser']) ?></span>
                    <?php if ($ev['disaster_report']): ?>
                      <span class="cl-tag" style="margin-top:6px;display:inline-block">
                        <?= e($ev['disaster_report']) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>

              <td>
                <span class="cl-status cl-status--<?= e(CleanupEvent::statusTone($ev['status'])) ?>">
                  <?= e(CleanupEvent::statusLabel($ev['status'])) ?>
                </span>
                <?php if ($ev['status'] === EVENT_CANCELLED): ?>
                  <div class="cl-note cl-note--red" style="margin-top:6px">
                    <?= $ev['deleted_at']
                        ? 'You cancelled this campaign.'
                        : 'Sent back by an administrator: ' . e($ev['cancel_reason'] ?: 'no reason given') ?>
                  </div>
                <?php endif; ?>
              </td>

              <td>
                <div class="cl-when">
                  <span><?= icon('calendar') ?><?= e(format_date($ev['event_date'])) ?></span>
                  <span><?= icon('clock') ?><?= e(date('g:i A', strtotime($ev['start_time']))) ?> -
                        <?= e(date('g:i A', strtotime($ev['end_time']))) ?></span>
                </div>
              </td>

              <td>
                <div class="cl-when">
                  <span><?= icon('map-pin') ?><?= e($ev['city']) ?></span>
                  <small style="color:var(--muted-3)"><?= e($ev['district']) ?></small>
                </div>
              </td>

              <td>
                <div class="cl-figures">
                  <div><?= icon('users') ?><span><strong><?= e($ev['registered']) ?></strong><small>Registered</small></span></div>
                  <div><?= icon('user') ?><span><strong><?= e($ev['pending']) ?></strong><small>Pending</small></span></div>
                </div>
              </td>

              <td>
                <div class="cl-figures">
                  <div><?= icon('bar-chart') ?><span><strong><?= e(CleanupEvent::fillPercent($ev)) ?>%</strong><small>Places filled</small></span></div>
                  <div><?= icon('message-circle') ?><span><strong><?= e($ev['feedback']) ?></strong><small>Feedback</small></span></div>
                </div>
              </td>

              <td>
                <a class="btn btn--outline" href="<?= url('/org/cleanup/' . $ev['id']) ?>">View Details</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

