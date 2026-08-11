<?php
/** "Admin - Users" from admin.svg. */
require_once APP_PATH . '/views/partials/icons.php';

$roleLabels = [
    ROLE_COMMUNITY    => 'Community Member',
    ROLE_ORGANIZATION => 'Organization',
    ROLE_GN           => 'Grama Niladhari',
    ROLE_BLOOD_BANK   => 'Blood Bank',
];
$statusLabels = [
    STATUS_ACTIVE    => 'Verified',
    STATUS_PENDING   => 'Pending',
    STATUS_SUSPENDED => 'Suspended',
    STATUS_REJECTED  => 'Rejected',
];
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Users</h1>
    <p>View, verify and manage all member accounts.</p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/users') ?>">
  <select name="role" onchange="this.form.submit()">
    <option value="">All Roles</option>
    <?php foreach ($roleLabels as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= ($filters['role'] ?? '') === $value ? 'selected' : '' ?>>
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
    <a class="btn-mini" href="<?= url('/admin/users') ?>">Clear</a>
  <?php endif; ?>
</form>

<div class="data-card">
  <?php if (!$users): ?>
    <div class="admin-empty">
      <span class="admin-empty__icon"><?= icon('users') ?></span>
      <strong>No accounts match</strong>
      <p>Try clearing the filters.</p>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>User</th><th>Role</th><th>District</th>
          <th>Status</th><th>Joined</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="cell-title"><?= e($u['full_name']) ?></div>
              <div class="cell-sub"><?= e($u['email']) ?></div>
            </td>
            <td><?= e($roleLabels[$u['role']] ?? $u['role']) ?></td>
            <td><?= e($u['district'] ?: '—') ?></td>
            <td>
              <span class="pill pill--<?= e($u['status']) ?>">
                <?= e($statusLabels[$u['status']] ?? $u['status']) ?>
              </span>
            </td>
            <td><?= format_date($u['created_at']) ?></td>
            <td>
              <div class="cell-actions">
                <?php if ($u['status'] === STATUS_PENDING): ?>
                  <a class="btn-mini btn-mini--view" href="<?= url('/admin/verification/' . $u['id']) ?>">
                    Review
                  </a>
                <?php elseif ($u['status'] === STATUS_ACTIVE): ?>
                  <form method="post" action="<?= url('/admin/users/' . $u['id'] . '/suspend') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--danger"
                            data-confirm="Suspend <?= e($u['full_name']) ?>? They will be signed out and blocked.">
                      Suspend
                    </button>
                  </form>
                <?php else: ?>
                  <form method="post" action="<?= url('/admin/users/' . $u['id'] . '/reinstate') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn-mini btn-mini--approve"
                            data-confirm="Reinstate <?= e($u['full_name']) ?>?">
                      Reinstate
                    </button>
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
