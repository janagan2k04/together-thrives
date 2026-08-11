<?php
/** "Organizations" from Comm_User-Homepage.svg — the public directory. */
require_once APP_PATH . '/views/partials/icons.php';
?>
<div class="page-wrap">

  <div class="page-head">
    <div class="page-head__text">
      <h1>Organizations</h1>
      <p>Discover and connect with trusted organizations working for a better community.</p>
    </div>
  </div>

  <form class="filters" method="get" action="<?= url('/organizations') ?>">
    <select name="sort">
      <option>Most Active</option>
      <option>Newest</option>
      <option>A–Z</option>
    </select>
    <select name="category">
      <option>All Categories</option>
      <option>Blood Donation</option>
      <option>Environment</option>
      <option>Disaster Relief</option>
      <option>Community Support</option>
    </select>
    <select name="type">
      <option>All Types</option>
      <option>NGO / Non-Profit</option>
      <option>Government</option>
      <option>Community Group</option>
    </select>
  </form>

  <?php if ($realCount < count($organizations)): ?>
    <p class="sample-note">
      <?= icon('help-circle') ?>
      <?= $realCount ? e($realCount) . ' registered organization' . ($realCount === 1 ? ' is' : 's are') . ' listed first.' : 'No organizations have registered yet.' ?>
      The greyed entries below come from the Figma so the directory shows what a full page looks like.
    </p>
  <?php endif; ?>

  <div class="panel">
    <?php foreach ($organizations as $o): ?>
      <article class="org-card<?= $o['sample'] ? ' is-sample' : '' ?>">
        <span class="org-card__logo">Logo</span>

        <div class="org-card__body">
          <h2 class="org-card__name">
            <?= e($o['name']) ?>
            <?php if ($o['verified']): ?><?= icon('check-circle') ?><?php endif; ?>
            <?php if ($o['sample']): ?><span class="tag-sample">sample</span><?php endif; ?>
          </h2>
          <p class="org-card__blurb"><?= e(str_limit($o['blurb'], 140)) ?></p>

          <div class="tag-row">
            <?php foreach ($o['tags'] as $t): ?>
              <span class="tag"><?= e($t) ?></span>
            <?php endforeach; ?>
          </div>

          <div class="org-card__meta">
            <span><?= icon('map-pin') ?><?= e($o['city']) ?></span>
            <?php if ($o['site'] !== ''): ?>
              <span><?= icon('globe') ?><?= e($o['site']) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <div class="org-card__stats">
          <div><?= icon('users') ?><strong><?= e($o['volunteers']) ?></strong> volunteers</div>
          <div><?= icon('calendar') ?><strong><?= e($o['activities']) ?></strong> activities</div>
        </div>

        <a class="btn btn--outline" href="<?= url('/organizations/' . $o['id']) ?>">View Profile</a>
      </article>
    <?php endforeach; ?>
  </div>

</div>
