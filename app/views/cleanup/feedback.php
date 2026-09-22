<?php
/**
 * "Cleanup : Volunteer Feedback Form" - frame 5 of Cleanup-User.svg.
 *
 * `event_feedback` stores one overall `rating`, one `safety_rating`, a comment
 * and an anonymity flag, so the form asks for exactly that. The five per-topic
 * stars the Figma drew have no column and are not asked for - a star that goes
 * nowhere is a question the volunteer answered for nothing.
 *
 * Re-opening the form after rating a campaign fills it in again: the unique key
 * on (event_id, user_id) means the second submission edits the first.
 */
require_once APP_PATH . '/views/partials/icons.php';

use Models\Cleanup\CleanupEvent;

$ev = $event;

/** Five stars, drawn high-to-low because .cl-rate is row-reverse. */
$stars = function (string $name, int $checked = 0) {
    $out = '<div class="cl-rate">';
    for ($i = 5; $i >= 1; $i--) {
        $id = $name . '_' . $i;
        $out .= '<input type="radio" id="' . e($id) . '" name="' . e($name) . '" value="' . $i . '"'
              . ($i === $checked ? ' checked' : '') . '>'
              . '<label for="' . e($id) . '" title="' . $i . ' of 5">★</label>';
    }
    return $out . '</div>';
};
?>

<a class="back-link" href="<?= url('/my/activities') ?>"><?= icon('arrow-left') ?>Back to My Activities</a>

<form method="post" action="<?= url('/cleanup/' . $ev['id'] . '/feedback') ?>">
  <?= csrf_field() ?>

  <div class="split">
    <div class="stack">

      <section class="panel">
        <div class="panel__body">
          <h1 style="font-size:var(--fs-xl);font-weight:700;color:var(--ink)">Volunteer Feedback</h1>
          <p class="muted" style="margin-top:4px">
            <?= e($ev['title']) ?> · <?= e($ev['organiser']) ?> · <?= e($ev['event_date']) ?> ·
            <?= e($ev['district']) ?>
          </p>
          <?php if ($existing): ?>
            <div class="cl-note cl-note--green" style="margin-top:var(--s-4)">
              You rated this campaign on <?= e(format_date($existing['created_at'])) ?>. Submitting
              again replaces that rating.
            </div>
          <?php else: ?>
            <div class="cl-note cl-note--green" style="margin-top:var(--s-4)">
              Tell the organiser how the day went. Your rating is shown on their public profile.
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><h2>Overall Experience</h2></div>
        <div class="panel__body">
          <div class="field <?= has_error('rating') ? 'field--invalid' : '' ?>">
            <?= $stars('rating', (int) old('rating', (string) ($existing['rating'] ?? 0))) ?>
            <span class="field__error"><?= error_for('rating') ?></span>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><h2>Safety</h2></div>
        <div class="panel__body">
          <div class="cl-rate-row <?= has_error('safety_rating') ? 'field--invalid' : '' ?>">
            <span>Safety guidelines &amp; briefing</span>
            <?= $stars('safety_rating', (int) old('safety_rating', (string) ($existing['safety_rating'] ?? 0))) ?>
          </div>
          <span class="field__error"><?= error_for('safety_rating') ?></span>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><h2>Comments &amp; Suggestions</h2></div>
        <div class="panel__body">
          <div class="field" style="margin-bottom:0">
            <textarea class="field__textarea" name="comment" maxlength="500"
                      placeholder="Tell the organiser what went well and what could be improved…"><?= old('comment', (string) ($existing['comment'] ?? '')) ?></textarea>
            <span class="field__hint">Up to 500 characters.</span>
          </div>

          <label class="cl-check" style="margin-top:var(--s-4)">
            <input type="checkbox" name="is_anonymous" value="1" <?= !empty($existing['is_anonymous']) ? 'checked' : '' ?>>
            Submit this feedback anonymously
          </label>
        </div>
      </section>
    </div>

    <div class="stack">
      <section class="panel">
        <div class="panel__head"><?= icon('leaf') ?><h2>Campaign</h2></div>
        <div class="panel__body">
          <div class="chip-row">
            <span class="cl-status cl-status--<?= e(CleanupEvent::statusTone($ev['status'])) ?>">
              <?= e(CleanupEvent::statusLabel($ev['status'])) ?>
            </span>
            <span class="cl-status cl-status--neutral"><?= e(CleanupEvent::categoryLabel($ev['category'])) ?></span>
          </div>
          <div class="mini-list">
            <div><span>Date</span><span><?= e($ev['event_date']) ?></span></div>
            <div><span>Time</span><span><?= e($ev['start_time']) ?> - <?= e($ev['end_time']) ?></span></div>
            <div><span>Meeting point</span><span><?= e($ev['meeting_point']) ?></span></div>
            <div><span>Volunteers attended</span><span><?= e($ev['checked_in']) ?></span></div>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel__head"><?= icon('shield-check') ?><h2>Your Certificate</h2></div>
        <div class="panel__body">
          <p class="muted">
            Your appreciation certificate for this campaign is ready to open, print or save.
          </p>
          <a class="btn btn--outline btn--block" style="margin-top:var(--s-3)"
             href="<?= url('/cleanup/certificate/' . $ev['id']) ?>">Open Certificate</a>
        </div>
      </section>
    </div>
  </div>

  <div class="cl-form-foot">
    <p>Feedback is shared with the organiser and shown on their public profile.</p>
    <a class="btn btn--outline" href="<?= url('/my/activities') ?>">Cancel</a>
    <button class="btn btn--primary" type="submit"><?= $existing ? 'Update Feedback' : 'Submit Feedback' ?></button>
  </div>
</form>
