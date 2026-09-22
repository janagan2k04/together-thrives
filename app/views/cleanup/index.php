<?php
/**
 * "Cleanup" - frame 1 of Cleanup-User.svg. Open to guests.
 *
 * The two tabs are a query string rather than JavaScript, so every tab is a
 * shareable URL and the back button behaves. The filter selects post the same
 * way and now really narrow the list - `CleanupEvent::browse()` applies them.
 *
 * Only approved campaigns reach this page. A campaign an administrator has not
 * published yet is private to the organisation that posted it.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Cleanup\CleanupEvent;

/** "Tomorrow, 7:00 AM" - the card's one-line when, built from the two columns. */
$when = static function (array $ev): string {
    $day  = strtotime($ev['event_date']);
    $diff = (int) floor(($day - strtotime('today')) / 86400);
    $label = match (true) {
        $diff === 0 => 'Today',
        $diff === 1 => 'Tomorrow',
        default     => date('d M Y', $day),
    };
    return $label . ', ' . date('g:i A', strtotime($ev['start_time']));
};

/**
 * Who may start a campaign from this page. An organisation gets the real form;
 * a community member gets the proposal form; a guest is sent to sign in. A GN
 * officer and a blood bank can open neither route, so they get no button at all
 * - before the week 6 audit they were handed the community form and got a 403.
 */
$createUrl = match (true) {
    !is_logged_in()             => '/login',
    role_is(ROLE_ORGANIZATION)  => '/org/cleanup/create',
    role_is(ROLE_COMMUNITY)     => '/cleanup/create',
    default                     => null,
};
?>

<div class="cl-page">

<div class="cl-head">
  <span class="cl-head__icon"><?= icon('leaf') ?></span>
  <div class="cl-head__text">
    <h1>Cleanup / Volunteering Campaigns</h1>
    <p>Join hands to clean and rebuild our communities.</p>
  </div>
</div>

<?php if ($impact): ?>
  <div class="cl-impact">
    <span class="cl-impact__label">Your Impact</span>
    <?php foreach ($impact as [$value, $label, $tone]): ?>
      <div class="cl-tile cl-tile--<?= e($tone) ?>">
        <strong><?= e($value) ?></strong>
        <span><?= e($label) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="cl-tabs">
  <a class="<?= $tab === 'all' ? 'is-active' : '' ?>" href="<?= url('/cleanup') ?>">All</a>
  <a class="<?= $tab === 'joined' ? 'is-active' : '' ?>" href="<?= url('/cleanup?tab=joined') ?>">Joined</a>
  <span class="cl-tabs__spacer"></span>
  <?php if ($createUrl !== null): ?>
    <a class="btn btn--primary" href="<?= url($createUrl) ?>">Create Campaign</a>
  <?php endif; ?>
</div>

<form class="cl-filters" method="get" action="<?= url('/cleanup') ?>">
  <input type="hidden" name="tab" value="<?= e($tab) ?>">

  <select name="city" onchange="this.form.submit()">
    <option value="">All Locations</option>
    <?php foreach ($cities as $c): ?>
      <option value="<?= e($c['id']) ?>" <?= (string) ($filters['city'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
        <?= e($c['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="category" onchange="this.form.submit()">
    <option value="">All Categories</option>
    <?php foreach (CleanupEvent::CATEGORIES as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= ($filters['category'] ?? '') === $key ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="status" onchange="this.form.submit()">
    <option value="">All Status</option>
    <?php foreach (CleanupEvent::PUBLIC_STATUSES as $key): ?>
      <option value="<?= e($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
        <?= e(CleanupEvent::statusLabel($key)) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <span class="cl-filters__spacer"></span>
  <button class="btn btn--outline" type="submit"><?= icon('search') ?>Filters</button>
</form>

<?php if (!$events): ?>
  <div class="card empty-state">
    <?= $tab === 'joined'
        ? 'You have not joined a campaign yet. Open the All tab to find one near you.'
        : 'No cleanup campaigns are published right now.' ?>
  </div>
<?php else: ?>
  <div class="cl-list">
    <?php foreach ($events as $ev): ?>
      <?php $hasJoined = in_array((int) $ev['id'], $joined, true); ?>
      <article class="cl-card">
        <div class="cl-card__media">
          <?= icon('leaf') ?>
          <span class="cl-card__badge cl-status cl-status--<?= e(CleanupEvent::statusTone($ev['status'])) ?>">
            <?= e(CleanupEvent::statusLabel($ev['status'])) ?>
          </span>
        </div>

        <div class="cl-card__body">
          <h3><a href="<?= url('/cleanup/' . $ev['id']) ?>"><?= e($ev['title']) ?></a></h3>
          <span class="cl-card__where"><?= icon('map-pin') ?><?= e($ev['district']) ?></span>
          <p class="cl-card__blurb"><?= e(str_limit($ev['description'], 160)) ?></p>

          <div class="cl-card__meta">
            <span class="cl-fact"><?= icon('calendar') ?><?= e($when($ev)) ?></span>
            <span class="cl-fact"><?= icon('users') ?><?= e($ev['registered']) ?> Volunteers</span>
            <span class="cl-fact"><?= icon('leaf') ?><?= e(CleanupEvent::categoryLabel($ev['category'])) ?></span>
          </div>
        </div>

        <div class="cl-card__side">
          <span class="cl-card__pct">
            <?= e(CleanupEvent::fillPercent($ev)) ?>%
            <small>Places filled</small>
          </span>

          <?php if ($ev['status'] === EVENT_COMPLETED): ?>
            <a class="btn btn--outline" href="<?= url('/cleanup/' . $ev['id']) ?>">View Campaign</a>
          <?php elseif (!is_logged_in()): ?>
            <a class="btn btn--primary" href="<?= url('/login') ?>">Join Campaign</a>
          <?php elseif ($hasJoined): ?>
            <a class="btn btn--outline" href="<?= url('/cleanup/' . $ev['id']) ?>">Already Joined</a>
          <?php elseif (role_is(ROLE_COMMUNITY)): ?>
            <a class="btn btn--primary" href="<?= url('/cleanup/' . $ev['id'] . '/join') ?>">Join Campaign</a>
          <?php else: ?>
            <a class="btn btn--outline" href="<?= url('/cleanup/' . $ev['id']) ?>">View Campaign</a>
          <?php endif; ?>

          <span class="cl-card__share"><?= icon('arrow-right') ?>Share</span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

</div>
