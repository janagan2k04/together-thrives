<?php
/**
 * "GN - Disaster Report Details" - description, timeline, and the side rail.
 *
 * Real `disaster_reports` row since 2026-08-27. The Evidence panel is gone with
 * the sample data: `disaster_report_photos` exists but the create form's upload
 * has no writer, so the panel could only ever have listed four filenames that
 * were not there.
 *
 * Affected families and people displaced went the same way - the Figma asks for
 * both and the frozen table has no column for either, so they are collected on
 * the form and not claimed back here.
 */
require_once APP_PATH . '/views/partials/icons.php';

$verified = $report['status'] === MOD_APPROVED;
$pending  = $report['status'] === MOD_PENDING;
$label    = $statuses[$report['status']] ?? ucfirst($report['status']);
?>

<a class="back-link" href="<?= url('/gn/disasters') ?>"><?= icon('arrow-left') ?>Back to Reported Disasters</a>

<div class="page-head">
  <div class="page-head__text">
    <h1>
      <?= e($report['title']) ?>
      <span class="pill pill--<?= $verified ? 'active' : ($pending ? 'pending' : 'rejected') ?>"><?= e($label) ?></span>
      <span class="pill pill--<?= in_array($report['severity'], ['high', 'critical'], true) ? 'rejected' : 'neutral' ?>">
        <?= e($severities[$report['severity']] ?? ucfirst($report['severity'])) ?> Severity
      </span>
    </h1>
    <p>
      Report DR-<?= e(str_pad((string) $report['id'], 4, '0', STR_PAD_LEFT)) ?> ·
      <?= e($report['location']) ?>, <?= e($report['district']) ?> ·
      Reported <?= e(format_date($report['created_at'])) ?>
    </p>
  </div>
</div>

<div class="split">
  <div class="stack">
    <section class="panel">
      <div class="panel__head">
        <?= icon('file-text') ?>
        <h2>Disaster Description</h2>
        <small>Submitted by <?= e($report['reporter']) ?></small>
      </div>
      <div class="panel__body">
        <p style="font-size:var(--fs-base);color:var(--ink-2)"><?= e($report['description']) ?></p>

        <dl class="kv" style="margin-top:var(--s-5)">
          <div><dt>Disaster type</dt><dd><?= e($types[$report['disaster_type']] ?? $report['disaster_type']) ?></dd></div>
          <div><dt>Reported on</dt><dd><?= e(format_date($report['created_at'])) ?></dd></div>
        </dl>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('clipboard') ?><h2>Verification Timeline</h2></div>
      <div class="panel__body">
        <ul class="timeline">
          <li class="is-done">
            <strong>Report submitted</strong><small><?= e(format_date($report['created_at'])) ?></small>
            <p>Filed by your division.</p>
          </li>
          <li class="<?= $pending ? 'is-current' : 'is-done' ?>">
            <strong>Evidence check</strong><small><?= $pending ? 'In progress' : 'Complete' ?></small>
            <p>An administrator reviews the description and severity.</p>
          </li>
          <li class="<?= $verified ? 'is-done' : '' ?>">
            <strong>Decision</strong>
            <small><?= $verified ? 'Approved' : ($pending ? 'Pending' : 'Rejected') ?></small>
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
          <strong><?= e($label) ?></strong>
          <p style="margin-top:4px">
            <?php if ($pending): ?>
              An administrator is reviewing your report. You will be notified once it is
              approved or rejected.
            <?php elseif ($verified): ?>
              This report is published. Response actions below are unlocked.
            <?php else: ?>
              This report was not approved. Raise a new one with clearer detail if the
              situation still stands.
            <?php endif; ?>
          </p>
        </div>
        <dl class="kv kv--single" style="margin-top:var(--s-4)">
          <div><dt>Submitted on</dt><dd><?= e(format_date($report['created_at'])) ?></dd></div>
          <?php if ($report['reviewed_at']): ?>
            <div><dt>Decided on</dt><dd><?= e(format_date($report['reviewed_at'])) ?></dd></div>
          <?php endif; ?>
          <?php if ($report['reject_reason']): ?>
            <div><dt>Reason</dt><dd><?= e($report['reject_reason']) ?></dd></div>
          <?php endif; ?>
        </dl>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('map-pin') ?><h2>Location</h2></div>
      <div class="panel__body">
        <dl class="kv kv--single">
          <div><dt>District</dt><dd><?= e($report['district']) ?></dd></div>
          <div><dt>Area</dt><dd><?= e($report['location']) ?></dd></div>
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
        <a class="btn btn--outline btn--block <?= $verified ? '' : 'is-disabled' ?>"
           href="<?= url($verified ? '/org/cleanup/create' : '/gn/disasters/' . $report['id']) ?>">
          Create Cleanup Campaign
        </a>
      </div>
    </section>
  </div>
</div>
