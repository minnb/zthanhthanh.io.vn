<?php
/**
 * Danh sách phim & dự án. Phim đang phát triển dùng thẻ tông tối "noir".
 *
 * @var list<array<string, mixed>> $films
 * @var list<array{label: string, url?: string}> $breadcrumb
 */
?>
<header class="container page-head">
  <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
  <p class="eyebrow">Phim &amp; dự án</p>
  <h1 class="h-1">Phim</h1>
  <p class="lead">Những câu chuyện mình đã đưa lên màn ảnh, và những câu chuyện đang lớn lên cùng mình.</p>
</header>

<section class="container section section--flush-top" aria-label="Danh sách phim">
  <ul class="grid grid--2" role="list">
    <?php foreach ($films as $film): ?>
      <?php $noir = empty($film['poster']) && ($film['status'] ?? '') === 'in_development'; ?>
      <li>
        <article class="film-card <?= $noir ? 'dark-surface' : 'card' ?>">
          <?php if ($noir): ?>
            <div class="noir-art" aria-hidden="true">
              <div class="noir-art__moon"></div>
              <?php if (!empty($film['title_en'])): ?><p class="noir-art__word" lang="en"><?= e($film['title_en']) ?></p><?php endif; ?>
            </div>
          <?php else: ?>
            <span class="media">
              <?php if (!empty($film['poster'])): ?>
                <?= partial('partials/image', ['media' => $film['poster'], 'ratio' => 16 / 10, 'sizes' => '(min-width: 640px) 50vw, 100vw']) ?>
              <?php else: ?>
                <?= partial('partials/art-poster', ['filmTitle' => (string) $film['title'], 'caption' => (string) $film['caption']]) ?>
              <?php endif; ?>
            </span>
          <?php endif; ?>
          <div class="film-card__body">
            <p><span class="badge label<?= $noir ? '' : ' badge--gold' ?>"><?= e($film['badge'] ?: $film['status_label']) ?></span></p>
            <h2 class="film-card__title"><a href="<?= e(url($film['url'])) ?>"><?= e($film['title']) ?></a><?php if (!empty($film['caption'])): ?><small><?= e($film['caption']) ?></small><?php endif; ?></h2>
            <?php if (!empty($film['logline'])): ?><p class="logline"><?= e($film['logline']) ?></p><?php endif; ?>
            <div class="film-card__foot"><a class="text-link" href="<?= e(url($film['url'])) ?>">Xem phim <?= e($film['title']) ?> <?= icon('arrow-r') ?></a></div>
          </div>
        </article>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
