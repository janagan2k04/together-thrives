<header class="topbar topbar--guest">
  <a class="logo" href="<?= url('/') ?>">Together<span>Thrives</span></a>
  <span class="topbar__sep"></span>

  <nav class="topbar__nav">
    <?php /* Disaster reporting needs an account, so guests are sent to sign in. */ ?>
    <a href="<?= url('/login') ?>"><?= icon('help-circle') ?> Report</a>
    <a href="<?= url('/support') ?>"><?= icon('life-buoy') ?> Support</a>
  </nav>

  <div class="topbar__spacer"></div>

  <div class="topbar__right">
    <span class="topbar__place"><?= icon('map-pin') ?> Colombo, Sri Lanka</span>
    <span class="topbar__sep"></span>
    <a class="btn btn--outline" href="<?= url('/login') ?>">Sign In</a>
    <a class="btn btn--brand" href="<?= url('/register') ?>">Register</a>
  </div>
</header>
