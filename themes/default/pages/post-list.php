<?php
/**
 * Danh sách bài (theo danh mục hoặc tag).
 *
 * @var string $heading
 * @var string $intro
 * @var string $eyebrow
 * @var string $basePath
 * @var array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int} $result
 * @var list<array{label: string, url?: string}> $breadcrumb
 */
$offset = ($result['page'] - 1) * $result['per_page'];
?>
<header class="container page-head">
  <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
  <p class="eyebrow"><?= e($eyebrow) ?></p>
  <h1 class="h-1"><?= e($heading) ?><?= $result['page'] > 1 ? ' <span class="muted">– Trang ' . (int) $result['page'] . '</span>' : '' ?></h1>
  <?php if ($intro !== ''): ?><p class="lead"><?= e($intro) ?></p><?php endif; ?>
</header>

<section class="container section" aria-label="Danh sách bài viết">
  <?php if ($result['items'] === []): ?>
    <p class="empty-state">Chưa có bài viết nào ở đây. Mình đang viết, bạn quay lại sau nhé.</p>
  <?php else: ?>
    <div class="grid grid--3">
      <?php foreach ($result['items'] as $i => $post): ?>
        <?= partial('partials/post-card', ['post' => $post, 'index' => $offset + $i + 1, 'headingTag' => 'h2']) ?>
      <?php endforeach; ?>
    </div>
    <?= partial('partials/pagination', ['basePath' => $basePath, 'page' => $result['page'], 'pages' => $result['pages']]) ?>
  <?php endif; ?>
</section>
