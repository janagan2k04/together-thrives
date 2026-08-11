<section class="section section--tint">
  <div class="wrap">
    <div class="section__head">
      <span class="eyebrow">Create an account</span>
      <h2>Choose the role that fits you</h2>
      <p>Each account type unlocks a different set of functions across the four modules.</p>
    </div>

    <?php require APP_PATH . '/views/partials/role-cards.php'; ?>

    <p style="margin-top:var(--s-10);text-align:center;color:var(--muted)">
      Already have an account?
      <a class="auth-link" href="<?= url('/login') ?>">Sign in</a>
    </p>
  </div>
</section>
