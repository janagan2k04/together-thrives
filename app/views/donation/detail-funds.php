<?php
/**
 * "Donation - View Details · Funds (Org)" - frame 4 of Donation-Org.svg.
 *
 * The requester's own view of a fund request. Weeks 4-5: a real row.
 *
 * The frame's raised-versus-transferred split is gone. There is no transferred
 * column anywhere in the frozen schema, so week 3 derived it by taking two
 * thirds of the raised figure - fine as a placeholder, but on a real request it
 * would be inventing money movements. The same goes for the High Urgency badge
 * and the organisation notes panel: no columns, so no panels.
 */
require_once APP_PATH . '/views/partials/icons.php';

/** Two letters for the avatar, from whatever the name gives us. */
$initialsOf = static function (?string $name): string {
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last) ?: 'AD';
};


$r         = $request;
$target    = (float) $r['target_amount'];
$collected = (float) $r['collected_amount'];
$remaining = max(0, $target - $collected);
$fill      = $target > 0 ? min(100, (int) round($collected / $target * 100)) : 0;
$donorNo   = (int) $r['donors'];
$avg       = $donorNo > 0 ? (int) round($collected / $donorNo) : 0;
$daysLeft  = (int) $r['days_left'];
$cancelled = $r['deleted_at'] !== null;
?>

<div class="crumbs">
  <a href="<?= url('/my/donations') ?>">Donations</a> ›
  <a href="<?= url('/my/donations?tab=funds') ?>">Fund Requests</a> ›
  <span class="is-current"><?= e($r['title']) ?></span>
</div>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__body">
    <div class="row-between" style="align-items:flex-start">
      <div>
        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:var(--s-2)">
          <h1 style="font-size:var(--fs-2xl);font-weight:700;color:var(--ink)"><?= e($r['title']) ?></h1>
          <span class="don-status don-status--amber">Monetary Funds</span>
          <span class="don-status don-status--<?= e($tones[$r['status']]) ?>">
            <?= e($statuses[$r['status']]) ?>
          </span>
        </div>
        <p class="muted" style="margin-top:6px">
          Request ID <?= e($r['ref']) ?> · <?= e($r['affected_area']) ?> ·
          <?= e(format_date($r['starts_on'])) ?> - <?= e(format_date($r['ends_on'])) ?>
        </p>
      </div>

      <?php if (!$cancelled): ?>
        <a class="btn btn--outline" href="<?= url('/my/donations/' . $r['id'] . '/edit') ?>">Edit Request</a>

        <form method="post" action="<?= url('/my/donations/' . $r['id']) ?>" style="display:inline">
          <?= csrf_field() ?><?= method_field('DELETE') ?>
          <button class="btn btn--danger" data-confirm="Cancel this request? Donors are notified and remaining funds are refunded.">
            Cancel Request
          </button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</section>

<?php if ($cancelled): ?>
  <div class="don-note don-note--amber" style="margin-bottom:var(--s-5)">
    <?= icon('help-circle') ?>
    This request was cancelled. It stays here for your records and is no longer shown to donors.
  </div>
<?php elseif ($r['status'] === MOD_REJECTED && $r['reject_reason']): ?>
  <div class="don-note don-note--red" style="margin-bottom:var(--s-5)">
    <?= icon('help-circle') ?><strong>Not approved:</strong> <?= e($r['reject_reason']) ?>
  </div>
<?php endif; ?>

<div class="split">
  <div class="stack">

    <section class="panel">
      <div class="panel__body">
        <div class="don-raised">
          <b>LKR <?= e(number_format($collected)) ?></b>
          <span>raised of LKR <?= e(number_format($target)) ?> goal</span>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:var(--s-2);margin-top:var(--s-3)">
          <span class="don-status don-status--green"><?= e($fill) ?>% funded</span>
          <span class="don-status don-status--red">
            <?= icon('clock') ?>
            <?= $daysLeft >= 0 ? e($daysLeft) . ' days remaining' : 'Collection closed' ?>
          </span>
        </div>

        <div class="don-progress" style="margin-top:var(--s-4)">
          <div class="don-progress__bar" style="width:<?= e($fill) ?>%"></div>
        </div>

        <dl class="don-figures">
          <div class="don-figure"><dt>Donors</dt><dd><?= e(number_format($donorNo)) ?></dd></div>
          <div class="don-figure"><dt>Avg. donation</dt><dd>LKR <?= e(number_format($avg)) ?></dd></div>
          <div class="don-figure"><dt>Started</dt><dd><?= e(format_date($r['starts_on'])) ?></dd></div>
          <div class="don-figure"><dt>Ends</dt><dd><?= e(format_date($r['ends_on'])) ?></dd></div>
        </dl>
      </div>
    </section>

    <div class="split split--even">
      <section class="panel">
        <div class="panel__head">
          <?= icon('bar-chart') ?><h2>Funding</h2>
          <small>Target and remaining need</small>
        </div>
        <div class="panel__body">
          <div class="don-money">
            <div class="don-money__row"><span>Target amount</span><b>LKR <?= e(number_format($target)) ?></b></div>
            <div class="don-money__row"><span>Raised so far</span><span class="don-up">LKR <?= e(number_format($collected)) ?></span></div>
            <div class="don-money__row"><span>Remaining</span><span class="don-down">LKR <?= e(number_format($remaining)) ?></span></div>
          </div>

          <p class="muted" style="margin-top:var(--s-3);font-size:var(--fs-xs)">
            Funds are released to the organisation only after admin verification.
          </p>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head">
          <?= icon('users') ?><h2>Donor Activity</h2>
          <small>Recent contributions</small>
        </div>
        <div class="panel__body">
        <?php if (!$donors): ?>
          <p class="muted">No donations yet. Gifts appear here as they are made.</p>
        <?php else: ?>
        <div class="don-donors">
          <?php foreach ($donors as $d): ?>
            <div class="don-donor">
              <span class="don-avatar"><?= e($d['donor'] ? $initialsOf($d['donor']) : 'AD') ?></span>
              <span>
                <strong><?= e($d['donor'] ?: 'Anonymous Donor') ?></strong>
                <small><?= e(time_ago($d['created_at'])) ?></small>
              </span>
              <span class="don-donor__amt">
                <b><?= e(format_money((float) $d['amount'])) ?></b>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
          </div>
      </section>
    </div>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><?= icon('file-text') ?><h2>Situation</h2></div>
      <div class="panel__body">
        <div class="don-photo">
          Disaster area photograph
          <span class="don-photo__tag"><?= e($r['district']) ?> District</span>
        </div>
        <p style="margin-top:var(--s-4);font-size:var(--fs-base);color:var(--ink-2)"><?= e($r['description']) ?></p>
        <?php if (!empty($r['disaster_details'])): ?>
          <p style="margin-top:var(--s-3);font-size:var(--fs-base);color:var(--ink-2)"><?= e($r['disaster_details']) ?></p>
        <?php endif; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('clipboard') ?><h2>Progress</h2></div>
      <div class="panel__body">
        <ul class="don-ticks">
          <li><?= icon('check-circle') ?>Request submitted <?= e(format_date($r['created_at'])) ?></li>
          <?php if ($r['reviewed_at']): ?>
            <li><?= icon('check-circle') ?>Admin approval received <?= e(format_date($r['reviewed_at'])) ?></li>
          <?php else: ?>
            <li class="don-tick--pending"><?= icon('clock') ?>Awaiting admin approval</li>
          <?php endif; ?>
          <li class="don-tick--pending"><?= icon('clock') ?>Impact report pending</li>
        </ul>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('megaphone') ?><h2>Closing the request</h2></div>
      <div class="panel__body">
        <p class="muted">
          Publish an impact update once the campaign closes so donors can see the outcome.
        </p>
        <div style="display:flex;gap:var(--s-2);margin-top:var(--s-4)">
        </div>
      </div>
    </section>
  </div>
</div>

