<aside class="sidebar">
  <nav>
    <?php foreach ($items as $item): ?>
      <?php if ($item === null): ?>
        <hr class="sidebar__divider">
      <?php else: [$href, $label] = $item; ?>
        <a class="sidebar__link<?= nav_active($href) ?>" href="<?= url($href) ?>">
          <span class="sidebar__label"><?= e($label) ?></span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
</aside>
