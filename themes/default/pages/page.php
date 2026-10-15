<?php
/**
 * Trang tĩnh chung (trang không có template riêng). Giới thiệu, Liên hệ dùng pages/about, pages/contact.
 *
 * @var array<string, mixed> $page  Có thêm: html (đã render an toàn)
 * @var list<array{label: string, url?: string}> $breadcrumb
 */
?>
<article>
  <header class="container article-head">
    <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
    <h1 class="h-1"><?= e($page['title']) ?></h1>
  </header>
  <div class="container section section--flush-top">
    <div class="prose"><?= $page['html'] ?></div>
  </div>
</article>
