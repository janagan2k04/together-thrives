<?php require_once APP_PATH . '/views/partials/icons.php'; ?>
<!DOCTYPE html>
<html lang="<?= e(Core\Lang::locale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'TogetherThrives') ?></title>
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
</head>
<body class="guest-body">
  <?php require APP_PATH . '/views/partials/topbar-guest.php'; ?>
  <main class="guest-main">
    <?php require APP_PATH . '/views/partials/flash.php'; ?>
    <?= $content ?>
  </main>
  <?php require APP_PATH . '/views/partials/footer.php'; ?>
  <script src="<?= asset('js/core/app.js') ?>"></script>
</body>
</html>
