<?php
/** Confirmation screen after the sandbox checkout. */
require_once APP_PATH . '/views/partials/icons.php';
?>

<div class="page-head">
  <div class="page-head__text">
    <h1>Payment receipt</h1>
    <p>Thank you for supporting TogetherThrives.</p>
  </div>
</div>

<section class="panel" style="max-width:640px">
  <div class="panel__body">
    <div class="admin-empty">
      <span class="admin-empty__icon"><?= icon('check-circle') ?></span>
      <strong>Sandbox payment accepted</strong>
      <p>No card was charged and no card details were stored.</p>
    </div>

    <dl class="kv" style="margin-top:var(--s-5)">
      <div><dt>Reference</dt><dd><?= e($ref) ?></dd></div>
      <div><dt>Amount</dt><dd><?= e(format_money($amount)) ?></dd></div>
      <div><dt>Date</dt><dd><?= format_date(date('Y-m-d H:i:s'), 'd M Y, H:i') ?></dd></div>
      <div><dt>Gateway</dt><dd>PayHere sandbox</dd></div>
    </dl>

    <div class="page-head__actions" style="margin-top:var(--s-5)">
      <a class="btn btn--outline" href="<?= url('/support') ?>">Back to Support</a>
      <a class="btn btn--primary" href="<?= url(Core\Auth::homeFor()) ?>">Go to dashboard</a>
    </div>
  </div>
</section>
