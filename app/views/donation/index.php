<?php
/**
 * "Donation" - frame 1 of Donation-User.svg. The community browse screen.
 *
 * Weeks 4-5: real `donation_requests` rows, approved ones only.
 *
 * The four tabs and the filter dropdowns the frame draws are query strings, so
 * every tab is a shareable URL and the back button behaves. Open to guests,
 * which is why the card call to action drops to Sign in when logged out.
 *
 * The frame's "Any Urgency" filter is gone: there is no urgency column anywhere
 * in the frozen schema, so it could only ever have filtered on nothing.
 */
require_once APP_PATH . '/views/partials/icons.php';

$tabs = [
    'all'   => 'All Requests',
    'items' => 'Item Requests',
    'funds' => 'Fund Requests',
    'mine'  => 'My Donations',
];

/** Only water has its own glyph in the icon set; the rest get a neutral box. */
$itemIcon = static fn(string $name): string => $name === 'Water & Drinks' ? 'droplet' : 'inbox';
?>

<div class="don-page">

<div class="don-head">
  <span class="don-head__icon"><?= icon('gift') ?></span>
  <div class="don-head__text">
    <h1>Donation Requests</h1>
    <p>Help disaster affected communities by donating items or funds.</p>
  </div>
</div>

<?php if ($impact): ?>
  <?php
  /*
   * Every tile is counted against the signed-in account, so these read 0 until
   * the donor has actually given something. The Figma's "Lives Impacted" is not
   * here: how many people a gift reaches is not a number this platform can
   * count, and "Requests Supported" is.
   */
  $tones = ['green' => 'green', 'blue' => 'brand', 'amber' => 'amber'];
  $icons = ['green' => 'inbox', 'blue' => 'gift', 'amber' => 'users'];
  ?>
  <section class="don-impact">
    <div class="don-impact__title">Your Impact</div>
    <div class="don-impact__row">
      <?php foreach ($impact as [$value, $label, $tone]): ?>
        <div class="don-impact__tile don-impact__tile--<?= e($tones[$tone] ?? 'green') ?>">
          <?= icon($icons[$tone] ?? 'gift') ?>
          <span><strong><?= e($value) ?></strong><small><?= e($label) ?></small></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<nav class="don-tabs">
  <?php foreach ($tabs as $key => $label): ?>
    <?php if ($key === 'mine' && !is_logged_in()) continue; ?>
    <a class="<?= $tab === $key ? 'is-active' : '' ?>"
       href="<?= url('/donations' . ($key === 'all' ? '' : '?tab=' . $key)) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($tab === 'mine'): ?>
  <?php /* The donor's own giving - `item_reservations` and `fund_donations`
           merged into one list, newest first, because a donor thinks of the two
           as one thing they did. */ ?>
  <?php if (!$myGiving): ?>
    <div class="panel">
      <div class="panel__body empty-state">
        <p>You have not donated to a request yet.</p>
        <a class="btn btn--primary" href="<?= url('/donations') ?>">Browse requests</a>
      </div>
    </div>
  <?php else: ?>
    <div class="don-list">
      <?php foreach ($myGiving as $g): ?>
        <article class="don-card">
          <div class="don-card__thumb">
            <span class="don-card__tag don-card__tag--<?= e($g['kind']) ?>">
              <?= $g['kind'] === 'items' ? 'Item Request' : 'Fund Request' ?>
            </span>
          </div>
          <div class="don-card__body">
            <h3><a href="<?= url('/donations/' . $g['request_id']) ?>"><?= e($g['title']) ?></a></h3>
            <div class="don-card__by">By <?= e($g['requester']) ?></div>
            <p class="don-card__text">
              You gave <strong><?= e($g['what']) ?></strong> on <?= e(format_date($g['at'])) ?>.
            </p>

            <div class="don-card__meta">
              <span class="badge badge--<?= e($g['tone']) ?>"><?= e($g['status']) ?></span>

              <?php /* Undo, for an item promise the requester has not confirmed
                        yet. A paid fund donation has no undo - the money has
                        left - so no button is drawn for one. */ ?>
              <?php if ($g['kind'] === 'items' && $g['raw_status'] === 'reserved'): ?>
                <form method="post" class="inline"
                      action="<?= url('/donations/' . $g['request_id'] . '/reservations/' . $g['id'] . '/cancel') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn--outline btn--sm"
                          data-confirm="Withdraw this reservation? The quantity goes back on the request.">
                    Undo
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php else: ?>

<form class="don-filters" method="get" action="<?= url('/donations') ?>">
  <input type="hidden" name="tab" value="<?= e($tab) ?>">

  <select name="city" onchange="this.form.submit()">
    <option value="">All Locations</option>
    <?php foreach ($cities as $c): ?>
      <option value="<?= e($c['id']) ?>" <?= $filters['city'] === (string) $c['id'] ? 'selected' : '' ?>>
        <?= e($c['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="status" onchange="this.form.submit()">
    <option value="">Any Status</option>
    <?php foreach ($statuses as $key => $label): ?>
      <?php if (!in_array($key, \Models\Donation\DonationRequest::PUBLIC_STATUSES, true)) continue; ?>
      <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>

  <span class="don-filters__spacer"></span>
  <span class="muted"><?= e($total) ?> request<?= (int) $total === 1 ? '' : 's' ?></span>
</form>

<?php if (!$requests): ?>
  <div class="panel"><div class="panel__body empty-state">
    <p>No approved donation requests match this filter yet.</p>
    <p class="muted">
      Requests appear here once an organisation or Grama Niladhari posts one and an
      administrator approves it.
    </p>
  </div></div>
<?php else: ?>
  <div class="don-list">
    <?php foreach ($requests as $r): ?>
      <?php
      $isItems = $r['request_type'] === 'items';
      $items   = $itemsBy[(int) $r['id']] ?? [];
      ?>
      <article class="don-card">
        <div class="don-card__thumb">
          <span class="don-card__tag don-card__tag--<?= e($r['request_type']) ?>">
            <?= $isItems ? 'Item Request' : 'Fund Request' ?>
          </span>
        </div>

        <div class="don-card__body">
          <h3><a href="<?= url('/donations/' . $r['id']) ?>"><?= e($r['title']) ?></a></h3>

          <div class="don-card__by">
            By <?= e($r['requester']) ?>
            <?php if ($r['verified']): ?><?= icon('check-circle') ?><?php endif; ?>
          </div>
          <div class="don-card__where"><?= icon('map-pin') ?><?= e($r['district']) ?> District</div>

          <p class="don-card__text"><?= e($r['description']) ?></p>

          <?php /* Status and the closing date used to sit in a right-hand
                    progress column. The column came off on 2026-09-13 - a
                    percentage complete is a claim the card does not need to make
                    - and these two, which are facts about the request, moved
                    here. */ ?>
          <div class="don-card__meta">
            <?php if ($r['status'] === REQUEST_OPEN && (int) $r['days_left'] >= 0): ?>
              <span class="don-card__ends">
                <?= icon('clock') ?>
                <?= (int) $r['days_left'] === 0 ? 'Ends today' : 'Ends in ' . e($r['days_left']) . ' days' ?>
              </span>
            <?php else: ?>
              <span class="don-status don-status--<?= e($tones[$r['status']]) ?>">
                <?= e($statuses[$r['status']]) ?>
              </span>
            <?php endif; ?>
          </div>

          <?php if ($isItems && $items): ?>
            <div class="don-chips">
              <?php foreach (array_slice($items, 0, 3) as $it): ?>
                <span class="don-chip">
                  <?= icon($itemIcon($it['item_name'])) ?>
                  <span>
                    <strong><?= e($it['item_name']) ?></strong>
                    <small><?= e($it['qty_required']) ?> <?= e($it['unit']) ?></small>
                  </span>
                </span>
              <?php endforeach; ?>
              <?php if (count($items) > 3): ?>
                <span class="don-chip don-chip--more">+<?= count($items) - 3 ?><br>more</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>


<?php endif; ?>
</div>
