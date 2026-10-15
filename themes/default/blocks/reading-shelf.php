<?php
/**
 * Tủ sách gối đầu giường (Góc sáng tác): bìa chữ + lời tự sự của tác giả cho từng cuốn.
 *
 * Dùng ở Góc sáng tác (inStudio = true) và có thể bật làm khối trang chủ (home_blocks, xem
 * tools/activate_reading_shelf.php; HomeController nạp thêm studio.css) – khi đó tự bọc container.
 *
 * @var list<array<string, mixed>> $featuredBooks
 * @var bool|null $inStudio
 */
if (empty($featuredBooks)) {
    return;
}
$inStudio = !empty($inStudio);
?>
<?php if (!$inStudio): ?><div class="container section"><?php endif; ?>
<section class="studio-panel" id="panel-books" aria-labelledby="shelf-heading">
  <div class="section-head">
    <div class="section-head__text">
      <p class="eyebrow eyebrow--gold">Tủ sách gối đầu giường</p>
      <h2 class="h-2" id="shelf-heading">Đọc để hiểu, để thương và để cố gắng</h2>
      <p class="muted">Với một biên kịch, đọc sách không chỉ là giải trí, mà là cách tích lũy vốn sống và học cách thấu cảm với những nỗi đau vượt thời gian của con người.</p>
    </div>
  </div>
  <ol class="shelf">
    <?php foreach ($featuredBooks as $i => $book): ?>
      <li>
        <article class="book card">
          <?= partial('partials/book-cover', ['book' => $book]) ?>
          <div>
            <p class="label book__num"><?= sprintf('%02d', $i + 1) ?></p>
            <h3 class="book__title"><?= e((string) $book['title']) ?></h3>
            <p class="book__author"><?= e((string) $book['author']) ?></p>
            <?php if (!empty($book['note'])): ?><p class="book__note">“<?= e((string) $book['note']) ?>”</p><?php endif; ?>
            <p class="book__links">
              <?php if (!empty($book['post_url'])): ?>
                <a class="text-link" href="<?= e(url((string) $book['post_url'])) ?>">Bài viết liên quan <?= icon('arrow-r') ?></a>
              <?php endif; ?>
              <?php if (!empty($book['affiliate_url'])): ?>
                <a class="text-link" href="<?= e(link_url((string) $book['affiliate_url'])) ?>" target="_blank" rel="noopener sponsored">Tìm đọc <?= icon('arrow-ur') ?><span class="sr-only">(mở tab mới)</span></a>
              <?php endif; ?>
            </p>
          </div>
        </article>
      </li>
    <?php endforeach; ?>
  </ol>
  <p class="muted small mt-8">Nếu bạn chọn mua sách qua các liên kết này, bạn đang gửi một tách trà ấm tiếp sức cho những trang viết tiếp theo của mình. Cảm ơn bạn thật nhiều!</p>
</section>
<?php if (!$inStudio): ?></div><?php endif; ?>
