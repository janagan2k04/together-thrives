<?php
/**
 * "Notifications" — the same layout for every role, matching
 * Notification-All_Org.svg and the community and admin variants.
 */
require_once APP_PATH . '/views/partials/icons.php';

$filters = ['All', 'Unread', 'Campaigns', 'Donations', 'Volunteers', 'Verification'];
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Notifications</h1>
    <p>
      <?php if ($unread): ?>
        You have <?= e($unread) ?> unread notification<?= $unread === 1 ? '' : 's' ?>.
      <?php else: ?>
        You are all caught up.
      <?php endif; ?>
    </p>
  </div>
  <div class="page-head__actions">
    <form method="post" action="<?= url('/notifications/read') ?>">
      <?= csrf_field() ?>
      <button class="btn btn--outline" <?= $unread ? '' : 'disabled' ?>>Mark all as read</button>
    </form>
    <a class="btn btn--outline" href="<?= url('/settings') ?>">Notification settings</a>
  </div>
</div>

<div class="chip-row">
  <?php foreach ($filters as $i => $f): ?>
    <button type="button" class="chip<?= $i === 0 ? ' is-active' : '' ?>"><?= e($f) ?></button>
  <?php endforeach; ?>
</div>

<?php if (!$isReal): ?>
  <p class="sample-note">
    <?= icon('help-circle') ?>
    This account has no notifications yet, so the Figma content is shown. Real notifications
    appear here the moment an administrator decides on your account or someone answers one of
    your requests.
  </p>
<?php endif; ?>

<div class="panel">
  <?php if (!$groups): ?>
    <div class="empty-state">Nothing here yet.</div>
  <?php else: ?>
    <?php foreach ($groups as [$heading, $items]): ?>
      <div class="notif-group">
        <p class="notif-group__label"><?= e($heading) ?></p>
        <?php foreach ($items as $n): ?>
          <div class="notif">
            <span class="notif__icon notif__icon--<?= e($n['tone']) ?>"><?= icon($n['icon']) ?></span>
            <div class="notif__text">
              <strong><?= e($n['title']) ?></strong>
              <?php if ($n['body'] !== ''): ?><p><?= e($n['body']) ?></p><?php endif; ?>
              <span class="notif__when"><?= e($n['when']) ?></span>
            </div>
            <div class="notif__side">
              <?php if ($n['action'] !== '' && $n['link'] !== ''): ?>
                <a class="btn btn--brand" href="<?= url($n['link']) ?>"><?= e($n['action']) ?></a>
              <?php endif; ?>
              <?php if ($n['unread']): ?><span class="notif__dot" title="Unread"></span><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
