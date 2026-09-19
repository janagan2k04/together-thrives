<?php
/**
 * "Blood Donation Form - Submitted" from Blood-User.svg.
 *
 * The eligibility verdict itself arrives as the flash message the layout
 * already renders above this card - the controller scores the answers the way
 * `screening_responses.result` will.
 */
require_once APP_PATH . '/views/partials/icons.php';

$next = [
    ['file-text', 'What happens next?', 'Our team will review your answers carefully.'],
    ['bell',      'You will be notified', "You'll get a notification within 1-2 business days."],
    ['check-circle', 'Thank you!',        'Your willingness to donate can save many lives.'],
];
?>

<div class="done-card">
  <div class="done-card__tick">
    <span class="inner"><?= icon('check') ?></span>
  </div>

  <h1>Form Submitted Successfully!</h1>
  <p>Thank you for completing the blood donation screening form. Your information has been saved securely.</p>

  <div class="done-next">
    <?php foreach ($next as [$ico, $head, $body]): ?>
      <div>
        <span class="done-next__ico"><?= icon($ico) ?></span>
        <span>
          <strong><?= e($head) ?></strong>
          <small><?= e($body) ?></small>
        </span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="done-card__actions">
    <a class="btn btn--outline" href="<?= url('/blood') ?>"><?= icon('arrow-left') ?>Back to Blood Donation</a>
    <a class="btn btn--blood" href="<?= url(Core\Auth::homeFor()) ?>"><?= icon('grid') ?>Go to Dashboard</a>
  </div>

</div>
