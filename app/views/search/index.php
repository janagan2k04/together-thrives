<?php
/**
 * The topbar search results. Reuse listings are searched for real; the other
 * three modules are listed as sections so it is obvious the search reaches
 * them once they store rows.
 */
require_once APP_PATH . '/views/partials/icons.php';

$pending = [
    ['Cleanup campaigns',  '/cleanup',   'Member 1 · weeks 4–5'],
    ['Donation requests',  '/donations', 'Member 2 · weeks 4–5'],
    ['Blood campaigns',    '/blood',     'Member 3 · weeks 4–5'],
];
?>
<div class="page-wrap">

  <div class="page-head">
    <div class="page-head__text">
      <h1>Search</h1>
      <p>
        <?php if ($q === ''): ?>
          Search campaigns, requests and reuse listings across the platform.
        <?php else: ?>
          <?= e(count($items)) ?> reuse listing<?= count($items) === 1 ? '' : 's' ?> matching
          &ldquo;<?= e($q) ?>&rdquo;.
        <?php endif; ?>
      </p>
    </div>
  </div>

  <form method="get" action="<?= url('/search') ?>" style="margin-bottom:var(--s-5)">
    <div class="field" style="max-width:560px;margin:0">
      <input class="field__input" type="search" name="q" value="<?= e($q) ?>"
             placeholder="Search campaigns, requests, resources…">
    </div>
  </form>

  <section class="panel" style="margin-bottom:var(--s-5)">
    <div class="panel__head"><?= icon('leaf') ?><h2>Reuse Items</h2><small>Searched live</small></div>
    <?php if ($q === ''): ?>
      <div class="empty-state">Type something above to search.</div>
    <?php elseif (!$items): ?>
      <div class="empty-state">No reuse listings match &ldquo;<?= e($q) ?>&rdquo;.</div>
    <?php else: ?>
      <div class="panel__body panel__body--flush">
        <table class="data-table">
          <thead><tr><th>Listing</th><th>Category</th><th>City</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($items as $i): ?>
              <tr>
                <td>
                  <div class="cell-title"><?= e($i['title']) ?></div>
                  <div class="cell-sub"><?= e(str_limit($i['description'], 80)) ?></div>
                </td>
                <td><?= e($i['category']) ?></td>
                <td><?= e($i['city']) ?></td>
                <td><span class="pill pill--<?= $i['status'] === ITEM_AVAILABLE ? 'active' : 'neutral' ?>"><?= e(ucfirst($i['status'])) ?></span></td>
                <td>
                  <div class="cell-actions">
                    <a class="btn-mini btn-mini--view" href="<?= url('/reuse/' . $i['id']) ?>">View</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="panel__head"><h2>Other modules</h2></div>
    <div class="panel__body stack">
      <?php foreach ($pending as [$label, $link, $owner]): ?>
        <div class="row-between">
          <div>
            <strong style="font-size:var(--fs-sm)"><?= e($label) ?></strong>
            <div class="muted">Searchable once the module stores rows — <?= e($owner) ?></div>
          </div>
          <a class="btn-mini" href="<?= url($link) ?>">Browse</a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</div>
