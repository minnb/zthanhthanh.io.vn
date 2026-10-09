<?php
/**
 * Dòng thời gian dạng dải phim: dọc trên mobile, lưới ngang có lỗ phim trên desktop.
 *
 * @var list<array{label: string, title: string, description: ?string, is_highlight: int|string}> $milestones
 */
if ($milestones === []) {
    return;
}
?>
<div class="timeline">
  <div class="timeline__strip timeline__strip--top" aria-hidden="true"></div>
  <ol class="timeline__list">
    <?php foreach ($milestones as $m): ?>
      <li class="timeline__item<?= (int) $m['is_highlight'] === 1 ? ' timeline__item--highlight' : '' ?>">
        <span class="timeline__dot" aria-hidden="true"></span>
        <p class="timeline__label"><?= e($m['label']) ?></p>
        <p class="timeline__title"><?= e($m['title']) ?></p>
        <?php if (!empty($m['description'])): ?><p class="timeline__desc"><?= e($m['description']) ?></p><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
  <div class="timeline__strip timeline__strip--bottom" aria-hidden="true"></div>
</div>
