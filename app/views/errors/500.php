<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>500 - TogetherThrives</title>
  <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
</head>
<body class="error-body">
  <div class="error-card">
    <h1>500</h1>
    <p><?= e($message ?? '') ?: 'Something went wrong.' ?></p>
    <a class="btn btn--primary" href="<?= url('/') ?>">Back to home</a>
  </div>
</body>
</html>
