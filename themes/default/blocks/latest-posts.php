<?php
/**
 * 3 bài mới nhất, bố cục tạp chí: 1 bài lớn + 2 bài ngang. Ẩn khi chưa có bài.
 *
 * @var list<array<string, mixed>> $latestPosts
 */
if ($latestPosts === []) {
    return;
}
?>
<section class="section" aria-labelledby="latest-posts">
  <div class="container">
    <div class="section-head">
      <div class="section-head__text">
        <p class="eyebrow">Hậu trường biên kịch</p>
        <h2 class="h-2" id="latest-posts">Bài viết mới nhất</h2>
      </div>
      <a class="text-link" href="<?= e(url('/bai-viet')) ?>">Xem tất cả bài viết <?= icon('arrow-r') ?></a>
    </div>
    <div class="mag">
      <?php foreach ($latestPosts as $i => $post): ?>
        <?= partial('partials/post-card', [
            'post' => $post,
            'variant' => $i === 0 ? 'feature' : 'row',
            'sizes' => $i === 0 ? '(min-width: 1024px) 680px, 100vw' : '(min-width: 1024px) 200px, (min-width: 640px) 40vw, 100vw',
        ]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
