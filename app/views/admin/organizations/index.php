<?php
/** "Admin - Organizations" from admin.svg. */
require_once APP_PATH . '/views/partials/icons.php';

$statusLabels = [
    STATUS_ACTIVE    => 'Verified',
    STATUS_PENDING   => 'Pending',
    STATUS_SUSPENDED => 'Suspended',
    STATUS_REJECTED  => 'Rejected',
];
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Organizations</h1>
    <p>Verify and manage non-profit organizations and blood banks.</p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/organizations') ?>">
  <select name="type" onchange="this.form.submit()">
    <option value="">All Types</option>
    <?php foreach ($types as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= ($filters['org_type'] ?? '') === $value ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select name="status" onchange="this.form.submit()">
    <option value="">All Status</option>
    <?php foreach ($statusLabels as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <?php if ($filters): ?>
    <a class="btn-mini" href="<?= url('/admin/organizations') ?>">Clear</a>
  <?php endif; ?>
</form>

<div class="data-card">
  <?php if (!$orgs): ?>
    <div class="admin-empty">
      <span class="admin-empty__icon"><?= icon('building') ?></span>
      <strong>No organizations yet</strong>
      <p>They will appear here once an organization registers.</p>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Organization</th><th>Type</th><th>City</th>
          <th>Status</th><th>Registered</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orgs as $o): ?>
          <tr>
            <td>
              <div class="cell-title"><?= e($o['org_name']) ?></div>
              <div class="cell-sub">
                <?= e($o['website'] ?: $o['email']) ?> · Reg. <?= e($o['reg_number'] ?: '—') ?>
              </div>
            </td>
            <td><?= e($types[$o['org_type']] ?? $o['org_type']) ?></td>
            <td><?= e($o['city'] ?: '—') ?></td>
            <td>
              <span class="pill pill--<?= e($o['status']) ?>">
                <?= e($statusLabels[$o['status']] ?? $o['status']) ?>
              </span>
            </td>
            <td><?= format_date($o['created_at']) ?></td>
            <td>
              <div class="cell-actions">
                <?php if ($o['status'] === STATUS_PENDING): ?>
                  <a class="btn-mini btn-mini--view" href="<?= url('/admin/verification/' . $o['user_id']) ?>">
                    Review
                  </a>
                <?php elseif ($o['status'] === STATUS_ACTIVE): ?>
                  <form method="post" action="<?= url('/admin/users/' . $o['user_id'] . '/suspend') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--danger"
                            data-confirm="Suspend <?= e($o['org_name']) ?>?">Suspend</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="<?= url('/admin/users/' . $o['user_id'] . '/reinstate') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--approve"
                            data-confirm="Reinstate <?= e($o['org_name']) ?>?">Reinstate</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
