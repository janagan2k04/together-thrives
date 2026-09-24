<?php
/**
 * "Community User Homepage" from Comm_User-Homepage.svg.
 *
 * Every figure and every row here comes from Services\DashboardService, which
 * counts real rows for the signed-in account. It used to be four hard-coded
 * arrays: Keerthy Ganesh's Impact panel read 14 cleanup camps, 23 items
 * donated, 6 blood camps and 8 volunteer hours on an account that had done none
 * of it. With no seed content most tiles read 0, and 0 is the true answer.
 *
 * Volunteer Hours is gone from the strip: `attendance` stores check-in and
 * check-out times and nothing writes them, so the tile could only ever have
 * been invented. Items Listed took its place, which reuse really does count.
 */
require_once APP_PATH . '/views/partials/icons.php';

$statusTone = static fn(string $s): string => match ($s) {
    'selected', 'received', 'published', 'confirmed' => 'success',
    'rejected', 'cancelled'                          => 'danger',
    default                                          => 'pending',
};
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Welcome back, <?= e($user['full_name'] ?? '') ?>!</h1>
    <p>Let's make a positive impact today.</p>
  </div>
  <div class="page-head__actions">
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
        <a class="btn-mini" href="<?= url('/cleanup') ?>">Browse campaigns</a>
      </div>
      <div class="panel__body">
        <?php if (!$joined): ?>
          <div class="empty-state">
            You have not joined a campaign yet. Open Cleanup Campaigns to find one near you.
          </div>
        <?php else: ?>
          <div class="grid grid--3">
            <?php foreach ($joined as $c): ?>
              <article class="card">
                <span class="badge badge--<?= e($statusTone($c['status'])) ?>"><?= e(ucfirst($c['status'])) ?></span>
                <h3 style="margin-top:var(--s-3);font-size:var(--fs-base);font-weight:700">
                  <a href="<?= url('/cleanup/' . $c['id']) ?>"><?= e($c['title']) ?></a>
                </h3>
                <p class="muted" style="margin-top:2px">By <?= e($c['by_whom']) ?></p>
                <p class="muted" style="margin-top:6px;font-size:var(--fs-xs)">
                  <?= e(format_date($c['on_date'])) ?>
                </p>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head">
        <h2>Your Requests</h2>
        <a class="btn-mini" href="<?= url('/my/activities') ?>">View all</a>
      </div>
      <div class="panel__body">
        <?php if (!$requests): ?>
          <div class="empty-state">You have not asked for anything yet.</div>
        <?php else: ?>
          <div class="grid grid--2">
            <?php foreach ($requests as $q): ?>
              <article class="card">
                <span class="badge badge--<?= e($statusTone($q['status'])) ?>"><?= e(ucfirst($q['status'])) ?></span>
                <h3 style="margin-top:var(--s-3);font-size:var(--fs-base);font-weight:700"><?= e($q['title']) ?></h3>
                <p class="muted" style="margin-top:2px"><?= e($q['category']) ?></p>
                <p class="muted" style="margin-top:6px;font-size:var(--fs-xs)">
                  Reuse request · <?= e(time_ago($q['created_at'])) ?>
                </p>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><h2 style="color:var(--red-dark)">Critical Alerts</h2></div>
      <div class="panel__body stack">
        <?php if (!$alerts): ?>
          <div class="empty-state">No alerts for your district.</div>
        <?php else: ?>
          <?php foreach ($alerts as [$level, $title, $body, $when]): ?>
            <div class="card" style="background:var(--surface-2)">
              <span class="badge badge--<?= $level === 'High' ? 'danger' : 'pending' ?>"><?= e($level) ?></span>
              <strong style="display:block;margin-top:8px;font-size:var(--fs-sm)"><?= e($title) ?></strong>
              <p class="muted" style="margin-top:4px"><?= e($body) ?></p>
              <small style="color:var(--muted-3);font-size:var(--fs-xs)"><?= e($when) ?></small>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><h2>Upcoming &amp; Active Nearby</h2></div>
      <div class="panel__body stack">
        <?php if (!$nearby): ?>
          <div class="empty-state">Nothing coming up in your district.</div>
        <?php else: ?>
          <?php foreach ($nearby as $n): ?>
            <div class="row-between">
              <div>
                <small class="muted" style="font-size:var(--fs-xs)"><?= e($n['kind']) ?></small>
                <strong style="display:block;font-size:var(--fs-sm)"><?= e($n['title']) ?></strong>
                <small class="muted" style="font-size:var(--fs-xs)"><?= e(format_date($n['on_date'])) ?></small>
              </div>
              <a class="btn-mini btn-mini--approve" href="<?= url($n['link']) ?>">Open</a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>
