<?php
/**
 * Phân trang: trang 1 dùng URL sạch, các trang sau dùng ?trang=N.
 *
 * @var string $basePath
 * @var int $page
 * @var int $pages
 */
if ($pages <= 1) {
    return;
}
$href = static fn (int $n): string => url($basePath) . ($n > 1 ? '?trang=' . $n : '');
?>
<nav class="pagination" aria-label="Phân trang">
  <?php if ($page > 1): ?><a href="<?= e($href($page - 1)) ?>" rel="prev">← Trước</a><?php endif; ?>
  <?php for ($n = 1; $n <= $pages; $n++): ?>
    <?php if ($n === $page): ?>
      <span aria-current="page"><?= $n ?></span>
    <?php else: ?>
      <a href="<?= e($href($n)) ?>"><?= $n ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  <?php if ($page < $pages): ?><a href="<?= e($href($page + 1)) ?>" rel="next">Sau →</a><?php endif; ?>
</nav>
