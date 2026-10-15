<?php
/**
 * Thẻ bài viết. Link chỉ bọc tiêu đề (tên link ngắn gọn cho trình đọc màn hình); ::after của link
 * phủ cả thẻ nên bấm vào ảnh/tóm tắt vẫn mở bài. Ảnh bìa lấy từ Post::cards() ('cover' hoặc null).
 *
 * @var array{title: string, url: string, excerpt?: string, category?: string, published_at?: string, reading_minutes?: int, cover?: array<string, mixed>|null} $post
 * @var string|null $headingTag  Chỉ nhận "h2" hoặc "h3" (mặc định h3)
 * @var string|null $variant     "feature" | "row" | null (bố cục tạp chí trang chủ)
 * @var string|null $sizes       Thuộc tính sizes cho ảnh
 */
$tag = in_array($headingTag ?? 'h3', ['h2', 'h3'], true) ? ($headingTag ?? 'h3') : 'h3';
$variant = in_array($variant ?? '', ['feature', 'row'], true) ? (string) $variant : '';
$published = !empty($post['published_at']) ? strtotime((string) $post['published_at']) : false;
?>
<article class="post-card<?= $variant !== '' ? ' post-card--' . $variant : '' ?>">
  <div class="card card--hover">
    <div class="media" aria-hidden="true">
      <?php if (!empty($post['cover'])): ?>
        <?= partial('partials/image', ['media' => $post['cover'], 'ratio' => 3 / 2, 'sizes' => $sizes ?? '(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw']) ?>
      <?php endif; ?>
    </div>
    <div class="post-card__body">
      <?php if (!empty($post['category'])): ?><p class="label post-card__cat"><?= e($post['category']) ?></p><?php endif; ?>
      <<?= $tag ?> class="post-card__title"><a class="post-card__link" href="<?= e(url($post['url'])) ?>"><?= e($post['title']) ?></a></<?= $tag ?>>
      <?php if (!empty($post['excerpt'])): ?><p class="post-card__excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
      <p class="post-card__meta">
        <?php if ($published): ?><time datetime="<?= date('c', $published) ?>"><?= date('d/m/Y', $published) ?></time><?php endif; ?>
        <?php if (!empty($post['reading_minutes'])): ?><?= $published ? '<span aria-hidden="true">·</span>' : '' ?><span><?= (int) $post['reading_minutes'] ?> phút đọc</span><?php endif; ?>
      </p>
    </div>
  </div>
</article>
