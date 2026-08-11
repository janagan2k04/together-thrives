<?php
/** "Admin - Suspended Users and Posts" from admin.svg. */
require_once APP_PATH . '/views/partials/icons.php';

$tone = ['Suspended' => 'suspended', 'Removed' => 'neutral', 'Banned' => 'rejected'];
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Suspended Users and Posts</h1>
    <p>Manage suspended accounts and removed content across all modules.</p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/suspended') ?>">
  <select name="type">
    <option>All Types</option>
    <option>User Accounts</option>
    <option>Organizations</option>
    <option>Campaigns</option>
    <option>Item Listings</option>
  </select>
  <select name="reason">
    <option>All Reasons</option>
    <option>False Information</option>
    <option>Spam / Duplicate</option>
    <option>Inappropriate Content</option>
    <option>Fraud Suspicion</option>
  </select>
</form>

<?php if ($realCount < count($rows)): ?>
  <p class="sample-note">
    <?= icon('help-circle') ?>
    <?= $realCount
        ? e($realCount) . ' real suspended account' . ($realCount === 1 ? '' : 's') . ' listed first — those buttons work.'
        : 'No accounts are currently suspended.' ?>
    The greyed rows are Figma content; suspended <em>posts</em> need the modules that own them.
  </p>
<?php endif; ?>

<div class="data-card">
  <table class="data-table">
    <thead>
      <tr><th>Account / Post</th><th>Type</th><th>Reason</th><th>Suspended On</th><th>Status</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="<?= $r['sample'] ? 'is-sample' : '' ?>">
          <td>
            <div class="cell-title">
              <?= e($r['name']) ?>
              <?php if ($r['sample']): ?><span class="tag-sample">sample</span><?php endif; ?>
            </div>
            <div class="cell-sub"><?= e($r['sub']) ?></div>
          </td>
          <td><?= e($r['type']) ?></td>
          <td><?= e($r['reason']) ?></td>
          <td><?= e($r['on']) ?></td>
          <td><span class="pill pill--<?= e($tone[$r['status']] ?? 'neutral') ?>"><?= e($r['status']) ?></span></td>
          <td>
            <div class="cell-actions">
              <?php if (!$r['sample'] && $r['action'] === 'Reinstate'): ?>
                <form method="post" action="<?= url('/admin/users/' . $r['id'] . '/reinstate') ?>" class="inline">
                  <?= csrf_field() ?>
                  <button class="btn-mini btn-mini--approve"
                          data-confirm="Reinstate <?= e($r['name']) ?>?">Reinstate</button>
                </form>
              <?php else: ?>
                <button class="btn-mini" type="button" disabled><?= e($r['action']) ?></button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
