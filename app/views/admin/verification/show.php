<?php
/**
 * One pending application in full, so the administrator can read the submitted
 * details and open the proof document before deciding.
 *
 * There is no frame for this in admin.svg — the design approves straight from
 * the table row. Built anyway: approving an account without being able to see
 * the document it was justified by would make the queue meaningless.
 */
require_once APP_PATH . '/views/partials/icons.php';

$r = $request;

$roleLabels = [
    ROLE_COMMUNITY    => 'Community Member',
    ROLE_ORGANIZATION => 'Organization',
    ROLE_GN           => 'Grama Niladhari',
    ROLE_BLOOD_BANK   => 'Blood Bank',
];

$heading = match ($r['role']) {
    ROLE_ORGANIZATION => $r['org_name'],
    ROLE_BLOOD_BANK   => $r['bank_name'],
    default           => $r['full_name'],
};

// Each role stores its document on a different table.
$document = match ($r['role']) {
    ROLE_ORGANIZATION => $r['org_document'],
    ROLE_GN           => $r['gn_document'],
    ROLE_BLOOD_BANK   => $r['bank_document'],
    default           => $r['nic_document_path'],
};

// dt => dd, skipping anything the applicant left empty.
$details = match ($r['role']) {
    ROLE_ORGANIZATION => [
        'Organization Name'   => $r['org_name'],
        'Registration Number' => $r['reg_number'],
        'Organization Type'   => Models\Organization::TYPES[$r['org_type']] ?? $r['org_type'],
        'Website'             => $r['website'],
        'Contact Email'       => $r['email'],
        'Description'         => $r['description'],
    ],
    ROLE_GN => [
        'Full Name'      => $r['full_name'],
        'NIC Number'     => $r['nic'],
        'GN Division'    => $r['gn_division'],
        'DS Division'    => $r['ds_division'],
        'District'       => $r['district'],
        'Office Phone'   => $r['phone'],
        'Contact Email'  => $r['email'],
    ],
    ROLE_BLOOD_BANK => [
        'Blood Bank Name' => $r['bank_name'],
        'Licence Number'  => $r['license_no'],
        'Hospital'        => $r['hospital_name'],
        'Hotline'         => $r['hotline'],
        'Contact Email'   => $r['email'],
    ],
    default => [
        'Full Name'     => $r['full_name'],
        'Contact Email' => $r['email'],
        'Phone Number'  => $r['phone'],
        'City'          => $r['city'],
    ],
};
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1><?= e($heading ?: $r['full_name']) ?></h1>
    <p>
      <?= e($roleLabels[$r['role']] ?? $r['role']) ?>
      · submitted <?= e(time_ago($r['created_at'])) ?>
      · <span class="pill pill--pending">Pending</span>
    </p>
  </div>
  <a class="btn-mini" href="<?= url('/admin/verification') ?>">
    <?= icon('arrow-left') ?> Back to queue
  </a>
</div>

<div class="detail-grid">
  <div class="detail-card">
    <div class="detail-card__head">
      <?= icon('clipboard') ?>
      <strong>Submitted Details</strong>
    </div>
    <div class="detail-card__body">
      <dl class="field-list">
        <?php foreach ($details as $label => $value): ?>
          <?php if (!$value) continue; ?>
          <div>
            <dt><?= e($label) ?></dt>
            <dd><?= e($value) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </div>
  </div>

  <div>
    <div class="detail-card">
      <div class="detail-card__head">
        <?= icon('file-check') ?>
        <strong>Proof Document</strong>
      </div>
      <div class="detail-card__body">
        <?php if ($document): ?>
          <a class="doc-link" href="<?= url('/admin/verification/' . $r['id'] . '/document') ?>"
             target="_blank" rel="noopener">
            <span class="doc-link__icon"><?= icon('file-text') ?></span>
            <span>
              <strong>Open document</strong>
              <small><?= e(strtoupper(pathinfo($document, PATHINFO_EXTENSION))) ?> · opens in a new tab</small>
            </span>
          </a>
        <?php else: ?>
          <p class="cell-sub">No document was uploaded with this registration.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-card" style="margin-top:var(--s-5)">
      <div class="detail-card__head">
        <?= icon('shield-check') ?>
        <strong>Decision</strong>
      </div>
      <div class="detail-card__body">
        <form method="post" action="<?= url('/admin/verification/' . $r['id'] . '/approve') ?>">
          <?= csrf_field() ?>
          <button class="btn btn--brand" style="width:100%"
                  data-confirm="Approve this account? They will be able to sign in immediately.">
            <?= icon('check') ?> Approve account
          </button>
        </form>

        <form method="post" action="<?= url('/admin/verification/' . $r['id'] . '/reject') ?>"
              class="decision" style="margin-top:var(--s-5)">
          <?= csrf_field() ?>
          <label class="field__label" for="reason">Reason for rejection <span style="color:var(--muted-3);font-weight:400">(sent to the applicant)</span></label>
          <textarea id="reason" name="reason" maxlength="300"
                    placeholder="e.g. The uploaded document is unreadable."></textarea>
          <button class="btn-mini btn-mini--danger" style="align-self:flex-start"
                  data-confirm="Reject this registration? They will not be able to sign in.">
            <?= icon('x-circle') ?> Reject registration
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
