<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>403 - TogetherThrives</title>
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
</head>
<body class="error-body">
  <div class="error-card">
    <h1>403</h1>
    <p><?= e($message ?? '') ?: 'Something went wrong.' ?></p>
    <a class="btn btn--primary" href="<?= url('/') ?>">Back to home</a>
  </div>
</body>
</html>
