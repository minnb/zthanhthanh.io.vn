<?php
/**
 * Hộp "Thì thầm từ tủ sách" cuối bài: sách gắn với bài (books.post_id) kèm lời tự sự của tác giả.
 *
 * @var list<array<string, mixed>> $books
 */
if (empty($books)) {
    return;
}
?>
<aside class="whisper card" aria-labelledby="whisper-title">
  <p class="label whisper__label">Thì thầm từ tủ sách</p>
  <p class="whisper__lead" id="whisper-title">Cuốn sách đã đồng hành và tiếp thêm chất liệu cho bài viết này của mình.</p>
  <?php foreach ($books as $book): ?>
    <div class="whisper__item">
      <?= partial('partials/book-cover', ['book' => $book]) ?>
      <div>
        <p><strong><?= e((string) $book['title']) ?></strong> <span class="muted">· <?= e((string) $book['author']) ?></span></p>
        <?php if (!empty($book['note'])): ?><p class="whisper__note">“<?= e((string) $book['note']) ?>”</p><?php endif; ?>
        <?php if (!empty($book['affiliate_url'])): ?>
          <a class="text-link" href="<?= e(link_url((string) $book['affiliate_url'])) ?>" target="_blank" rel="noopener sponsored">Tìm đọc cuốn sách này <?= icon('arrow-ur') ?><span class="sr-only">(mở tab mới)</span></a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <p class="whisper__foot">Nếu bạn mua sách qua liên kết giới thiệu này, đó cũng là một tách trà ấm tiếp sức cho những trang viết tiếp theo của mình. <a class="text-accent" href="<?= e(url('/tu-sach')) ?>">Xem cả tủ sách →</a></p>
</aside>
