<?php
/**
 * Dòng thời gian làm phim: dọc trên mobile, hàng ngang (4 rồi 8 cột) trên màn rộng.
 *
 * @var list<array{label: string, title: string, description: ?string, is_highlight: int|string}> $milestones
 * @var bool|null $compact  true = luôn xếp dọc (cột bên của Góc sáng tác)
 */
if ($milestones === []) {
    return;
}
?>
<ol class="timeline<?= empty($compact) ? ' timeline--wide' : '' ?>">
  <?php foreach ($milestones as $m): ?>
    <li class="timeline__item<?= (int) $m['is_highlight'] === 1 ? ' timeline__item--highlight' : '' ?>">
      <p class="label timeline__label"><?= e($m['label']) ?></p>
      <p class="timeline__title"><?= e($m['title']) ?></p>
      <?php if (!empty($m['description'])): ?><p class="timeline__desc"><?= e($m['description']) ?></p><?php endif; ?>
    </li>
  <?php endforeach; ?>
</ol>
