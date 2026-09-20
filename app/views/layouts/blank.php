<?php
/**
 * Bare shell - no topbar, no sidebar, no flash strip.
 *
 * For pages that are a document rather than a screen: the blood donation
 * appreciation certificate is the first, and any printable receipt or letter
 * that follows belongs here too. Everything inside is the view's own.
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
<body class="blank-body">
  <?= $content ?>
</body>
</html>
