<?php
/**
 * Breadcrumb hiển thị. Phần tử cuối là trang hiện tại (không có url).
 * Bước 4 sinh JSON-LD BreadcrumbList từ cùng mảng $items.
 *
 * @var list<array{label: string, url?: string}> $items
 */
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <ol>
    <?php foreach ($items as $i => $item): ?>
      <?php if ($i === array_key_last($items) || empty($item['url'])): ?>
        <li aria-current="page"><?= e($item['label']) ?></li>
      <?php else: ?>
        <li><a href="<?= e(url($item['url'])) ?>"><?= e($item['label']) ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>
