<?php
/**
 * Organiser's view of one campaign — from UI/Blood.
 *
 * The Figma's "Registered Donors" table and "Post-Campaign Impact" panel
 * depend on screening_responses and blood_donation_records, which belong to
 * ScreeningController/CertificateController, not this CRUD — flagged to the
 * team lead. Shown here as empty states until that data genuinely exists.
 */
require_once APP_PATH . '/views/partials/icons.php';

$statuses = \Models\Blood\BloodCampaign::STATUSES;

$badgeClass = match ($row['status']) {
    'published', 'ongoing' => 'badge--success',
    'pending'               => 'badge--pending',
    'rejected', 'cancelled' => 'badge--danger',
    default                 => 'badge--neutral',
};
?>

<div class="page-wrap">

  <a class="blood-back" href="<?= url('/my/blood') ?>"><?= icon('arrow-left') ?>Back to Blood Campaigns</a>

  <div class="page-header">
    <div>
      <h1><?= e($row['title']) ?>
        <span class="badge <?= $badgeClass ?>"><?= e($statuses[$row['status']] ?? $row['status']) ?></span>
      </h1>
      <p class="page-header__sub">
        <?= e($row['venue']) ?> · <?= e($row['city']) ?> ·
        <?= e(format_date($row['campaign_date'])) ?> ·
        <?= e($row['start_time']) ?> – <?= e($row['end_time']) ?>
      </p>
    </div>
    <div class="page-header__spacer"></div>
    <a class="btn btn--outline" href="<?= url('/my/blood/' . $row['id'] . '/edit') ?>">Edit</a>
    <?php if (!in_array($row['status'], ['cancelled', 'completed'], true)): ?>
      <form method="post" action="<?= url('/my/blood/' . $row['id']) ?>" class="inline">
        <?= csrf_field() ?><?= method_field('DELETE') ?>
        <button class="btn btn--danger" type="submit"
                data-confirm="Cancel &quot;<?= e($row['title']) ?>&quot;? Donors will no longer be able to join.">
          Cancel Campaign
        </button>
      </form>
    <?php endif; ?>
  </div>

  <div class="count-grid">
    <div class="count">
      <span class="count__ico count__ico--blue"><?= icon('eye') ?></span>
      <div><strong>–</strong><span>Viewed</span></div>
    </div>
    <div class="count">
      <span class="count__ico count__ico--brand"><?= icon('users') ?></span>
      <div><strong><?= e($row['target_donors']) ?></strong><span>Target Donors</span></div>
    </div>
    <div class="count">
      <span class="count__ico count__ico--green"><?= icon('check-circle') ?></span>
      <div><strong>–</strong><span>Eligible to Donate</span></div>
    </div>
    <div class="count">
      <span class="count__ico count__ico--red"><?= icon('x-circle') ?></span>
      <div><strong>–</strong><span>Not Eligible</span></div>
    </div>
  </div>

  <div class="split">
    <div class="stack">

      <section class="sec">
        <div class="sec__head">
          <div><strong>Campaign Information</strong></div>
        </div>
        <div class="sec__body">
          <p><?= e($row['description']) ?></p>
          <div class="readout-grid readout-grid--3" style="margin-top:var(--s-4)">
            <div class="readout"><span class="readout__label">Units Required</span>
              <span class="readout__value"><?= e($row['target_donors']) ?> donors</span></div>
            <div class="readout"><span class="readout__label">Contact</span>
              <span class="readout__value"><?= e($row['contact_phone']) ?></span></div>
            <div class="readout"><span class="readout__label">Group Chat</span>
              <span class="readout__value"><?= $row['auto_group_chat'] ? 'Auto-created' : 'Not created' ?></span></div>
          </div>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Registered Donors</strong>
            <small>Screening is evaluated automatically when a donor joins.</small></div>
        </div>
        <div class="sec__body">
          <div class="empty-state">
            No donors have registered yet.
          </div>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="sec">
        <div class="sec__head">
          <div><strong>Verification Status</strong></div>
        </div>
        <div class="sec__body">
          <?php if ($row['status'] === 'pending'): ?>
            <p class="muted">Awaiting admin fact-check before it goes live.</p>
          <?php elseif ($row['status'] === 'rejected'): ?>
            <p class="muted">This campaign was rejected by an admin.</p>
          <?php else: ?>
            <p class="muted">Verified &amp; published.</p>
          <?php endif; ?>
        </div>
      </section>

      <section class="sec">
        <div class="sec__head">
          <div><strong>Post-Campaign Impact</strong></div>
        </div>
        <div class="sec__body">
          <div class="empty-state">
            Impact can be published once the campaign has ended.
          </div>
        </div>
      </section>
    </div>
  </div>
</div>