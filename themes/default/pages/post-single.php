<?php
/**
 * Chi tiết bài viết: đầu bài căn giữa kiểu tạp chí, ảnh bìa lớn, prose, thẻ + chia sẻ,
 * hộp sách cuối bài, hộp tác giả, bài trước/sau, đọc tiếp.
 *
 * @var array<string, mixed> $post   Có thêm: url, html (đã render an toàn), reading_minutes, tags, cover
 * @var list<array<string, mixed>> $related
 * @var array{prev: array<string, mixed>|null, next: array<string, mixed>|null} $adjacent
 * @var list<array<string, mixed>> $books
 * @var list<array{label: string, url?: string}> $breadcrumb
 * @var array<string, mixed> $site
 */
$published = strtotime((string) $post['published_at']) ?: time();
$updated = strtotime((string) $post['updated_at']) ?: $published;
$authorName = (string) ($site['authorName'] ?? '');
$avatar = (string) ($site['authorAvatar'] ?? '');
$prev = $adjacent['prev'] ?? null;
$next = $adjacent['next'] ?? null;
?>
<article>
  <header class="container article-head">
    <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
    <p><a class="badge label" href="<?= e(url('/' . $post['category_slug'])) ?>"><?= e($post['category_name']) ?></a></p>
    <h1 class="h-1"><?= e($post['title']) ?></h1>
    <?php if (!empty($post['excerpt'])): ?><p class="lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
    <div class="byline">
      <?php if ($avatar !== ''): ?>
        <img class="avatar avatar--sm" src="<?= e(url($avatar)) ?>" alt="" width="40" height="40">
      <?php else: ?>
        <span class="avatar avatar--sm" aria-hidden="true"><?= e(mb_substr($authorName, 0, 1)) ?></span>
      <?php endif; ?>
      <span>Bởi <strong><?= e($authorName) ?></strong></span>
      <span aria-hidden="true">·</span>
      <time datetime="<?= date('c', $published) ?>"><?= date('d/m/Y', $published) ?></time>
      <span aria-hidden="true">·</span>
      <span class="byline__item"><?= icon('clock') ?><?= (int) $post['reading_minutes'] ?> phút đọc</span>
      <?php if ($updated - $published > 86400): ?>
        <span aria-hidden="true">·</span><span>Cập nhật <time datetime="<?= date('c', $updated) ?>"><?= date('d/m/Y', $updated) ?></time></span>
      <?php endif; ?>
    </div>
  </header>

  <?php if (!empty($post['cover'])): ?>
    <figure class="container cover">
      <div class="media"><?= partial('partials/image', ['media' => $post['cover'], 'ratio' => \App\Core\Image::FRAME_COVER, 'eager' => true, 'sizes' => '(min-width: 1180px) 1100px, 100vw']) ?></div>
      <?php if (!empty($post['cover']['caption'])): ?><figcaption><?= e($post['cover']['caption']) ?></figcaption><?php endif; ?>
    </figure>
  <?php endif; ?>

  <div class="container">
    <div class="prose article__body">
      <?= $post['html'] /* đã render qua Markdown safeMode */ ?>
    </div>

    <footer class="post-foot">
      <?php if (!empty($post['tags'])): ?>
        <ul class="tag-list" aria-label="Chủ đề">
          <?php foreach ($post['tags'] as $tag): ?>
            <li><a class="tag" href="<?= e(url('/chu-de/' . $tag['slug'])) ?>">#<?= e(ltrim((string) $tag['name'], '#')) ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?= partial('partials/share-buttons', ['shareUrl' => url($post['url']), 'shareTitle' => (string) $post['title']]) ?>
    </footer>

    <div class="article-aside">
      <?= partial('partials/book-card', ['books' => $books ?? []]) ?>
      <?= partial('partials/author-box') ?>
    </div>

    <?php if ($prev || $next): ?>
      <nav class="post-nav" aria-label="Bài trước và bài tiếp">
        <?php if ($prev): ?>
          <a class="card card--hover" href="<?= e(url($prev['url'])) ?>" rel="prev"><span class="label post-nav__dir"><?= icon('arrow-l') ?>Bài trước</span><span class="post-nav__title"><?= e($prev['title']) ?></span></a>
        <?php endif; ?>
        <?php if ($next): ?>
          <a class="card card--hover post-nav__next" href="<?= e(url($next['url'])) ?>" rel="next"><span class="label post-nav__dir">Bài tiếp<?= icon('arrow-r') ?></span><span class="post-nav__title"><?= e($next['title']) ?></span></a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </div>
</article>

<?php if ($related !== []): ?>
  <section class="section" aria-labelledby="related">
    <div class="container">
      <div class="section-head">
        <div class="section-head__text">
          <p class="eyebrow">Đọc tiếp</p>
          <h2 class="h-2" id="related">Có thể bạn muốn đọc</h2>
        </div>
        <a class="text-link" href="<?= e(url('/bai-viet')) ?>">Tất cả bài viết <?= icon('arrow-r') ?></a>
      </div>
      <ul class="grid grid--3" role="list">
        <?php foreach ($related as $item): ?>
          <li><?= partial('partials/post-card', ['post' => $item]) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
<?php endif; ?>
