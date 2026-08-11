<?php
/**
 * Admin dashboard. Every number on this page is a real count, which is the
 * point of the screen — it is the only place that answers "is anything waiting
 * for me?" without opening four queues.
 */
require_once APP_PATH . '/views/partials/icons.php';

$roleLabels = [
    ROLE_COMMUNITY    => 'Community Member',
    ROLE_ORGANIZATION => 'Organization',
    ROLE_GN           => 'Grama Niladhari',
    ROLE_BLOOD_BANK   => 'Blood Bank',
    ROLE_ADMIN        => 'Administrator',
];
$statusTone = [
    STATUS_ACTIVE    => 'active',
    STATUS_PENDING   => 'pending',
    STATUS_SUSPENDED => 'suspended',
    STATUS_REJECTED  => 'rejected',
];
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Dashboard</h1>
    <p>Everything waiting on an administrator, and who is on the platform.</p>
  </div>
</div>

<div class="stat-grid stat-grid--4">
  <?php foreach ($stats as $s): ?>
    <a class="stat stat--<?= e($s['tone']) ?>" href="<?= url($s['link']) ?>">
      <div class="stat__label"><?= e($s['label']) ?></div>
      <div class="stat__value"><?= e($s['value']) ?></div>
      <div class="stat__hint"><?= e($s['hint']) ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="split">
  <section class="panel">
    <div class="panel__head">
      <h2>Recently registered</h2>
      <a class="btn-mini" href="<?= url('/admin/users') ?>">All users</a>
    </div>
    <div class="panel__body panel__body--flush">
      <?php if (!$recent): ?>
        <div class="admin-empty">
          <span class="admin-empty__icon"><?= icon('users') ?></span>
          <strong>No accounts yet</strong>
          <p>Registrations appear here as they arrive.</p>
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($recent as $u): ?>
              <tr>
                <td>
                  <div class="cell-title"><?= e($u['full_name']) ?></div>
                  <div class="cell-sub"><?= e($u['email']) ?></div>
                </td>
                <td><?= e($roleLabels[$u['role']] ?? $u['role']) ?></td>
                <td><span class="pill pill--<?= e($statusTone[$u['status']] ?? 'neutral') ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                <td><?= format_date($u['created_at']) ?></td>
                <td>
                  <div class="cell-actions">
                    <?php if ($u['status'] === STATUS_PENDING): ?>
                      <a class="btn-mini btn-mini--view" href="<?= url('/admin/verification/' . $u['id']) ?>">Review</a>
                    <?php else: ?>
                      <a class="btn-mini" href="<?= url('/admin/users') ?>">Manage</a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </section>

  <div class="stack">
    <section class="panel">
      <div class="panel__head"><h2>Accounts by role</h2></div>
      <div class="panel__body">
        <div class="mini-list">
          <?php foreach ($roles as $label => $count): ?>
            <div><span><?= e($label) ?></span><span><?= e($count) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><h2>Jump to</h2></div>
      <div class="panel__body stack">
        <a class="btn btn--outline btn--block" href="<?= url('/admin/verification') ?>">Verification queue</a>
        <a class="btn btn--outline btn--block" href="<?= url('/admin/reuse/letters') ?>">GS letter review</a>
        <a class="btn btn--outline btn--block" href="<?= url('/admin/complaints') ?>">Complaints &amp; feedback</a>
        <a class="btn btn--outline btn--block" href="<?= url('/admin/logs') ?>">System logs</a>
      </div>
    </section>
  </div>
</div>
