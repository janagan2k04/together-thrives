<?php
/**
 * Edit Listing. No Figma frame exists for editing, so it reuses the
 * "Donate an Item" form partial with the listing loaded into it.
 */
require_once APP_PATH . '/views/partials/icons.php';
?>

<div class="reuse-page">
  <div class="page-header">
    <div>
      <h1>Edit Listing</h1>
      <p class="page-header__sub">Update the details of “<?= e($item['title']) ?>”.</p>
    </div>
    <div class="page-header__spacer"></div>
    <a class="btn btn--outline" href="<?= url('/reuse/' . $item['id']) ?>">View listing</a>
  </div>

  <form class="reuse-form-grid" method="post" action="<?= url('/my/reuse/' . $item['id']) ?>"
        enctype="multipart/form-data">
    <?= csrf_field() ?><?= method_field('PUT') ?>
    <?php require APP_PATH . '/views/reuse/_form.php'; ?>
    <?php require APP_PATH . '/views/reuse/_guidelines.php'; ?>
  </form>
</div>
