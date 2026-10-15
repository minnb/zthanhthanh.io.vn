<?php
/**
 * Danh sách bài: tất cả (/bai-viet), theo danh mục hoặc theo tag. Thanh chuyên mục + lưới 3 cột + phân trang.
 *
 * @var string $heading
 * @var string $intro
 * @var string $eyebrow
 * @var string $basePath
 * @var array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int} $result
 * @var array{categories: list<array<string, mixed>>, allTotal: int, activePath: string}|null $filter
 * @var list<array{label: string, url?: string}> $breadcrumb
 */
?>
<header class="container page-head">
  <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
  <p class="eyebrow"><?= e($eyebrow) ?></p>
  <h1 class="h-1"><?= e($heading) ?><?= $result['page'] > 1 ? ' <span class="muted">– Trang ' . (int) $result['page'] . '</span>' : '' ?></h1>
  <?php if ($intro !== ''): ?><p class="lead"><?= e($intro) ?></p><?php endif; ?>
</header>

<section class="container section section--flush-top" aria-labelledby="post-list-title">
  <h2 class="sr-only" id="post-list-title">Danh sách bài viết</h2>
  <div class="toolbar">
    <?php if (!empty($filter)): ?><?= partial('partials/category-filter', $filter) ?><?php endif; ?>
    <p class="muted small"><?= (int) $result['total'] ?> bài viết<?= $result['pages'] > 1 ? ' · Trang ' . (int) $result['page'] . '/' . (int) $result['pages'] : '' ?></p>
  </div>
  <?php if ($result['items'] === []): ?>
    <p class="empty-state">Chưa có bài viết nào ở đây. Mình đang viết, bạn quay lại sau nhé.</p>
  <?php else: ?>
    <ul class="grid grid--3" role="list">
      <?php foreach ($result['items'] as $post): ?>
        <li><?= partial('partials/post-card', ['post' => $post]) ?></li>
      <?php endforeach; ?>
    </ul>
    <?= partial('partials/pagination', ['basePath' => $basePath, 'page' => $result['page'], 'pages' => $result['pages']]) ?>
  <?php endif; ?>
</section>
