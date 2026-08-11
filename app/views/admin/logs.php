<?php
/**
 * "Admin - System Logs" from admin.svg.
 *
 * This one is real. `Logger::audit()` has written every approval, rejection,
 * suspension, sign-in and reuse decision since week 2, so the screen reads the
 * file back rather than showing invented rows.
 */
require_once APP_PATH . '/views/partials/icons.php';

$sources = ['audit' => 'Audit trail', 'error' => 'Errors', 'app' => 'Application'];

/** A line is a failure if it says so. Everything else the logger writes succeeded. */
$failed = static fn(string $m): bool =>
    (bool) preg_match('/\b(fail|failed|refus|denied|error|invalid)\b/i', $m);
?>

<div class="admin-head">
  <div class="admin-head__text">
    <h1>System Logs</h1>
    <p>Audit trail of all administrator and system actions on the platform.</p>
  </div>
</div>

<form class="filters" method="get" action="<?= url('/admin/logs') ?>">
  <select name="source" onchange="this.form.submit()">
    <?php foreach ($sources as $value => $label): ?>
      <option value="<?= e($value) ?>" <?= $source === $value ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="data-card">
  <?php if (!$lines): ?>
    <div class="admin-empty">
      <span class="admin-empty__icon"><?= icon('file-text') ?></span>
      <strong>Nothing logged yet</strong>
      <p><code>storage/logs/<?= e($source) ?>.log</code> is empty or has not been created.</p>
    </div>
  <?php else: ?>
    <table class="data-table log-table">
      <thead><tr><th>Timestamp</th><th>Level</th><th>Action</th><th>Result</th></tr></thead>
      <tbody>
        <?php foreach ($lines as $l): ?>
          <tr>
            <td><?= e($l['at'] ?: '—') ?></td>
            <td><span class="pill pill--neutral"><?= e($l['level']) ?></span></td>
            <td><?= e($l['message']) ?></td>
            <td>
              <?php if ($source === 'error' || $failed($l['message'])): ?>
                <span class="pill pill--rejected">Failed</span>
              <?php else: ?>
                <span class="pill pill--active">Success</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  Showing the most recent entries from <code>storage/logs/<?= e($source) ?>.log</code>, newest
  first. The <code>audit_logs</code> table exists too; moving the logger onto it is a later
  change and this screen would not need to alter.
</p>
