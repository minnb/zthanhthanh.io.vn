<?php
/**
 * 3 bài Hậu trường mới nhất. Ẩn khi chưa có bài (bài viết có từ Bước 3/6).
 *
 * @var list<array<string, mixed>> $latestPosts
 */
if ($latestPosts === []) {
    return;
}
?>
<section class="section section--tint" aria-labelledby="latest-posts">
  <div class="container">
    <div class="section__head">
      <div>
        <p class="eyebrow">#hautruongbienkich</p>
        <h2 class="h-2 mt-2" id="latest-posts">Hậu trường biên kịch</h2>
      </div>
      <a class="section__more" href="<?= e(url('/hau-truong-bien-kich')) ?>">Xem tất cả →</a>
    </div>
    <div class="grid grid--3">
      <?php foreach ($latestPosts as $i => $post): ?>
        <?= partial('partials/post-card', ['post' => $post, 'index' => $i + 1]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
