<?php
/**
 * The donor's request queue for one listing. No Figma frame exists for this
 * screen — the design covers the requester's side — so it follows the table
 * pattern used elsewhere in the project.
 */
require_once APP_PATH . '/views/partials/icons.php';

$states   = \Models\Reuse\ItemRequest::STATUSES;
$statuses = \Models\Reuse\ReuseItem::STATUSES;
$selected = null;
foreach ($requests as $r) {
    if ($r['status'] === 'selected') { $selected = $r; break; }
}
?>

<div class="reuse-page">

  <a class="item-back" href="<?= url('/my/reuse') ?>"><?= icon('arrow-left') ?> Back to My Listings</a>

  <div class="page-header">
    <span class="cell-item__thumb">
      <?php if ($cover): ?>
        <img src="<?= url('/reuse/image/' . $cover['id']) ?>" alt="">
      <?php else: ?>
        <?= icon('gift') ?>
      <?php endif; ?>
    </span>
    <div>
      <h1><?= e($item['title']) ?></h1>
      <p class="page-header__sub">
        <?= count($requests) ?> request<?= count($requests) === 1 ? '' : 's' ?>
        &nbsp;·&nbsp;
        <span class="item-status item-status--<?= e($item['status']) ?>">
          <?= e($statuses[$item['status']] ?? $item['status']) ?>
        </span>
      </p>
    </div>
    <div class="page-header__spacer"></div>
    <a class="btn btn--outline" href="<?= url('/reuse/' . $item['id']) ?>">View listing</a>
  </div>

  <?php if ($selected && $item['status'] !== ITEM_DONATED): ?>
    <div class="card" style="margin-bottom: var(--s-5)">
      <strong><?= e($selected['requester_name']) ?> is collecting this item.</strong>
      <p class="page-header__sub">
        Once they have it, mark the donation as complete — that is what starts
        their fair-distribution cooldown.
      </p>
      <form method="post" action="<?= url('/my/reuse/requests/' . $selected['id'] . '/handover') ?>" class="form-actions">
        <?= csrf_field() ?>
        <button class="btn btn--primary" data-confirm="Confirm that <?= e($selected['requester_name']) ?> has received this item?">
          Mark as handed over
        </button>
      </form>
    </div>
  <?php endif; ?>

  <?php if (!$requests): ?>
    <div class="card empty-state">
      <strong>No requests yet</strong>
      <p>People browsing the reuse listings can ask you for this item.</p>
    </div>
  <?php else: ?>
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Requester</th><th>Message</th><th>Asked</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td>
              <div class="cell-item__title"><?= e($r['requester_name']) ?></div>
              <div class="link-muted"><?= e($r['requester_city'] ?: '—') ?></div>
              <div class="request-flags">
                <?php if ((int) $r['is_disaster_affected'] === 1): ?>
                  <?php // "Verified" is only claimable once an administrator has read the letter. ?>
                  <?php if (($r['gs_status'] ?? null) === MOD_APPROVED): ?>
                    <span class="badge badge--success">Verified disaster-affected</span>
                  <?php else: ?>
                    <span class="badge badge--pending">Disaster-affected (claimed)</span>
                  <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($r['gs_verification_id'])): ?>
                  <a class="badge badge--neutral" href="<?= url('/my/reuse/requests/' . $r['id'] . '/letter') ?>" target="_blank">
                    GS letter (<?= e($r['gs_status'] ?? MOD_PENDING) ?>)
                  </a>
                <?php endif; ?>
              </div>
            </td>
            <td><p class="request-reason"><?= e($r['reason'] ?: '—') ?></p></td>
            <td><?= e(time_ago($r['created_at'])) ?></td>
            <td>
              <span class="badge <?= $r['status'] === 'selected' ? 'badge--success' : ($r['status'] === 'pending' ? 'badge--pending' : 'badge--neutral') ?>">
                <?= e($states[$r['status']] ?? $r['status']) ?>
              </span>
            </td>
            <td>
              <?php if ($r['status'] === 'pending' && !$selected && $item['status'] === ITEM_AVAILABLE): ?>
                <div class="row-actions">
                  <form method="post" action="<?= url('/my/reuse/requests/' . $r['id'] . '/select') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn btn--primary btn--sm"
                            data-confirm="Give this item to <?= e($r['requester_name']) ?>? Everyone else will be told it has gone.">
                      Select
                    </button>
                  </form>
                  <form method="post" action="<?= url('/my/reuse/requests/' . $r['id'] . '/reject') ?>" class="inline">
                    <?= csrf_field() ?>
                    <button class="btn btn--danger btn--sm"
                            data-confirm="Turn down <?= e($r['requester_name']) ?>?">
                      Decline
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
