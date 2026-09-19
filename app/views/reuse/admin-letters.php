<?php
/**
 * Administrator review of Grama Niladhari letters.
 *
 * No frame in admin.svg covers this, so it follows the sibling screen -
 * views/admin/verification/index.php - down to the filter control, because an
 * administrator moving between the two should not have to learn a second table.
 */
require_once APP_PATH . '/views/partials/icons.php';

$tabs = [
    MOD_PENDING  => 'Awaiting review',
    MOD_APPROVED => 'Verified',
    MOD_REJECTED => 'Rejected',
];

$pillFor = [
    MOD_PENDING  => 'pill--pending',
    MOD_APPROVED => 'pill--active',
    MOD_REJECTED => 'pill--rejected',
];
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>GS Letter Review</h1>
    <p>
      Grama Niladhari letters attached to reuse requests. A verified letter gives
      that request priority in the donor's queue, so it is worth opening the file
      before deciding.
    </p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/reuse/letters') ?>">
  <select name="status" onchange="this.form.submit()">
    <?php foreach ($tabs as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>>
        <?= e($label) ?><?= $value === MOD_PENDING && $pending > 0 ? ' (' . (int) $pending . ')' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<div class="data-card">
  <?php if (!$letters): ?>
    <div class="admin-empty">
      <span class="admin-empty__icon"><?= icon('check-circle') ?></span>
      <?php if ($status === MOD_PENDING): ?>
        <strong>No letters waiting for review</strong>
        <p>New ones appear here when a requester claims to be disaster-affected.</p>
      <?php else: ?>
        <strong>Nothing in this state</strong>
        <p>No letter has been <?= e($tabs[$status]) ?> yet.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Requester</th>
          <th>Requested Item</th>
          <th>Uploaded</th>
          <th>Letter</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($letters as $l): ?>
          <tr>
            <td>
              <div class="cell-title"><?= e($l['full_name']) ?></div>
              <div class="cell-sub">
                <?= e($l['city'] ?: 'City not recorded') ?>
                <?= $l['nic'] ? ' · NIC ' . e($l['nic']) : '' ?>
              </div>
            </td>
            <td>
              <?php if ($l['item_id']): ?>
                <div class="cell-title">
                  <a href="<?= url('/reuse/' . $l['item_id']) ?>"><?= e($l['item_title']) ?></a>
                </div>
                <div class="cell-sub">Listed by <?= e($l['donor_name']) ?></div>
              <?php else: ?>
                <?php /* A letter outlives the request it came with - that
                         request may have been withdrawn since. */ ?>
                <div class="cell-sub">Request no longer active</div>
              <?php endif; ?>
            </td>
            <td><?= format_date($l['created_at']) ?></td>
            <td>
              <a class="btn-mini btn-mini--view"
                 href="<?= url('/admin/reuse/letters/' . $l['id'] . '/file') ?>" target="_blank">
                <?= icon('eye') ?> Open
              </a>
            </td>
            <td>
              <?php if ($l['status'] === MOD_PENDING): ?>
                <div class="cell-actions">
                  <form method="post" action="<?= url('/admin/reuse/letters/' . $l['id'] . '/approve') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--approve"
                            data-confirm="Verify <?= e($l['full_name']) ?> as disaster-affected? Their requests will be prioritised for the next <?= (int) \Services\ItemRequestService::LETTER_VALID_MONTHS ?> months.">
                      Verify
                    </button>
                  </form>
                  <form method="post" action="<?= url('/admin/reuse/letters/' . $l['id'] . '/reject') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--danger"
                            data-confirm="Reject this letter? <?= e($l['full_name']) ?> will be told it could not be verified.">
                      Reject
                    </button>
                  </form>
                </div>
              <?php else: ?>
                <span class="pill <?= e($pillFor[$l['status']] ?? 'pill--neutral') ?>">
                  <?= e($tabs[$l['status']] ?? $l['status']) ?>
                </span>
                <?php if ($l['valid_until']): ?>
                  <div class="cell-sub">Valid until <?= format_date($l['valid_until']) ?></div>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
