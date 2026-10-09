<?php
/**
 * Danh sách phim & dự án.
 *
 * @var list<array<string, mixed>> $films
 * @var list<array{label: string, url?: string}> $breadcrumb
 */
?>
<header class="container page-head">
  <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
  <p class="eyebrow">Phim & dự án</p>
  <h1 class="h-1">Phim</h1>
  <p class="lead">Những câu chuyện mình đã đưa lên màn ảnh, và những câu chuyện đang lớn lên cùng mình.</p>
</header>

<section class="container section" aria-label="Danh sách phim">
  <div class="grid grid--2">
    <?php foreach ($films as $film): ?>
      <article class="film-card">
        <a class="film-card__media" href="<?= e(url($film['url'])) ?>" tabindex="-1" aria-hidden="true">
          <?= partial('partials/art-poster', ['filmTitle' => (string) $film['title'], 'caption' => (string) $film['caption']]) ?>
          <?php if (!empty($film['badge'])): ?><span class="badge"><?= e($film['badge']) ?></span><?php endif; ?>
        </a>
        <div class="film-card__body">
          <p class="film-card__status"><?= e($film['status_label']) ?></p>
          <h2 class="film-card__title"><a href="<?= e(url($film['url'])) ?>"><?= e($film['title']) ?></a></h2>
          <?php if (!empty($film['logline'])): ?><p class="film-card__logline"><?= e($film['logline']) ?></p><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
