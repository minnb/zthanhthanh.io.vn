<?php
/**
 * Dải thành tích.
 *
 * @var list<array{value: string, label: string}> $achievements
 */
if ($achievements === []) {
    return;
}
?>
<section class="stats" aria-label="Thành tích">
  <ul class="container stats__list">
    <?php foreach ($achievements as $a): ?>
      <li class="stats__item">
        <p class="stats__value"><?= e($a['value']) ?></p>
        <p class="stats__label"><?= e($a['label']) ?></p>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
