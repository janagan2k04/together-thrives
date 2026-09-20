<div class="lang-switch">
  <?php foreach (['en' => 'EN', 'si' => 'සි', 'ta' => 'த'] as $code => $label): ?>
    <a href="<?= url('/language/' . $code) ?>"
       class="lang-switch__item<?= Core\Lang::locale() === $code ? ' is-active' : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>
