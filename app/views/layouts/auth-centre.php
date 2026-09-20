<?php
/**
 * Centred auth shell - a single card on a tinted page.
 * Used by forgot/reset password and the registration-submitted screen.
 *
 * View data:
 *   hideLogo   bool  suppress the wordmark above the card
 *   wideCard   bool  use the wider container (success screen)
 */
require_once APP_PATH . '/views/partials/icons.php';
?>
<!DOCTYPE html>
<html lang="<?= e(Core\Lang::locale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'TogetherThrives') ?></title>
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
</head>
<body>
  <main class="auth-centre<?= !empty($wideCard) ? ' auth-centre--wide' : '' ?>">
    <div class="auth-centre__inner">
      <?php if (empty($hideLogo)): ?>
        <a class="auth-centre__logo" href="<?= url('/') ?>">Together<span>Thrives</span></a>
      <?php endif; ?>
      <?php require APP_PATH . '/views/partials/flash.php'; ?>
      <?= $content ?>
    </div>
  </main>
  <script src="<?= asset('js/core/app.js') ?>"></script>
</body>
</html>
