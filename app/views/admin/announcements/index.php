<?php
/** "Admin - Announcements" from admin.svg. */
require_once APP_PATH . '/views/partials/icons.php';
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Announcements</h1>
    <p>Publish platform-wide notices to users, organizations and Grama Niladharis.</p>
  </div>
  <a class="btn btn--brand" href="<?= url('/admin/announcements/create') ?>">New Announcement</a>
</div>

<form class="filters" method="get" action="<?= url('/admin/announcements') ?>">
  <select name="audience">
    <option value="">All Audiences</option>
    <?php foreach ($audiences as $value => $label): ?>
      <option value="<?= e($value) ?>"><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<p class="sample-note">
  <?= icon('help-circle') ?>
  Sample content. The <code>announcements</code> table is in the schema with the same audience
  list the form offers, so only the insert is missing.
</p>

<div class="data-card">
  <table class="data-table">
    <thead><tr><th>Announcement</th><th>Audience</th><th>Published On</th><th>Reach</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($announcements as $a): ?>
        <tr>
          <td>
            <div class="cell-title"><?= e($a['title']) ?></div>
            <div class="cell-sub"><?= e($a['sub']) ?></div>
          </td>
          <td><?= e($a['audience']) ?></td>
          <td><?= e($a['on']) ?></td>
          <td><?= e($a['reach']) ?></td>
          <td>
            <div class="cell-actions">
              <a class="btn-mini btn-mini--view" href="<?= url('/admin/announcements/' . $a['id'] . '/edit') ?>">Edit</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
