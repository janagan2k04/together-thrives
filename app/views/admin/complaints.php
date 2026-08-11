<?php
/** "Admin - Complaints & Feedback" from admin.svg. */
require_once APP_PATH . '/views/partials/icons.php';

$tone = ['Open' => 'pending', 'In Review' => 'neutral', 'Resolved' => 'active', 'Dismissed' => 'rejected'];
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Complaints &amp; Feedback</h1>
    <p>Resolve reported issues, disputes and user feedback across the platform.</p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/complaints') ?>">
  <select name="module">
    <option>All Modules</option>
    <option>Cleanup</option>
    <option>Item / Fund Donation</option>
    <option>Blood Donation</option>
    <option>Reuse Items</option>
    <option>Dengue Alerts</option>
  </select>
  <select name="status">
    <option>All Status</option>
    <option>Open</option><option>Reviewing</option><option>Resolved</option><option>Dismissed</option>
  </select>
  <select name="priority">
    <option>All Priority</option>
    <option>High</option><option>Medium</option><option>Low</option>
  </select>
</form>

<p class="sample-note">
  <?= icon('help-circle') ?>
  Sample content. The <code>reports</code> table is in the schema but nothing writes to it
  yet — complaints become real with the moderation backend.
</p>

<div class="data-card">
  <table class="data-table">
    <thead><tr><th>Subject</th><th>Module</th><th>Raised By</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($complaints as $c): ?>
        <tr>
          <td>
            <div class="cell-title"><?= e($c['subject']) ?></div>
            <div class="cell-sub"><?= e($c['id']) ?> · <?= e($c['sub']) ?></div>
          </td>
          <td><?= e($c['module']) ?></td>
          <td>
            <div class="cell-title"><?= e($c['by']) ?></div>
            <div class="cell-sub"><?= e($c['byRole']) ?></div>
          </td>
          <td><span class="pill pill--<?= e($tone[$c['status']] ?? 'neutral') ?>"><?= e($c['status']) ?></span></td>
          <td>
            <div class="cell-actions">
              <a class="btn-mini btn-mini--view" href="<?= url('/admin/complaints/' . $c['id']) ?>">View</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
