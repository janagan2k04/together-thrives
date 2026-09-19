<?php
/**
 * "Certificate - Blood Donation Appreciation (A4 Landscape)" from Blood-User.svg.
 *
 * Rendered on the `blank` layout - this is a document, not a screen, so it has
 * no sidebar or topbar. The buttons above the sheet are hidden by the
 * `@media print` block in pages/blood.css, so Ctrl+P gives the sheet alone.
 */
require_once APP_PATH . '/views/partials/icons.php';

$c = $certificate;
?>

<div class="cert-page">

  <div class="cert-actions">
    <a class="btn btn--outline" href="<?= url('/blood?tab=donations') ?>">
      <?= icon('arrow-left') ?>Back to My Donations
    </a>
    <button class="btn btn--blood" type="button" onclick="window.print()">
      <?= icon('download') ?>Print or Save as PDF
    </button>
  </div>

  <article class="cert">
    <header class="cert__band">
      <p class="cert__brand">Together<span>Thrives</span></p>
      <p class="cert__tagline">Centralised Disaster Response &amp; Community Support System</p>
      <span class="cert__seal"><?= icon('shield-check') ?></span>
      <h1 class="cert__title">Certificate of Appreciation</h1>
    </header>

    <div class="cert__body">
      <p class="cert__lead">This certificate is proudly presented to</p>
      <p class="cert__name"><?= e($c['donor']) ?></p>

      <p class="cert__for">
        in recognition of a voluntary blood donation and the selfless gift of life given at
      </p>
      <p class="cert__camp"><?= e($c['camp']) ?></p>
      <p class="cert__where">organised by <?= e($c['organiser']) ?> · <?= e($c['date']) ?></p>

      <div class="cert__facts">
        <div>
          <?= icon('droplet') ?>
          <span><strong><?= e($c['units']) ?></strong><small>Blood donated</small></span>
        </div>
        <div>
          <?= icon('check-circle') ?>
          <span><strong><?= e($c['group']) ?></strong><small>Blood group</small></span>
        </div>
      </div>

      <p class="cert__id cert__id--foot">
        <?= icon('shield-check') ?>
        Certificate ID <strong><?= e($c['id']) ?></strong> · Issued <?= e($c['issued']) ?>
      </p>
    </div>
  </article>

</div>
