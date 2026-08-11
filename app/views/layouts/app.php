<?php use Core\Auth; ?>
<?php require_once APP_PATH . '/views/partials/icons.php'; ?>
<!DOCTYPE html>
<html lang="<?= e(Core\Lang::locale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'TogetherThrives') ?></title>
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
</head>
<body class="app-body">

  <?php
    // The admin area has its own topbar — wider search, unread counts, no public nav.
    $topbar = Auth::role() === ROLE_ADMIN ? 'topbar-admin' : 'topbar-user';
    require APP_PATH . "/views/partials/{$topbar}.php";
  ?>

  <div class="app-shell">
    <?php
      $sidebar = match (Auth::role()) {
          ROLE_ADMIN        => 'sidebar-admin',
          ROLE_ORGANIZATION, ROLE_BLOOD_BANK => 'sidebar-org',
          ROLE_GN           => 'sidebar-gn',
          default           => 'sidebar-community',
      };
      require APP_PATH . "/views/partials/{$sidebar}.php";
    ?>

    <main class="app-main<?= Auth::role() === ROLE_ADMIN ? ' admin-main' : '' ?>">
      <?php require APP_PATH . '/views/partials/flash.php'; ?>
      <?= $content ?>
    </main>
  </div>

  <script src="<?= asset('js/core/app.js') ?>"></script>
</body>
</html>
