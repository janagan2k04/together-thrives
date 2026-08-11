<?php
/** "1 · Item detail" frame from UI/Reuse/Reuse.svg. Open to guests. */
require_once APP_PATH . '/views/partials/icons.php';

$conditions   = \Models\Reuse\ReuseItem::CONDITIONS;
$statuses     = \Models\Reuse\ReuseItem::STATUSES;
$requestStates = \Models\Reuse\ItemRequest::STATUSES;

$cover     = $images[0] ?? null;
$available = $item['status'] === ITEM_AVAILABLE;
$initials  = mb_strtoupper(mb_substr($item['donor_name'], 0, 1));
?>

<div class="reuse-page">

  <a class="item-back" href="<?= url('/reuse') ?>"><?= icon('arrow-left') ?> Back to Reuse Items</a>

  <div class="item-detail">

    <div class="item-gallery">
      <div class="item-gallery__main">
        <?php if ($cover): ?>
          <img id="item-photo" src="<?= url('/reuse/image/' . $cover['id']) ?>" alt="<?= e($item['title']) ?>">
        <?php else: ?>
          <?= icon('gift') ?>
        <?php endif; ?>
        <span class="item-status item-status--<?= e($item['status']) ?>">
          <?= e($statuses[$item['status']] ?? $item['status']) ?>
        </span>
      </div>

      <?php if (count($images) > 1): ?>
        <div class="item-gallery__thumbs">
          <?php foreach ($images as $i => $img): ?>
            <img src="<?= url('/reuse/image/' . $img['id']) ?>" alt=""
                 class="<?= $i === 0 ? 'is-active' : '' ?>" data-photo>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <h1 class="item-detail__title"><?= e($item['title']) ?></h1>

      <div class="item-detail__tags">
        <span class="badge badge--neutral"><?= e($item['category']) ?></span>
        <span class="badge badge--success">
          <?= e($conditions[$item['item_condition']] ?? $item['item_condition']) ?> condition
        </span>
      </div>

      <div class="item-detail__where">
        <span><?= icon('map-pin') ?><?= e($item['city']) ?>, <?= e($item['district']) ?></span>
        <span><?= icon('clock') ?>Posted <?= e(time_ago($item['created_at'])) ?></span>
      </div>

      <h2>Description</h2>
      <p class="item-detail__desc"><?= e($item['description']) ?></p>

      <dl class="spec-grid">
        <div><dt>Category</dt><dd><?= e($item['category']) ?></dd></div>
        <div><dt>Condition</dt><dd><?= e($conditions[$item['item_condition']] ?? $item['item_condition']) ?></dd></div>
        <div><dt>Quantity</dt><dd><?= (int) $item['quantity'] ?></dd></div>
        <div><dt>Availability</dt><dd><?= e($statuses[$item['status']] ?? $item['status']) ?></dd></div>
      </dl>

      <?php if (!empty($item['pickup_note'])): ?>
        <p class="item-cta__hint"><?= icon('map-pin') ?> Pickup: <?= e($item['pickup_note']) ?></p>
      <?php endif; ?>

      <div class="item-cta">
        <?php if ($isOwner): ?>
          <div class="row-actions">
            <a class="btn btn--outline" href="<?= url('/my/reuse/' . $item['id'] . '/edit') ?>">Edit listing</a>
            <a class="btn btn--primary" href="<?= url('/my/reuse/' . $item['id'] . '/requests') ?>">
              View requests
            </a>
          </div>
          <p class="item-cta__note"><?= icon('help-circle') ?> This is your own listing.</p>

        <?php elseif ($myRequest): ?>
          <div class="item-cta__sent">
            <?= icon('check-circle') ?>
            <div>
              <strong>Request <?= e(strtolower($requestStates[$myRequest['status']] ?? $myRequest['status'])) ?></strong>
              <p>
                <?= $myRequest['status'] === 'pending'
                      ? 'The donor will review your request and respond.'
                      : 'The donor has responded to your request.' ?>
              </p>
            </div>
          </div>

        <?php elseif (!is_logged_in()): ?>
          <p class="item-cta__hint">Sign in to ask the donor for this item.</p>
          <a class="btn btn--primary btn--lg btn--block" href="<?= url('/login') ?>">Sign in to request</a>

        <?php elseif (!$available): ?>
          <button class="btn btn--primary btn--lg btn--block" disabled>
            <?= e($statuses[$item['status']] ?? $item['status']) ?>
          </button>
          <p class="item-cta__note"><?= icon('help-circle') ?> This item is no longer open to requests.</p>

        <?php else: ?>
          <p class="item-cta__hint">Disaster-affected? You can attach your GS letter in the next step.</p>
          <button class="btn btn--primary btn--lg btn--block" data-open-request>
            <?= icon('gift') ?> Request this item
          </button>
          <p class="item-cta__note">
            <?= icon('help-circle') ?>
            One request per item. A short cooldown applies after you receive a donation.
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="donor-card">
    <span class="donor-card__avatar"><?= e($initials) ?></span>
    <div>
      <div class="donor-card__name">
        <?= e($item['donor_name']) ?>
        <?php if ($item['donor_status'] === STATUS_ACTIVE): ?>
          <span class="donor-card__verified"><?= icon('shield-check') ?> Verified donor</span>
        <?php endif; ?>
      </div>
      <div class="donor-card__meta">
        <?= (int) $item['donor_donated_count'] ?> item<?= (int) $item['donor_donated_count'] === 1 ? '' : 's' ?> donated
        &nbsp;·&nbsp; Member since <?= e(format_date($item['donor_joined'], 'Y')) ?>
      </div>
    </div>
    <div class="page-header__spacer"></div>
    <a class="btn btn--outline" href="<?= url('/reuse/donor/' . $item['donor_id']) ?>">View Profile</a>
  </div>

  <?php if ($similar): ?>
    <h2 class="reuse-section">Similar items</h2>
    <div class="item-grid">
      <?php
        // _card.php reads $item, so park this page's listing and put it back —
        // the request modal below still needs it.
        $listing = $item;
        foreach ($similar as $item) {
            require APP_PATH . '/views/reuse/_card.php';
        }
        $item = $listing;
      ?>
    </div>
  <?php endif; ?>
</div>

<?php if (!$isOwner && !$myRequest && is_logged_in() && $available): ?>
  <?php require APP_PATH . '/views/reuse/_request-modal.php'; ?>
<?php endif; ?>

<script>
  // Gallery: clicking a thumbnail swaps the main photo.
  document.querySelectorAll('[data-photo]').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      var main = document.getElementById('item-photo');
      if (!main) return;
      main.src = thumb.src;
      document.querySelectorAll('[data-photo]').forEach(function (t) { t.classList.remove('is-active'); });
      thumb.classList.add('is-active');
    });
  });
</script>
