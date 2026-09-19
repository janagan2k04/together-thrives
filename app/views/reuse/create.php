<?php
/** "Reuse - Donate an Item" frame from UI/Reuse/Reuse.svg. */
require_once APP_PATH . '/views/partials/icons.php';
?>

<div class="reuse-page">
  <div class="page-header">
    <div>
      <h1>Donate an Item</h1>
      <p class="page-header__sub">List a reusable item and give it a second life for someone in need.</p>
    </div>
  </div>

  <form class="reuse-form-grid" method="post" action="<?= url('/my/reuse') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php require APP_PATH . '/views/reuse/_form.php'; ?>
    <?php require APP_PATH . '/views/reuse/_guidelines.php'; ?>
  </form>
</div>
