<?php
/**
 * Lời thủ thỉ từ trang sách (The Writer's Reading Corner)
 * Thiết kế tối giản, tinh tế như một ghi chép bên lề của tác giả Thanh Thanh.
 *
 * @var list<array<string, mixed>> $books
 */
if (empty($books)) {
    return;
}
?>
<aside class="author-reading-whisper" aria-label="Góc đọc của Thanh Thanh">
  <div class="reading-whisper__header">
    <span class="reading-whisper__badge">Bên lề trang viết</span>
    <p class="reading-whisper__lead">Cuốn sách đã đồng hành và tiếp thêm chất liệu cho bài viết này của mình:</p>
  </div>

  <div class="reading-whisper__list">
    <?php foreach ($books as $book): ?>
      <div class="reading-whisper__item">
        <div class="reading-whisper__book">
          <?php if (!empty($book['cover'])): ?>
            <div class="reading-whisper__cover">
              <?= partial('partials/image', [
                  'media' => $book['cover'],
                  'ratio' => '3/4',
                  'eager' => false,
                  'sizes' => '88px'
              ]) ?>
            </div>
          <?php else: ?>
            <div class="reading-whisper__spine" aria-hidden="true">
              <span class="reading-whisper__spine-title"><?= e($book['title']) ?></span>
            </div>
          <?php endif; ?>

          <div class="reading-whisper__meta">
            <h4 class="reading-whisper__title"><?= e($book['title']) ?></h4>
            <p class="reading-whisper__author"><?= e($book['author']) ?></p>

            <?php if (!empty($book['affiliate_url'])): ?>
              <a href="<?= e($book['affiliate_url']) ?>" target="_blank" rel="noopener sponsored" class="reading-whisper__link">
                <span>Tìm đọc cuốn sách này</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M7 17l9.2-9.2M17 17V8H8"/></svg>
              </a>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($book['note'])): ?>
          <blockquote class="reading-whisper__note">
            <p>“<?= e($book['note']) ?>”</p>
          </blockquote>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="reading-whisper__footer">
    ☕ Nếu bạn mua sách qua liên kết giới thiệu này, đó cũng là một tách trà ấm tiếp sức cho những trang viết tiếp theo của mình. Cảm ơn bạn thật nhiều!
  </p>
</aside>
