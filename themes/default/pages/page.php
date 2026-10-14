<?php
/**
 * Trang tĩnh (Giới thiệu, Liên hệ...).
 *
 * @var array<string, mixed> $page  Có thêm: html (đã render an toàn)
 * @var list<array{label: string, url?: string}> $breadcrumb
 */
?>
<article class="container article">
  <header class="article__head">
    <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
    <h1 class="h-1 mt-5"><?= e($page['title']) ?></h1>
  </header>
  <div class="prose article__body"><?= $page['html'] ?></div>
  <?php if ($page['slug'] === 'gioi-thieu'): ?>
    <div class="article__foot">
      <?= partial('partials/author-box') ?>
    </div>
  <?php endif; ?>
</article>
