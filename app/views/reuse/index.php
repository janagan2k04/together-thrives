<?php
/** "Reuse" browse frame from UI/Reuse/Reuse.svg. Open to guests. */
require_once APP_PATH . '/views/partials/icons.php';

// The design splits the first row out as "Recently Added Items". That heading
// only makes sense on an unfiltered first page sorted newest-first.
$isDefaultView = $page === 1 && empty($filters['sort']) && count($filters) === 0;
$recent        = $isDefaultView ? array_slice($items, 0, 4) : [];
$rest          = $isDefaultView ? array_slice($items, 4)    : $items;

/** Keep the current filters when only one of them changes. */
$queryWith = function (array $overrides) use ($filters): string {
    $params = array_filter(array_merge($filters, $overrides), fn($v) => $v !== '' && $v !== null);
    return $params ? '?' . http_build_query($params) : '';
};
?>

<div class="reuse-page">

  <div class="page-header">
    <div>
      <h1>Browse Reuse Items</h1>
      <p class="page-header__sub">Give items a second life and help someone in need.</p>
    </div>
    <div class="page-header__spacer"></div>
    <?php if (is_logged_in()): ?>
      <a class="btn btn--primary" href="<?= url('/my/reuse/create') ?>">Donate an Item</a>
    <?php else: ?>
      <a class="btn btn--primary" href="<?= url('/login') ?>" data-signin-required>Donate an Item</a>
    <?php endif; ?>
  </div>

  <form class="reuse-filters" method="get" action="<?= url('/reuse') ?>">
    <div class="reuse-filters__search">
      <?= icon('search') ?>
      <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>"
             placeholder='Search items, e.g., "chair", "book", "clothes"'>
    </div>

    <select name="category_id" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c['id']) ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
          <?= e($c['name']) ?> (<?= (int) $c['item_count'] ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <select name="item_condition" onchange="this.form.submit()">
      <option value="">All Conditions</option>
      <?php foreach ($conditions as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= ($filters['item_condition'] ?? '') === $value ? 'selected' : '' ?>>
          <?= e($label) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="city_id" onchange="this.form.submit()">
      <option value="">All Locations</option>
      <?php foreach ($cities as $city): ?>
        <option value="<?= e($city['id']) ?>" <?= (int) ($filters['city_id'] ?? 0) === (int) $city['id'] ? 'selected' : '' ?>>
          <?= e($city['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="sort" onchange="this.form.submit()">
      <option value="">Sort by: Newest</option>
      <option value="oldest"   <?= ($filters['sort'] ?? '') === 'oldest'   ? 'selected' : '' ?>>Oldest first</option>
      <option value="category" <?= ($filters['sort'] ?? '') === 'category' ? 'selected' : '' ?>>Category A-Z</option>
    </select>

    <button class="btn btn--outline" type="submit">Filters</button>
    <?php if ($filters): ?>
      <a class="btn btn--ghost" href="<?= url('/reuse') ?>">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (!$items): ?>
    <div class="card empty-state">
      <strong>No items match</strong>
      <p><?= $filters ? 'Try clearing the filters.' : 'Nothing has been listed yet - be the first to donate an item.' ?></p>
    </div>
  <?php else: ?>

    <?php if ($recent): ?>
      <h2 class="reuse-section">Recently Added Items</h2>
      <div class="item-grid">
        <?php foreach ($recent as $item): ?>
          <?php require APP_PATH . '/views/reuse/_card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($rest): ?>
      <h2 class="reuse-section"><?= $recent ? 'More Items' : e($total) . ' item' . ($total === 1 ? '' : 's') ?></h2>
      <div class="item-grid">
        <?php foreach ($rest as $item): ?>
          <?php require APP_PATH . '/views/reuse/_card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
      <nav class="pager">
        <?php if ($page > 1): ?>
          <a class="btn btn--outline" href="<?= url('/reuse' . $queryWith(['page' => $page - 1])) ?>">Previous</a>
        <?php endif; ?>
        <span class="pager__status">Page <?= e($page) ?> of <?= e($pages) ?></span>
        <?php if ($page < $pages): ?>
          <a class="btn btn--outline" href="<?= url('/reuse' . $queryWith(['page' => $page + 1])) ?>">Next</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

  <?php endif; ?>
</div>
