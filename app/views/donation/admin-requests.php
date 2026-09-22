<?php
/**
 * Administrator review of donation requests.
 *
 * No frame in admin.svg covers this, so it follows its sibling screens -
 * views/admin/verification/index.php, views/reuse/admin-letters.php and
 * views/blood/admin-campaigns.php - down to the filter control, because an
 * administrator moving between them should not have to learn a fourth table.
 *
 * Rejecting asks for a reason inline, because `reject_reason` is a real column
 * and the requester's own detail screen prints it back to them.
 */
require_once APP_PATH . '/views/partials/icons.php';

$tabs = [
    REQUEST_PENDING => 'Awaiting review',
    REQUEST_OPEN    => 'Approved',
    MOD_REJECTED    => 'Rejected',
];

$pillFor = [
    REQUEST_PENDING => 'pill--pending',
    REQUEST_OPEN    => 'pill--active',
    MOD_REJECTED    => 'pill--rejected',
];
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Donation Request Review</h1>
    <p>
      Appeals posted by organisations and Grama Niladharis. Donors are only shown
      a request once it is approved here, so check the affected area, the dates
      and what is actually being asked for before letting one through.
    </p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/donations') ?>">
  <select name="status" onchange="this.form.submit()">
    <?php foreach ($tabs as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>>
        <?= e($label) ?><?= $value === REQUEST_PENDING && $pending > 0 ? ' (' . (int) $pending . ')' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<div class="data-card">
  <?php if (!$requests): ?>
    <div class="admin-empty">
      <span class="admin-empty__icon"><?= icon('check-circle') ?></span>
      <?php if ($status === REQUEST_PENDING): ?>
        <strong>No requests waiting for review</strong>
        <p>New ones appear here when an organisation or Grama Niladhari posts an appeal.</p>
      <?php else: ?>
        <strong>Nothing in this state</strong>
        <p>No request has been <?= e(strtolower($tabs[$status])) ?> yet.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Request</th>
          <th>Requester</th>
          <th>Asking for</th>
          <th>Collection</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <?php $items = $itemsBy[(int) $r['id']] ?? []; ?>
          <tr>
            <td>
              <div class="cell-title"><?= e($r['title']) ?></div>
              <div class="cell-sub"><?= e($r['ref']) ?> · <?= e($r['affected_area']) ?>, <?= e($r['city']) ?></div>
              <div class="cell-sub"><?= e(str_limit($r['description'], 90)) ?></div>
            </td>
            <td>
              <div class="cell-title"><?= e($r['requester']) ?></div>
              <div class="cell-sub"><?= e($r['requester_email']) ?></div>
            </td>
            <td>
              <?php if ($r['request_type'] === 'funds'): ?>
                <div class="cell-title">LKR <?= e(number_format((float) $r['target_amount'])) ?></div>
                <div class="cell-sub">Monetary funds</div>
              <?php else: ?>
                <div class="cell-title"><?= count($items) ?> item line<?= count($items) === 1 ? '' : 's' ?></div>
                <div class="cell-sub">
                  <?= e(implode(', ', array_slice(array_column($items, 'item_name'), 0, 3))) ?>
                </div>
                <div class="cell-sub">Pickup: <?= e($r['pickup_point'] ?: 'Not set') ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="cell-title"><?= e(format_date($r['starts_on'])) ?></div>
              <div class="cell-sub">to <?= e(format_date($r['ends_on'])) ?></div>
              <div class="cell-sub">Posted <?= e(format_date($r['created_at'])) ?></div>
            </td>
            <td>
              <?php if ($r['status'] === REQUEST_PENDING): ?>
                <div class="cell-actions">
                  <form method="post" action="<?= url('/admin/donations/' . $r['id'] . '/approve') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--approve"
                            data-confirm="Approve &quot;<?= e($r['title']) ?>&quot;? Donors will see it straight away.">
                      Approve
                    </button>
                  </form>
                </div>
                <form method="post" action="<?= url('/admin/donations/' . $r['id'] . '/reject') ?>"
                      style="margin-top:var(--s-2)">
                  <?= csrf_field() ?>
                  <input class="field__input" name="reject_reason" maxlength="255"
                         placeholder="Reason for rejecting" style="font-size:var(--fs-xs)">
                  <button class="btn-mini btn-mini--danger" style="margin-top:4px"
                          data-confirm="Reject this request? The requester is shown your reason.">
                    Reject
                  </button>
                </form>
              <?php else: ?>
                <span class="pill <?= e($pillFor[$r['status']] ?? 'pill--neutral') ?>">
                  <?= e($tabs[$r['status']] ?? $statuses[$r['status']]) ?>
                </span>
                <?php if ($r['reviewed_at']): ?>
                  <div class="cell-sub">Reviewed <?= e(format_date($r['reviewed_at'])) ?></div>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
