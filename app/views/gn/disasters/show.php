<?php
/** "GN - Disaster Report Details" — description, evidence, timeline, and the side rail. */
require_once APP_PATH . '/views/partials/icons.php';

$verified = $report['status'] === 'Verified';
$pending  = $report['status'] === 'Pending Verification';

$evidence = ['Main road', 'Temple hall', 'Riverbank', 'Water level'];
?>

<a class="back-link" href="<?= url('/gn/disasters') ?>"><?= icon('arrow-left') ?>Back to Reported Disasters</a>

<div class="page-head">
  <div class="page-head__text">
    <h1>
      <?= e($report['title']) ?>
      <span class="pill pill--<?= $verified ? 'active' : ($pending ? 'pending' : 'rejected') ?>"><?= e($report['status']) ?></span>
      <span class="pill pill--<?= $report['severity'] === 'High' ? 'rejected' : 'neutral' ?>"><?= e($report['severity']) ?> Severity</span>
    </h1>
    <p>
      Report ID <?= e($report['id']) ?> ·
      <?= e($report['place']) ?>, <?= e($report['district']) ?> ·
      Reported <?= e($report['on']) ?> at <?= e($report['at']) ?>
    </p>
  </div>
</div>

<div class="split">
  <div class="stack">
    <section class="panel">
      <div class="panel__head">
        <?= icon('file-text') ?>
        <h2>Disaster Description</h2>
        <small>Submitted by the reporting officer</small>
      </div>
      <div class="panel__body">
        <p style="font-size:var(--fs-base);color:var(--ink-2)"><?= e($report['body']) ?></p>

        <dl class="kv" style="margin-top:var(--s-5)">
          <div><dt>Disaster type</dt><dd><?= e($report['type']) ?></dd></div>
          <div><dt>Occurred on</dt><dd><?= e($report['on']) ?>, <?= e($report['at']) ?></dd></div>
          <div><dt>Affected families</dt><dd><?= e($report['families']) ?></dd></div>
          <div><dt>People displaced</dt><dd style="color:var(--red)"><?= e($report['displaced']) ?></dd></div>
        </dl>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head">
        <?= icon('eye') ?>
        <h2>Evidence</h2>
        <small><?= e(count($evidence)) ?> photographs attached</small>
      </div>
      <div class="panel__body">
        <div class="grid grid--4">
          <?php foreach ($evidence as $i => $label): ?>
            <div class="card" style="background:var(--surface-2);min-height:120px;display:flex;flex-direction:column;justify-content:flex-end">
              <strong style="font-size:var(--fs-sm)"><?= e($label) ?></strong>
              <small class="muted" style="font-size:var(--fs-xs)">flood-0<?= e($i + 1) ?>.jpg</small>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="muted" style="margin-top:var(--s-4)">
          Photographs are geo-checked against the reported GN division during verification.
        </p>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('clipboard') ?><h2>Verification Timeline</h2></div>
      <div class="panel__body">
        <ul class="timeline">
          <li class="is-done">
            <strong>Report submitted</strong><small><?= e($report['on']) ?> · <?= e($report['at']) ?></small>
            <p>Submitted by your division with <?= e(count($evidence)) ?> evidence photos.</p>
          </li>
          <li class="<?= $pending ? 'is-current' : 'is-done' ?>">
            <strong>Evidence check</strong><small><?= $pending ? 'In progress' : 'Complete' ?></small>
            <p>An administrator reviews the attached evidence and severity.</p>
          </li>
          <li class="<?= $verified ? 'is-done' : '' ?>">
            <strong>Decision</strong><small><?= $verified ? 'Approved' : ($pending ? 'Pending' : 'Rejected') ?></small>
            <p>Once approved the report is published and response actions unlock.</p>
          </li>
        </ul>
      </div>
    </section>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><?= icon('clock') ?><h2>Verification Status</h2></div>
      <div class="panel__body">
        <div class="alert alert--<?= $verified ? 'success' : ($pending ? 'info' : 'error') ?>" style="display:block">
          <strong><?= e($report['status']) ?></strong>
          <p style="margin-top:4px">
            <?php if ($pending): ?>
              An administrator is reviewing your report. You will be notified once it is
              approved or rejected.
            <?php elseif ($verified): ?>
              This report is published. Response actions below are unlocked.
            <?php else: ?>
              This report was not approved. Raise a new one with clearer evidence if the
              situation still stands.
            <?php endif; ?>
          </p>
        </div>
        <dl class="kv kv--single" style="margin-top:var(--s-4)">
          <div><dt>Submitted on</dt><dd><?= e($report['on']) ?></dd></div>
        </dl>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('map-pin') ?><h2>Location</h2></div>
      <div class="panel__body">
        <dl class="kv kv--single">
          <div><dt>District</dt><dd><?= e($report['district']) ?></dd></div>
          <div><dt>Area</dt><dd><?= e($report['place']) ?></dd></div>
          <div><dt>Landmark</dt><dd><?= e($report['landmark']) ?></dd></div>
        </dl>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('leaf') ?><h2>Response Actions</h2>
        <small><?= $verified ? 'Unlocked' : 'Unlocked after verification' ?></small></div>
      <div class="panel__body stack">
        <?php if (!$verified): ?>
          <p class="muted">
            Donation requests and cleanup campaigns can only be created from a verified report.
          </p>
        <?php endif; ?>
        <a class="btn btn--primary btn--block <?= $verified ? '' : 'is-disabled' ?>"
           href="<?= url($verified ? '/my/donations/create' : '/gn/disasters/' . $report['id']) ?>">
          Create Donation Request
        </a>
        <a class="btn btn--outline btn--block" href="<?= url('/cleanup') ?>">Request Cleanup Campaign</a>
      </div>
    </section>

    <?php if ($pending): ?>
      <section class="panel panel--warn">
        <div class="panel__body row-between">
          <p class="muted">A report can be edited or withdrawn only while it is still pending.</p>
          <button class="btn btn--danger" type="button" disabled>Withdraw</button>
        </div>
      </section>
    <?php endif; ?>
  </div>
</div>
