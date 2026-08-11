<?php if (!empty($flash)): ?>
  <div class="flash-stack">
    <?php foreach ($flash as $type => $messages): ?>
      <?php foreach ($messages as $message): ?>
        <div class="alert alert--<?= e($type) ?>">
          <span><?= e($message) ?></span>
          <button type="button" class="alert__close" data-dismiss>&times;</button>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
