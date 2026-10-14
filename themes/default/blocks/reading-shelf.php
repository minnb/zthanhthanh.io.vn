<?php
/**
 * Khối trang chủ: Tủ sách gối đầu giường của Biên kịch (Reading Shelf)
 *
 * @var list<array<string, mixed>> $featuredBooks
 */
if (empty($featuredBooks)) {
    return;
}
?>
<section class="section section--reading-shelf" aria-labelledby="reading-shelf-title">
  <div class="container">
    <div class="section-head text-center">
      <p class="eyebrow">Tủ Sách & Vốn Sống</p>
      <h2 class="h-2" id="reading-shelf-title">Sách gối đầu giường của Biên kịch</h2>
      <p class="section-head__lead">Những cuốn sách đã nuôi dưỡng tư duy kể chuyện điện ảnh, tiếp thêm chất liệu sống và đồng hành cùng Thanh Thanh qua những tháng năm nghịch cảnh.</p>
    </div>

    <div class="shelf-grid mt-6">
      <?php
      $displayBooks = isset($shelfLimit) ? array_slice($featuredBooks, 0, (int) $shelfLimit) : $featuredBooks;
      foreach ($displayBooks as $book):
      ?>
        <article class="shelf-item">
          <div class="shelf-item__cover">
            <?php if (!empty($book['cover'])): ?>
              <div class="shelf-item__book-3d">
                <?= partial('partials/image', [
                    'media' => $book['cover'],
                    'ratio' => '3/4',
                    'eager' => false,
                    'sizes' => '(min-width: 960px) 180px, 140px'
                ]) ?>
                <div class="shelf-item__spine" aria-hidden="true"></div>
              </div>
            <?php else: ?>
              <div class="shelf-item__spine-mockup" aria-hidden="true">
                <span class="shelf-item__mockup-title"><?= e($book['title']) ?></span>
                <span class="shelf-item__mockup-author"><?= e($book['author']) ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div class="shelf-item__body">
            <h3 class="shelf-item__title"><?= e($book['title']) ?></h3>
            <p class="shelf-item__author">Tác giả: <strong><?= e($book['author']) ?></strong></p>

            <?php if (!empty($book['note'])): ?>
              <p class="shelf-item__note">“<?= e(mb_substr($book['note'], 0, 140)) ?><?= mb_strlen($book['note']) > 140 ? '...' : '' ?>”</p>
            <?php endif; ?>

            <div class="shelf-item__footer">
              <?php if (!empty($book['post_url'])): ?>
                <a href="<?= e(url($book['post_url'])) ?>" class="shelf-item__link-post">
                  <span>Xem bài viết liên quan</span>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
              <?php endif; ?>

              <?php if (!empty($book['affiliate_url'])): ?>
                <a href="<?= e($book['affiliate_url']) ?>" target="_blank" rel="noopener sponsored" class="btn btn--sm btn--book-shelf">
                  <span>Tìm đọc ↗</span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="shelf-note text-center mt-6">
      <p class="small muted">☕ Khi mua sách qua liên kết giới thiệu, bạn đang gửi một tách trà ấm tiếp sức cho những trang kịch bản tiếp theo của Thanh Thanh.</p>
    </div>
  </div>
</section>
