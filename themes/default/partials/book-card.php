<?php
/**
 * Partial: Góc Bàn Đọc (The Writer's Bookshelf / Book Card)
 * Hiển thị ở cuối bài viết để kết nối sách nuôi dưỡng trang viết & link Affiliate tinh tế.
 *
 * @var list<array<string, mixed>> $books
 */
if (empty($books)) {
    return;
}
?>
<section class="book-card-section" aria-label="Góc bàn đọc của Thanh Thanh">
  <div class="book-card-section__header">
    <span class="book-card-section__badge" aria-hidden="true">📖 Góc bàn đọc</span>
    <h3 class="book-card-section__title">Sách nuôi dưỡng trang viết này</h3>
    <p class="book-card-section__subtitle">Những cuốn sách đã tiếp thêm chất liệu, cảm hứng và vốn sống cho bài viết của Thanh Thanh.</p>
  </div>

  <div class="book-card-grid">
    <?php foreach ($books as $book): ?>
      <div class="book-card">
        <div class="book-card__visual">
          <?php if (!empty($book['cover'])): ?>
            <div class="book-card__cover-wrap">
              <?= partial('partials/image', [
                  'media' => $book['cover'],
                  'ratio' => '3/4',
                  'eager' => false,
                  'sizes' => '(min-width: 640px) 160px, 120px'
              ]) ?>
              <div class="book-card__spine" aria-hidden="true"></div>
            </div>
          <?php else: ?>
            <div class="book-card__spine-mockup" aria-hidden="true">
              <span class="book-card__mockup-title"><?= e($book['title']) ?></span>
              <span class="book-card__mockup-author"><?= e($book['author']) ?></span>
            </div>
          <?php endif; ?>
        </div>

        <div class="book-card__content">
          <h4 class="book-card__name"><?= e($book['title']) ?></h4>
          <p class="book-card__author">Tác giả: <strong><?= e($book['author']) ?></strong></p>

          <?php if (!empty($book['note'])): ?>
            <blockquote class="book-card__note">
              <p>“<?= e($book['note']) ?>”</p>
              <cite>— Lời bình từ Thanh Thanh</cite>
            </blockquote>
          <?php endif; ?>

          <?php if (!empty($book['affiliate_url'])): ?>
            <div class="book-card__action">
              <a href="<?= e($book['affiliate_url']) ?>" target="_blank" rel="noopener sponsored" class="btn btn--sm btn--book">
                <span>Tìm đọc tác phẩm này</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
              </a>
              <span class="book-card__disclaimer">☕ Khi mua sách qua liên kết này, bạn đang gửi một tách trà ấm tiếp sức cho những trang kịch bản tiếp theo của Thanh Thanh.</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
