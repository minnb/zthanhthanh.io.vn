<?php
/**
 * Thanh chuyên mục (link thật, không cần JS): Tất cả · từng chuyên mục kèm số bài.
 *
 * @var list<array{slug: string, name: string, total: int}> $categories
 * @var int $allTotal
 * @var string $activePath  "/bai-viet" hoặc "/{slug}" của trang đang xem; trang tag truyền chuỗi rỗng
 */
if (empty($categories)) {
    return;
}
$chip = static function (string $path, string $label, int $count) use ($activePath): string {
    return '<a class="chip" href="' . e(url($path)) . '"' . ($path === $activePath ? ' aria-current="page"' : '') . '>'
        . e($label) . '<small>' . $count . '</small></a>';
};
?>
<nav class="chips" aria-label="Chuyên mục">
  <?= $chip('/bai-viet', 'Tất cả', (int) $allTotal) ?>
  <?php foreach ($categories as $cat): ?>
    <?= $chip('/' . $cat['slug'], (string) $cat['name'], (int) $cat['total']) ?>
  <?php endforeach; ?>
</nav>
