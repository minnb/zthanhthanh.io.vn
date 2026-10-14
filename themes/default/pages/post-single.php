<?php
/**
 * Chi tiết bài viết.
 *
 * @var array<string, mixed> $post   Có thêm: url, html (đã render an toàn), reading_minutes, tags
 * @var list<array<string, mixed>> $related
 * @var list<array{label: string, url?: string}> $breadcrumb
 * @var array<string, mixed> $site
 */
$published = strtotime((string) $post['published_at']) ?: time();
$updated = strtotime((string) $post['updated_at']) ?: $published;
$authorName = (string) ($site['authorName'] ?? '');
?>
<article class="container article">
  <header class="article__head">
    <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
    <p class="eyebrow"><a href="<?= e(url('/' . $post['category_slug'])) ?>"><?= e($post['category_name']) ?></a></p>
    <h1 class="h-1"><?= e($post['title']) ?></h1>
    <?php if (!empty($post['excerpt'])): ?><p class="lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
    <div class="byline">
      <span class="avatar avatar--sm" aria-hidden="true"><?= e(mb_substr($authorName, 0, 1)) ?></span>
      <div>
        <p class="byline__name"><?= e($authorName) ?></p>
        <p class="byline__meta">
          <time datetime="<?= date('c', $published) ?>"><?= date('d/m/Y', $published) ?></time>
          · <?= (int) $post['reading_minutes'] ?> phút đọc
          <?php if ($updated - $published > 86400): ?>
            · Cập nhật <time datetime="<?= date('c', $updated) ?>"><?= date('d/m/Y', $updated) ?></time>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </header>

  <?php if (!empty($post['cover'])): ?>
    <figure class="article__cover">
      <?= partial('partials/image', ['media' => $post['cover'], 'ratio' => \App\Core\Image::FRAME_COVER, 'eager' => true, 'sizes' => '(min-width: 960px) 896px, 100vw']) ?>
      <?php if (!empty($post['cover']['caption'])): ?><figcaption class="small muted"><?= e($post['cover']['caption']) ?></figcaption><?php endif; ?>
    </figure>
  <?php endif; ?>

  <div class="prose article__body">
    <?= $post['html'] /* đã render qua Markdown safeMode */ ?>
  </div>

  <?php if (!empty($books)): ?>
    <?= partial('partials/book-card', ['books' => $books]) ?>
  <?php endif; ?>

  <footer class="article__foot">
    <?php if (!empty($post['tags'])): ?>
      <ul class="tag-list" aria-label="Chủ đề">
        <?php foreach ($post['tags'] as $tag): ?>
          <li><a class="tag" href="<?= e(url('/chu-de/' . $tag['slug'])) ?>">#<?= e($tag['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?= partial('partials/share-buttons', ['shareUrl' => url($post['url']), 'shareTitle' => (string) $post['title']]) ?>
    <?= partial('partials/author-box') ?>
  </footer>
</article>

<?php if ($related !== []): ?>
  <section class="section section--tint" aria-labelledby="related">
    <div class="container">
      <h2 class="h-2" id="related">Đọc tiếp</h2>
      <div class="grid grid--3 mt-5">
        <?php foreach ($related as $item): ?>
          <?= partial('partials/post-card', ['post' => $item]) ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>
