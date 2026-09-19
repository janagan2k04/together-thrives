<?php
/**
 * The donor's own listings. No Figma frame exists for this screen, so it
 * follows the table pattern the admin screens already use.
 */
require_once APP_PATH . '/views/partials/icons.php';

$conditions = \Models\Reuse\ReuseItem::CONDITIONS;
$statuses   = \Models\Reuse\ReuseItem::STATUSES;
?>

<div class="reuse-page">

  <div class="page-header">
    <div>
      <h1>My Listings</h1>
      <p class="page-header__sub">Everything you have listed for reuse.</p>
    </div>
    <div class="page-header__spacer"></div>
    <a class="btn btn--primary" href="<?= url('/my/reuse/create') ?>">Donate an Item</a>
  </div>

  <div class="stat-strip">
    <?php foreach ($statuses as $value => $label): ?>
      <div class="stat-strip__cell">
        <strong><?= (int) ($counts[$value] ?? 0) ?></strong>
        <span><?= e($label) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (!$items): ?>
    <div class="card empty-state">
      <strong>You have not listed anything yet</strong>
      <p>List a reusable item and give it a second life for someone in need.</p>
      <p><a class="btn btn--primary" href="<?= url('/my/reuse/create') ?>">Donate an Item</a></p>
    </div>
  <?php else: ?>
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Item</th><th>Category</th><th>Condition</th>
            <th>Location</th><th>Status</th><th>Requests</th><th>Listed</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div class="cell-item">
                <span class="cell-item__thumb">
                  <?php if (!empty($item['image_id'])): ?>
                    <img src="<?= url('/reuse/image/' . $item['image_id']) ?>" alt="">
                  <?php else: ?>
                    <?= icon('gift') ?>
                  <?php endif; ?>
                </span>
                <a class="cell-item__title" href="<?= url('/reuse/' . $item['id']) ?>">
                  <?= e($item['title']) ?>
                </a>
              </div>
            </td>
            <td><?= e($item['category']) ?></td>
            <td><?= e($conditions[$item['item_condition']] ?? $item['item_condition']) ?></td>
            <td><?= e($item['city']) ?></td>
            <td>
              <span class="item-status item-status--<?= e($item['status']) ?>">
                <?= e($statuses[$item['status']] ?? $item['status']) ?>
              </span>
            </td>
            <td>
              <?php if ((int) $item['pending_requests'] > 0): ?>
                <a class="btn btn--ghost btn--sm" href="<?= url('/my/reuse/' . $item['id'] . '/requests') ?>">
                  <?= (int) $item['pending_requests'] ?> pending
                </a>
              <?php elseif ((int) $item['total_requests'] > 0): ?>
                <a class="link-muted" href="<?= url('/my/reuse/' . $item['id'] . '/requests') ?>">
                  <?= (int) $item['total_requests'] ?> answered
                </a>
              <?php else: ?>
                <a class="link-muted" href="<?= url('/my/reuse/' . $item['id'] . '/requests') ?>">None</a>
              <?php endif; ?>
            </td>
            <td><?= e(format_date($item['created_at'])) ?></td>
            <td>
              <div class="row-actions">
                <?php if ($item['deleted_at'] !== null): ?>
                  <form method="post" action="<?= url('/my/reuse/' . $item['id'] . '/relist') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn btn--outline btn--sm"
                            data-confirm="Put &quot;<?= e($item['title']) ?>&quot; back on the browse page?">
                      Relist
                    </button>
                  </form>
                <?php else: ?>
                  <a class="btn btn--outline btn--sm" href="<?= url('/my/reuse/' . $item['id'] . '/edit') ?>">Edit</a>
                  <?php if ($item['status'] !== ITEM_DONATED): ?>
                    <?php /* A donated listing is a completed record - withdrawing it
                             would overwrite that with "removed" and lose the count. */ ?>
                    <form method="post" action="<?= url('/my/reuse/' . $item['id']) ?>" class="inline">
                      <?= csrf_field() ?><?= method_field('DELETE') ?>
                      <button class="btn btn--danger btn--sm"
                              data-confirm="Withdraw &quot;<?= e($item['title']) ?>&quot;? It will no longer be listed.">
                        Withdraw
                      </button>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
