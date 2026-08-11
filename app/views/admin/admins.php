<?php
/**
 * "Admin - Admin Management" from admin.svg.
 *
 * The real administrator rows come first. The group decided there is exactly
 * one administrator for the interim, so the extra rows are the Figma's and are
 * marked as such — nobody should go looking for a password for them.
 */
require_once APP_PATH . '/views/partials/icons.php';
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>Admin Management</h1>
    <p>Manage administrator accounts, roles and permissions.</p>
  </div>
  <a class="btn btn--brand" href="#add">Add Administrator</a>
</div>

<form class="filters" method="get" action="<?= url('/admin/admins') ?>">
  <select name="role">
    <option>All Roles</option>
    <option>Super Administrator</option>
    <option>Moderator</option>
    <option>Support Staff</option>
  </select>
  <select name="status">
    <option>All Status</option><option>Active</option><option>Inactive</option>
  </select>
</form>

<p class="sample-note">
  <?= icon('help-circle') ?>
  <?= e(count($real)) ?> real administrator account<?= count($real) === 1 ? '' : 's' ?>.
  The greyed rows below are Figma content — the group agreed on a single administrator
  for the interim, and no per-administrator permission columns exist in the schema.
</p>

<div class="data-card">
  <table class="data-table">
    <thead>
      <tr><th>Administrator</th><th>Role</th><th>Permissions</th><th>Last Active</th><th>Status</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($real as $a): ?>
        <tr>
          <td>
            <div class="cell-title"><?= e($a['full_name']) ?></div>
            <div class="cell-sub"><?= e($a['email']) ?></div>
          </td>
          <td>Super Administrator</td>
          <td>Full access</td>
          <td><?= $a['last_login_at'] ? format_date($a['last_login_at'], 'd M Y, H:i') : 'Never' ?></td>
          <td><span class="pill pill--<?= $a['status'] === STATUS_ACTIVE ? 'active' : 'suspended' ?>"><?= e(ucfirst($a['status'])) ?></span></td>
          <td>
            <div class="cell-actions">
              <a class="btn-mini btn-mini--view" href="<?= url('/profile') ?>">Edit</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php foreach ($samples as $a): ?>
        <tr class="is-sample">
          <td>
            <div class="cell-title"><?= e($a['name']) ?><span class="tag-sample">sample</span></div>
            <div class="cell-sub"><?= e($a['email']) ?></div>
          </td>
          <td><?= e($a['role']) ?></td>
          <td><?= e($a['perms']) ?></td>
          <td><?= e($a['active']) ?></td>
          <td><span class="pill pill--<?= $a['status'] === 'Active' ? 'active' : 'neutral' ?>"><?= e($a['status']) ?></span></td>
          <td>
            <div class="cell-actions">
              <button class="btn-mini" type="button" disabled>Edit</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<section class="panel" style="margin-top:var(--s-5);max-width:680px" id="add">
  <div class="panel__head"><?= icon('user-cog') ?><h2>Add Administrator</h2></div>
  <div class="panel__body">
    <form method="post" action="<?= url('/admin/admins') ?>">
      <?= csrf_field() ?>
      <div class="field-row">
        <div class="field <?= has_error('full_name') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="full_name">Full Name</label>
          <input class="field__input" id="full_name" name="full_name" value="<?= old('full_name') ?>">
          <span class="field__error"><?= error_for('full_name') ?></span>
        </div>
        <div class="field <?= has_error('email') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="email">Email</label>
          <input class="field__input" id="email" name="email" type="email" value="<?= old('email') ?>">
          <span class="field__error"><?= error_for('email') ?></span>
        </div>
      </div>

      <div class="field <?= has_error('admin_role') ? 'field--invalid' : '' ?>">
        <label class="field__label" for="admin_role">Role</label>
        <select class="field__select" id="admin_role" name="admin_role">
          <option value="super">Super Administrator</option>
          <option value="moderator">Moderator</option>
          <option value="support">Support Staff</option>
        </select>
        <span class="field__hint">
          <code>users.role</code> has one administrator value, so these labels are not stored —
          adding a sub-role needs a schema change and a group decision.
        </span>
        <span class="field__error"><?= error_for('admin_role') ?></span>
      </div>

      <button class="btn btn--brand" type="submit">Add Administrator</button>
    </form>
  </div>
</section>
