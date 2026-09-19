<?php
/**
 * "Critical Request Details (View Request)" from Blood-User.svg.
 *
 * "I Can Donate" goes to the screening form rather than sharing anything
 * straight away - the whole screen promises the donor that their name and
 * number reach the requester only after they screen eligible.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Blood\ScreeningResponse;

$checks = [
    'Hospital details confirmed',
    'Requester identity verified',
    'Published to donors on the blood donation hub',
];

$myGroup = $user['blood_group'] ?? null;
$matches = $myGroup !== null && $myGroup === $request['group'];
?>

<a class="blood-back" href="<?= url('/blood?tab=critical') ?>"><?= icon('arrow-left') ?>Back to Blood Donation</a>

<div class="blood-head">
  <div class="blood-head__text">
    <h1>Critical Blood Request - <?= e($request['group']) ?></h1>
    <p><?= e($request['hospitalShort']) ?> · Posted <?= e($request['posted']) ?> · Verified by admin</p>
  </div>
</div>

<div class="alert alert--error alert--stack" style="margin-bottom:var(--s-5)">
  <strong><?= e($request['priority']) ?> - needed by <?= e($request['neededBy']) ?></strong>
  <p style="margin-top:3px">
    If you are eligible, your contact details are shared with the requester immediately.
  </p>
</div>

<div class="split">
  <div class="stack">

    <section class="sec">
      <div class="sec__head">
        <div><strong>Blood Requirement</strong><small>What the patient needs</small></div>
      </div>
      <div class="sec__body">
        <div style="display:flex;gap:var(--s-5);align-items:flex-start">
          <div style="flex:none;padding:var(--s-5) var(--s-6);border-radius:var(--r-lg);
                      background:var(--red-tint);text-align:center">
            <span class="group-big"><?= e($request['group']) ?></span>
            <div style="margin-top:6px;font-size:var(--fs-xs);color:var(--red)">Blood Group</div>
          </div>

          <div style="flex:1">
            <div class="readout-grid readout-grid--3">
              <div class="readout"><span class="readout__label">Units needed</span>
                <span class="readout__value"><?= e($request['units']) ?> units</span></div>
              <div class="readout"><span class="readout__label">Priority</span>
                <span class="readout__value"><?= e($request['priority']) ?></span></div>
              <div class="readout"><span class="readout__label">Needed before</span>
                <span class="readout__value"><?= e($request['neededBy']) ?></span></div>
            </div>
            <div class="readout-grid" style="margin-top:var(--s-4)">
              <div class="readout"><span class="readout__label">Reason</span>
                <span class="readout__value"><?= e($request['reason']) ?></span></div>
              <div class="readout"><span class="readout__label">Units fulfilled</span>
                <span class="readout__value"><?= e($request['fulfilled']) ?> of <?= e($request['units']) ?> units</span></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="sec">
      <div class="sec__head">
        <div><strong>Hospital &amp; Contact</strong><small>Where to donate</small></div>
      </div>
      <div class="sec__body">
        <div class="readout-grid">
          <div class="readout"><span class="readout__label">Hospital</span>
            <span class="readout__value"><?= e($request['hospital']) ?></span></div>
          <div class="readout"><span class="readout__label">Ward</span>
            <span class="readout__value"><?= e($request['ward']) ?></span></div>
          <div class="readout"><span class="readout__label">Requested by</span>
            <span class="readout__value"><?= e($request['requester']) ?></span></div>
          <div class="readout"><span class="readout__label">Contact</span>
            <span class="readout__value">Shared after eligibility check</span></div>
        </div>
      </div>
    </section>

    <section class="sec">
      <div class="sec__head">
        <div><strong>Patient Note</strong><small>From the requester</small></div>
      </div>
      <div class="sec__body"><p><?= e($request['note']) ?></p></div>
    </section>
  </div>

  <div class="stack">
    <section class="sec">
      <div class="sec__head">
        <div><strong>Can You Donate?</strong><small>Complete a quick screening</small></div>
      </div>
      <div class="sec__body">
        <div class="mini-list">
          <div>
            <span>Your blood group</span>
            <span style="color:<?= $matches ? 'var(--green-dark)' : 'var(--muted-3)' ?>;font-weight:600">
              <?= $myGroup ? e($myGroup) . ($matches ? ' · Match' : '') : 'Not stated' ?>
            </span>
          </div>
          <div><span>Last donation</span><span>02 Apr 2026</span></div>
          <div><span>Eligible again</span><span style="color:var(--green-dark);font-weight:600">Yes</span></div>
        </div>

        <?php if (!$myGroup): ?>
          <p class="muted" style="margin-top:var(--s-3)">
            Add your blood group on <a href="<?= url('/profile') ?>">your profile</a> and matching
            requests will find you automatically.
          </p>
        <?php endif; ?>

        <form method="post" action="<?= url('/blood/critical/' . $request['id'] . '/respond') ?>"
              style="margin-top:var(--s-4)">
          <?= csrf_field() ?>
          <button class="btn btn--blood btn--block" type="submit">I Can Donate</button>
        </form>

        <p class="muted" style="margin-top:10px">
          You will be asked to complete a medical screening form before your details are shared.
        </p>
      </div>
    </section>

    <section class="sec">
      <div class="sec__head">
        <div><strong>Time Remaining</strong></div>
      </div>
      <div class="sec__body">
        <div class="alert alert--info alert--stack" style="background:var(--amber-tint);border-color:#f6e3ca">
          <strong style="font-size:var(--fs-xl);color:var(--amber-dark)"><?= e($request['remaining']) ?></strong>
          <p style="margin-top:3px">Needed before <?= e($request['neededBy']) ?></p>
        </div>
      </div>
    </section>

    <?php if ($mine): ?>
    <section class="sec">
      <div class="sec__head">
        <div>
          <strong>Donors Who Answered</strong>
          <small>Contact details are shown for donors the screening cleared</small>
        </div>
      </div>
      <div class="sec__body">
        <?php if (!$responders): ?>
          <p class="muted">Nobody has answered yet. Donors appear here as they complete the
             screening form.</p>
        <?php else: ?>
          <div class="mini-list">
            <?php foreach ($responders as $r): ?>
              <div>
                <span>
                  <strong style="color:var(--ink)"><?= e($r['full_name']) ?></strong>
                  <small style="display:block;color:var(--muted-3)">
                    <?= e($r['blood_group'] ?: '-') ?><?= $r['city'] ? ' - ' . e($r['city']) : '' ?>
                  </small>
                </span>
                <span>
                  <?php if ($r['phone']): ?>
                    <strong style="color:var(--green-dark)"><?= e($r['phone']) ?></strong>
                  <?php else: ?>
                    <span class="donor-hidden"><?= e(ScreeningResponse::RESULTS[$r['result']]) ?></span>
                  <?php endif; ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <section class="sec">
      <div class="sec__head">
        <div><strong>Verified Request</strong></div>
      </div>
      <div class="sec__body">
        <p class="muted" style="margin-bottom:var(--s-3)">
          This request was fact-checked by an administrator before being published to donors.
        </p>
        <ul class="tick-list">
          <?php foreach ($checks as $c): ?><li><?= e($c) ?></li><?php endforeach; ?>
        </ul>
      </div>
    </section>
  </div>
</div>

<?php if (!$mine): ?>
<div class="form-foot">
  <p>Your name and phone number are shared with the requester only if you are found eligible.</p>
  <a class="btn btn--outline" href="<?= url('/blood?tab=critical') ?>">Not Now</a>
  <form method="post" action="<?= url('/blood/critical/' . $request['id'] . '/respond') ?>" class="inline">
    <?= csrf_field() ?>
    <button class="btn btn--blood" type="submit">I Can Donate</button>
  </form>
</div>
<?php endif; ?>
