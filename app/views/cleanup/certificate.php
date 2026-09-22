<?php
/**
 * "Certificate - Volunteer Appreciation (A4 Landscape)" from Cleanup-User.svg.
 *
 * Rendered on the `blank` layout - a document, not a screen. The sheet reuses
 * the .cert* block from pages/blood.css; .cert--cleanup in pages/cleanup.css
 * swaps the accents to green and splits the fact strip into three columns. The
 * buttons above the sheet are hidden by blood.css's @media print block, so
 * Ctrl+P gives the sheet alone.
 */
require_once APP_PATH . '/views/partials/icons.php';

$c = $certificate;
?>

<div class="cert-page">

  <div class="cert-actions">
    <a class="btn btn--outline" href="<?= url('/my/activities') ?>">
      <?= icon('arrow-left') ?>Back to My Activities
    </a>
    <button class="btn btn--primary" type="button" onclick="window.print()">
      <?= icon('download') ?>Print or Save as PDF
    </button>
  </div>

  <article class="cert cert--cleanup">
    <header class="cert__band">
      <p class="cert__brand">Together<span>Thrives</span></p>
      <p class="cert__tagline">Centralised Disaster Response &amp; Community Support System</p>
      <span class="cert__seal"><?= icon('shield-check') ?></span>
      <h1 class="cert__title">Certificate of Appreciation</h1>
    </header>

    <div class="cert__body">
      <p class="cert__lead">This certificate is proudly presented to</p>
      <p class="cert__name"><?= e($c['volunteer']) ?></p>

      <p class="cert__for">
        in recognition of outstanding volunteer service, dedication and valuable contribution to
      </p>
      <p class="cert__camp"><?= e($c['event']) ?></p>
      <p class="cert__where">
        organised by <?= e($c['organiser']) ?> · <?= e($c['where']) ?> · <?= e($c['date']) ?>
      </p>

      <div class="cert__facts">
        <div>
          <?= icon('clock') ?>
          <span><strong><?= e($c['hours']) ?></strong><small>Time contributed</small></span>
        </div>
        <div>
          <?= icon('leaf') ?>
          <span><strong><?= e($c['waste']) ?></strong><small>Waste collected</small></span>
        </div>
        <div>
          <?= icon('users') ?>
          <span><strong><?= e($c['volunteers']) ?></strong><small>Volunteers on site</small></span>
        </div>
      </div>

      <p class="cert__id cert__id--foot">
        <?= icon('shield-check') ?>
        Certificate ID <strong><?= e($c['id']) ?></strong> · Issued <?= e($c['issued']) ?>
      </p>
    </div>
  </article>

</div>
