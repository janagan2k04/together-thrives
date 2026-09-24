<?php
/** Organization profile. No frame exists for it; it follows the directory card. */
require_once APP_PATH . '/views/partials/icons.php';
?>
<div class="page-wrap">

  <a class="back-link" href="<?= url('/organizations') ?>"><?= icon('arrow-left') ?>Back to Organizations</a>

  <div class="page-head">
    <div class="page-head__text">
      <h1>
        <?= e($org['name']) ?>
      </h1>
      <p><?= e($org['blurb']) ?></p>
    </div>
    <div class="page-head__actions">
      <?php if (is_logged_in()): ?>
        <a class="btn btn--outline" href="<?= url('/messages') ?>">Message</a>
      <?php endif; ?>
      <?php /* A Grama Niladhari officer hosts campaigns rather than joining
               them, so /cleanup is blocked for the role and this button 403'd. */ ?>
      <?php if (!role_is(ROLE_GN)): ?>
        <a class="btn btn--primary" href="<?= url('/cleanup') ?>">See their campaigns</a>
      <?php else: ?>
        <a class="btn btn--primary" href="<?= url('/org/cleanup') ?>">My campaigns</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="split">
    <div class="stack">
      <section class="panel">
        <div class="panel__head"><?= icon('building') ?><h2>About</h2></div>
        <div class="panel__body">
          <p style="font-size:var(--fs-base);color:var(--ink-2)"><?= e($org['blurb']) ?></p>
          <div class="tag-row" style="margin-top:var(--s-4)">
            <?php foreach ($org['tags'] as $t): ?>
              <span class="tag"><?= e($t) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('calendar') ?><h2>Recent Activity</h2></div>
        <div class="panel__body">
          <div class="empty-state">
            This organisation has not run a campaign yet.
          </div>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="panel">
        <div class="panel__head"><h2>Details</h2></div>
        <div class="panel__body">
          <dl class="kv kv--single">
            <div><dt>Type</dt><dd><?= e($org['type']) ?></dd></div>
            <div><dt>Location</dt><dd><?= e($org['city']) ?></dd></div>
            <?php if (!empty($org['reg'])): ?>
              <div><dt>Registration</dt><dd><?= e($org['reg']) ?></dd></div>
            <?php endif; ?>
            <?php if ($org['site'] !== ''): ?>
              <div><dt>Website</dt><dd><?= e($org['site']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($org['email']) && is_logged_in()): ?>
              <div><dt>Email</dt><dd><?= e($org['email']) ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($org['phone']) && is_logged_in()): ?>
              <div><dt>Phone</dt><dd><?= e($org['phone']) ?></dd></div>
            <?php endif; ?>
            <div>
              <dt>Verification</dt>
              <dd>
                <?php if ($org['verified']): ?>
                  <span class="badge badge--success">Verified by an administrator</span>
                <?php else: ?>
                  <span class="badge badge--pending">Not yet verified</span>
                <?php endif; ?>
              </dd>
            </div>
          </dl>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><h2>Impact</h2></div>
        <div class="panel__body">
          <div class="mini-list">
            <div><span>Volunteers</span><span><?= e($org['volunteers']) ?></span></div>
            <div><span>Activities</span><span><?= e($org['activities']) ?></span></div>
          </div>
        </div>
      </section>
    </div>
  </div>

</div>
