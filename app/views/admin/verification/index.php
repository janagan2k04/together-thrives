<?php
/**
 * "Admin - Verification Requests" from admin.svg.
 *
 * The design mixes account registrations with campaign and donation approvals.
 * Only registrations exist so far — the other request types arrive with their
 * own modules in weeks 3–5, so this table shows accounts only.
 */
require_once APP_PATH . '/views/partials/icons.php';

$roleLabels = [
    ROLE_COMMUNITY    => 'Community Member',
    ROLE_ORGANIZATION => 'Org Registration',
    ROLE_GN           => 'GN Registration',
    ROLE_BLOOD_BANK   => 'Blood Bank',
];
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Verification Requests</h1>
    <p>Review and approve account registrations before they can sign in.</p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/verification') ?>">
  <select name="role" onchange="this.form.submit()">
    <option value="">All Types</option>
    <?php foreach ($roleLabels as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= $role === $value ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <?php if ($role !== ''): ?>
    <a class="btn-mini" href="<?= url('/admin/verification') ?>">Clear</a>
  <?php endif; ?>
</form>

<div class="data-card">
  <?php if (!$requests): ?>
    <div class="admin-empty">
      <span class="admin-empty__icon"><?= icon('check-circle') ?></span>
      <strong>Nothing waiting for review</strong>
      <p>Every registration has been dealt with. New ones will appear here.</p>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Request</th>
          <th>Type</th>
          <th>Submitted By</th>
          <th>Submitted On</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <?php
            // Each role keeps its identifying detail on a different table.
            $name = match ($r['role']) {
                ROLE_ORGANIZATION => $r['org_name'],
                ROLE_BLOOD_BANK   => $r['bank_name'],
                default           => $r['full_name'],
            };
            $sub = match ($r['role']) {
                ROLE_ORGANIZATION => 'Reg. ' . ($r['reg_number'] ?: '—'),
                ROLE_GN           => trim(($r['gn_division'] ?? '') . ' · ' . ($r['district'] ?? ''), ' ·'),
                ROLE_BLOOD_BANK   => 'Licence ' . ($r['license_no'] ?: '—'),
                default           => 'Community member',
            };
          ?>
          <tr>
            <td>
              <div class="cell-title"><?= e($name ?: $r['full_name']) ?></div>
              <div class="cell-sub"><?= e($sub) ?></div>
            </td>
            <td><?= e($roleLabels[$r['role']] ?? $r['role']) ?></td>
            <td><?= e($r['email']) ?></td>
            <td><?= format_date($r['created_at']) ?></td>
            <td>
              <div class="cell-actions">
                <a class="btn-mini btn-mini--view" href="<?= url('/admin/verification/' . $r['id']) ?>">
                  <?= icon('eye') ?> View
                </a>
                <form method="post" action="<?= url('/admin/verification/' . $r['id'] . '/approve') ?>" class="inline">
                  <?= csrf_field() ?>
                  <button class="btn-mini btn-mini--approve"
                          data-confirm="Approve <?= e($name ?: $r['full_name']) ?>? They will be able to sign in.">
                    Verify
                  </button>
                </form>
                <form method="post" action="<?= url('/admin/verification/' . $r['id'] . '/reject') ?>" class="inline">
                  <?= csrf_field() ?>
                  <button class="btn-mini btn-mini--danger"
                          data-confirm="Reject <?= e($name ?: $r['full_name']) ?>? Open the request first if you want to give a reason.">
                    Reject
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
