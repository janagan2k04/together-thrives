<footer class="site-footer">
  <div class="site-footer__brand">
    <a class="logo logo--light" href="<?= url('/') ?>">Together<span>Thrives</span></a>
    <p>A centralized disaster response and community support platform for Sri Lanka.</p>
  </div>
  <div class="site-footer__bottom">
    <span>&copy; <?= date('Y') ?> TogetherThrives</span>
    <?php require APP_PATH . '/views/partials/language-switcher.php'; ?>
  </div>
</footer>
