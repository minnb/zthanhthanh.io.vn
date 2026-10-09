<?php
/**
 * Thẻ bài viết trong danh sách.
 *
 * @var array{title: string, url: string, excerpt?: string, category?: string, reading_minutes?: int} $post
 * @var int|null $index   Số thứ tự để hiển thị "Cảnh 01"
 * @var string|null $headingTag  Chỉ nhận "h2" hoặc "h3" (mặc định h3)
 */
$tag = in_array($headingTag ?? 'h3', ['h2', 'h3'], true) ? $headingTag ?? 'h3' : 'h3';
?>
<article class="post-card">
  <?php if (isset($index)): ?><p class="scene-no">Cảnh <?= sprintf('%02d', $index) ?></p><?php endif; ?>
  <<?= $tag ?> class="post-card__title"><a href="<?= e(url($post['url'])) ?>"><?= e($post['title']) ?></a></<?= $tag ?>>
  <?php if (!empty($post['excerpt'])): ?><p class="post-card__excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
  <p class="post-card__meta">
    <?= e($post['category'] ?? '') ?><?php if (!empty($post['reading_minutes'])): ?> · <?= (int) $post['reading_minutes'] ?> phút đọc<?php endif; ?>
  </p>
</article>
