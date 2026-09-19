<?php
/**
 * "2 · Request item - GS letter (disaster-affected)" frame from
 * UI/Reuse/Reuse.svg. Rendered only for a signed-in non-owner who has not
 * already asked for this item - the server checks all of that again on POST.
 */
$cover = $images[0] ?? null;

// A rejected request bounces back to this page, so reopen the dialog rather
// than flashing errors onto a form nobody can see.
$reopen = has_error('reason') || has_error('gs_letter');
?>
<div class="modal-backdrop" id="request-modal" <?= $reopen ? '' : 'hidden' ?>>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="request-modal-title">
    <form method="post" action="<?= url('/reuse/' . $item['id'] . '/request') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="modal__head">
        <h2 id="request-modal-title">Request this item</h2>
        <button class="modal__close" type="button" data-close-request aria-label="Close">&times;</button>
      </div>

      <div class="modal__body">
        <div class="modal__item">
          <span class="modal__item-thumb">
            <?php if ($cover): ?>
              <img src="<?= url('/reuse/image/' . $cover['id']) ?>" alt="">
            <?php else: ?>
              <?= icon('gift') ?>
            <?php endif; ?>
          </span>
          <div>
            <strong><?= e($item['title']) ?></strong>
            <div class="item-detail__tags">
              <span class="badge badge--neutral"><?= e($item['category']) ?></span>
              <span class="badge badge--success">
                <?= e(\Models\Reuse\ReuseItem::CONDITIONS[$item['item_condition']] ?? $item['item_condition']) ?>
              </span>
            </div>
          </div>
        </div>

        <div class="field <?= has_error('reason') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="reason">Message to donor <span class="field__hint">(optional)</span></label>
          <textarea class="field__textarea" id="reason" name="reason" maxlength="1000"
                    placeholder="Hi! I'd really value this item for my family. I can pick it up this weekend."><?= old('reason') ?></textarea>
          <span class="field__error"><?= error_for('reason') ?></span>
        </div>

        <div class="disaster-box">
          <div class="disaster-box__row">
            <input type="checkbox" id="is_disaster_affected" name="is_disaster_affected" value="1"
                   <?= old('is_disaster_affected') ? 'checked' : '' ?>>
            <div>
              <label for="is_disaster_affected">
                <strong>I'm affected by a disaster and need priority</strong>
              </label>
              <p>
                Verified disaster-affected requests are given priority under our
                fair distribution rules.
              </p>
              <div class="disaster-box__upload">
                <label class="field__label" for="gs_letter">
                  <?= icon('upload-cloud') ?> Upload Grama Niladhari (GS) verification letter
                </label>
                <input type="file" id="gs_letter" name="gs_letter" accept="application/pdf,image/png,image/jpeg">
                <span class="field__hint">PDF, PNG or JPG · max 5MB. An administrator checks it before priority applies.</span>
                <span class="field__error"><?= error_for('gs_letter') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal__foot">
        <button class="btn btn--outline" type="button" data-close-request>Cancel</button>
        <button class="btn btn--primary" type="submit">Send request</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Open and close the request dialog.
  (function () {
    var modal = document.getElementById('request-modal');
    if (!modal) return;

    document.querySelectorAll('[data-open-request]').forEach(function (btn) {
      btn.addEventListener('click', function () { modal.hidden = false; });
    });
    document.querySelectorAll('[data-close-request]').forEach(function (btn) {
      btn.addEventListener('click', function () { modal.hidden = true; });
    });
    modal.addEventListener('click', function (event) {
      if (event.target === modal) modal.hidden = true;
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') modal.hidden = true;
    });
  })();
</script>
