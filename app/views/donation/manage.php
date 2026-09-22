<?php
/**
 * "Donation Requests (Org)" - frame 1 of Donation-Org.svg.
 *
 * Weeks 4-5: the requester's own real rows, filtered by requester_id.
 *
 * Shared by organisations, Grama Niladharis and administrators, because
 * `donation_requests.requester_type` is an ENUM of exactly those three and all
 * three sidebars link here. The screen is the same for each; it lists the rows
 * the signed-in account posted and nobody else's.
 *
 * The frame's Location filter is gone: this list is one requester's own
 * requests, and filtering a handful of rows by city filters nothing useful.
 * Status is the filter that earns its place, so it is the one kept.
 */
require_once APP_PATH . '/views/partials/icons.php';

$tabs = [
    'all'   => 'All Requests',
    'items' => 'Item Requests',
    'funds' => 'Fund Requests',
];
?>

<div class="don-head">
  <div class="don-head__text">
    <h1>Donation Requests</h1>
    <p>Manage all your donation requests for disaster relief and recovery.</p>
  </div>
  <div class="don-head__actions">
    <a class="btn btn--accent" href="<?= url('/my/donations/create') ?>">Create Donation Request</a>
  </div>
</div>

<nav class="don-tabs">
  <?php foreach ($tabs as $key => $label): ?>
    <a class="<?= $tab === $key ? 'is-active' : '' ?>"
       href="<?= url('/my/donations' . ($key === 'all' ? '' : '?tab=' . $key)) ?>">
      <?= e($label) ?> (<?= e($typeCounts[$key]) ?>)
    </a>
  <?php endforeach; ?>
</nav>

<form class="don-filters" method="get" action="<?= url('/my/donations') ?>">
  <input type="hidden" name="tab" value="<?= e($tab) ?>">

  <select name="status" onchange="this.form.submit()">
    <option value="">All Status (<?= e($typeCounts['all']) ?>)</option>
    <?php foreach ($statuses as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>>
        <?= e($label) ?> (<?= e($counts[$key]) ?>)
      </option>
    <?php endforeach; ?>
  </select>

  <span class="don-filters__spacer"></span>
  <span class="muted">Showing <?= count($requests) ?> of <?= e($typeCounts['all']) ?></span>
</form>

<?php if (!$requests): ?>
  <div class="panel"><div class="panel__body empty-state">
    <p>
      <?= $status !== '' || $tab !== 'all'
            ? 'No requests match this filter yet.'
            : 'You have not created a donation request yet.' ?>
    </p>
    <a class="btn btn--accent" style="margin-top:var(--s-4)" href="<?= url('/my/donations/create') ?>">
      Create your first request
    </a>
  </div></div>
<?php else: ?>
  <div class="don-list">
    <?php foreach ($requests as $r): ?>
      <?php
      $isItems   = $r['request_type'] === 'items';
      $items     = $itemsBy[(int) $r['id']] ?? [];
      $needed    = array_sum(array_column($items, 'qty_required'));
      $got       = array_sum(array_column($items, 'qty_received'));
      $cats      = array_values(array_unique(array_column($items, 'item_name')));
      $cancelled = $r['deleted_at'] !== null;
      ?>
      <article class="don-card" style="grid-template-columns:110px minmax(0,1fr) 170px 190px 150px">
        <div class="don-card__thumb" style="height:110px"></div>

        <div class="don-card__body">
          <span class="don-status don-status--<?= e($tones[$r['status']]) ?>">
            <?= e($statuses[$r['status']]) ?>
          </span>
          <h3 style="margin-top:8px">
            <a href="<?= url('/my/donations/' . $r['id']) ?>"><?= e($r['title']) ?></a>
          </h3>
          <div class="don-card__where"><?= icon('map-pin') ?><?= e($r['district']) ?> District</div>
          <p class="don-card__text"><?= e(str_limit($r['description'], 90)) ?></p>
          <div class="don-card__where" style="margin-top:8px">
            <?= icon('calendar') ?>Requested on <?= e(format_date($r['created_at'])) ?>
          </div>
          <div class="don-card__where">
            <?= icon('calendar') ?>Collection: <?= e(format_date($r['starts_on'])) ?> - <?= e(format_date($r['ends_on'])) ?>
          </div>
        </div>

        <div>
          <dt style="font-size:var(--fs-xs);font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--muted-3)">Type</dt>
          <dd style="margin-top:4px;font-size:var(--fs-base);color:var(--ink)"><?= $isItems ? 'Items' : 'Funds' ?></dd>

          <?php if ($cats): ?>
            <dt style="margin-top:var(--s-3);font-size:var(--fs-xs);font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--muted-3)">Categories</dt>
            <dd style="margin-top:6px">
              <div class="don-cats">
                <?php foreach (array_slice($cats, 0, 2) as $c): ?>
                  <span class="don-cat"><?= e($c) ?></span>
                <?php endforeach; ?>
                <?php if (count($cats) > 2): ?>
                  <span class="don-cat don-cat--more">+<?= count($cats) - 2 ?> more</span>
                <?php endif; ?>
              </div>
            </dd>
          <?php endif; ?>
        </div>

        <div class="don-card__side" style="align-items:flex-start;text-align:left">
          <span class="don-side-label"><?= icon('bar-chart') ?>Progress</span>
          <span class="don-card__pct"><?= e($r['progress']) ?>%</span>
          <span class="don-card__of">
            <?php if ($isItems): ?>
              <?= e($got) ?> / <?= e($needed) ?><small>Items received</small>
            <?php else: ?>
              Rs. <?= e(number_format((float) $r['collected_amount'])) ?>
              / Rs. <?= e(number_format((float) $r['target_amount'])) ?>
              <small>Funds received</small>
            <?php endif; ?>
          </span>
          <?php if ($r['status'] === REQUEST_OPEN && (int) $r['days_left'] >= 0): ?>
            <span class="don-card__ends">
              <?= icon('clock') ?>
              <?= (int) $r['days_left'] === 0 ? 'Ends today' : 'Ends in ' . e($r['days_left']) . ' days' ?>
            </span>
          <?php endif; ?>
        </div>

        <div style="display:grid;align-content:center;gap:var(--s-2)">
          <a class="btn btn--outline" href="<?= url('/my/donations/' . $r['id']) ?>">View Details</a>
          <?php if (!$cancelled): ?>
            <a class="btn btn--ghost" href="<?= url('/my/donations/' . $r['id'] . '/edit') ?>">Edit</a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

