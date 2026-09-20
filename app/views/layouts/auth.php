<?php
/**
 * Split auth shell - brand panel on the left, form on the right.
 * Used by sign-in and every registration form.
 *
 * The view supplies the left panel through view data:
 *   asideTitle       string  main heading
 *   asideAccent      string  second line, rendered in brand purple (optional)
 *   asideLede        string  supporting paragraph (optional)
 *   asideBenefits    array   [[icon, tint, title, description], …]
 *   demoAccounts     array   [[name, role, email, password], …] - sign-in only,
 *                            and it takes the place of asideBenefits rather than
 *                            sitting beside them; both pinned to the panel foot.
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
  <div class="auth-split">

    <aside class="auth-aside">
      <a class="auth-aside__logo" href="<?= url('/') ?>">Together<span>Thrives</span></a>

      <div class="auth-aside__head">
        <h1 class="auth-aside__title">
          <?= e($asideTitle ?? 'Welcome back!') ?>
          <?php if (!empty($asideAccent)): ?><em><?= e($asideAccent) ?></em><?php endif; ?>
          <?php if (!empty($asideTail)): ?><span><?= e($asideTail) ?></span><?php endif; ?>
        </h1>
        <?php if (!empty($asideLede)): ?>
          <p class="auth-aside__lede"><?= e($asideLede) ?></p>
        <?php endif; ?>
      </div>

      <?php if (!empty($demoAccounts)): ?>
        <?php /* Sign-in only. Demo scaffolding, so it is labelled as such and
                 disappears entirely when DEMO_ACCOUNTS=false in .env. */ ?>
        <div class="demo-accounts">
          <div class="demo-accounts__head">
            <strong>Demo accounts</strong>
            <span>Press <em>Use</em> to fill the sign-in form.</span>
          </div>

          <?php foreach ($demoAccounts as [$name, $roleLabel, $email, $password]): ?>
            <div class="demo-account">
              <div class="demo-account__who">
                <strong><?= e($name) ?></strong>
                <span class="badge badge--neutral"><?= e($roleLabel) ?></span>
              </div>
              <div class="demo-account__creds">
                <code><?= e($email) ?></code>
                <span>·</span>
                <code><?= e($password) ?></code>
              </div>
              <button type="button" class="btn btn--outline demo-account__use"
                      data-demo-fill
                      data-demo-email="<?= e($email) ?>"
                      data-demo-password="<?= e($password) ?>">Use</button>
            </div>
          <?php endforeach; ?>
        </div>

      <?php elseif (!empty($asideBenefits)): ?>
        <div class="auth-benefits">
          <?php foreach ($asideBenefits as [$ico, $tint, $bTitle, $bDesc]): ?>
            <div class="auth-benefit">
              <span class="auth-benefit__icon auth-benefit__icon--<?= e($tint) ?>"><?= icon($ico) ?></span>
              <div>
                <strong><?= e($bTitle) ?></strong>
                <p><?= e($bDesc) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </aside>

    <main class="auth-panel<?= !empty($panelTinted) ? ' auth-panel--tinted' : '' ?>">
      <div style="width:100%;max-width:<?= !empty($wideForm) ? '700' : '560' ?>px">
        <?php require APP_PATH . '/views/partials/flash.php'; ?>
        <?= $content ?>
      </div>
    </main>

  </div>
  <script src="<?= asset('js/core/app.js') ?>"></script>
</body>
</html>
