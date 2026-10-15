<?php
/**
 * Dự án phim tiêu biểu: thẻ phim nổi bật (ĐỢI) + thẻ dự án đang phát triển (tông tối "noir").
 *
 * @var array<string, mixed>|null $featuredFilm
 * @var list<array<string, mixed>>|null $allFilms
 * @var array<string, mixed> $site
 */
if (!$featuredFilm) {
    return;
}
$devFilm = null;
foreach ($allFilms ?? [] as $f) {
    if ((int) $f['id'] !== (int) $featuredFilm['id'] && ($f['status'] ?? '') === 'in_development') {
        $devFilm = $f;
        break;
    }
}
$contactEmail = (string) ($site['contactEmail'] ?? '');
?>
<section class="section section--tint" id="featured-works" aria-labelledby="films-title">
  <div class="container">
    <div class="section-head">
      <div class="section-head__text">
        <p class="eyebrow">Dự án phim</p>
        <h2 class="h-2" id="films-title">Phim &amp; dự án tiêu biểu</h2>
      </div>
      <a class="text-link" href="<?= e(url('/phim')) ?>">Tất cả phim <?= icon('arrow-r') ?></a>
    </div>

    <div class="films">
      <article class="film-card card" aria-labelledby="film-<?= e((string) $featuredFilm['slug']) ?>">
        <span class="media">
          <?php if (!empty($featuredFilm['poster'])): ?>
            <?= partial('partials/image', ['media' => $featuredFilm['poster'], 'ratio' => 16 / 10, 'sizes' => '(min-width: 1240px) 680px, (min-width: 960px) 55vw, 100vw']) ?>
          <?php else: ?>
            <?= partial('partials/art-poster', ['filmTitle' => (string) $featuredFilm['title'], 'caption' => (string) ($featuredFilm['caption'] ?? '')]) ?>
          <?php endif; ?>
        </span>
        <div class="film-card__body">
          <?php if (!empty($featuredFilm['badge'])): ?><p><span class="badge badge--gold label"><?= icon('star') ?><?= e($featuredFilm['badge']) ?></span></p><?php endif; ?>
          <h3 class="film-card__title" id="film-<?= e((string) $featuredFilm['slug']) ?>">
            <a href="<?= e(url((string) $featuredFilm['url'])) ?>"><?= e($featuredFilm['title']) ?></a>
            <small><?= e(trim(($featuredFilm['title_en'] ?? '') . (!empty($featuredFilm['year']) ? ' · ' . $featuredFilm['year'] : ''), ' ·')) ?></small>
          </h3>
          <ul class="meta-row">
            <?php if (!empty($featuredFilm['genre'])): ?><li><?= icon('film') ?><?= e($featuredFilm['genre']) ?></li><?php endif; ?>
            <?php if (!empty($featuredFilm['duration_note']) || !empty($featuredFilm['duration_min'])): ?>
              <li><?= icon('clock') ?><?= e((string) ($featuredFilm['duration_note'] ?: $featuredFilm['duration_min'] . ' phút')) ?></li>
            <?php endif; ?>
            <li><?= icon('users') ?>Biên kịch &amp; vai chính: <?= e((string) ($site['authorName'] ?? '')) ?></li>
          </ul>
          <?php if (!empty($featuredFilm['logline'])): ?><p class="logline"><?= e($featuredFilm['logline']) ?></p><?php endif; ?>
          <?php if (!empty($featuredFilm['awards_list'])): ?>
            <ul class="awards" aria-label="Thành tích">
              <?php foreach ($featuredFilm['awards_list'] as $award): ?><li><?= icon('star') ?><span><?= e((string) $award) ?></span></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="film-card__foot">
            <a class="text-link" href="<?= e(url((string) $featuredFilm['url'])) ?>">Trang phim &amp; hậu trường <?= icon('arrow-r') ?></a>
            <a class="text-link" href="<?= e(url('/goc-sang-tac')) ?>">Đọc trích đoạn kịch bản <?= icon('arrow-r') ?></a>
          </div>
        </div>
      </article>

      <?php if ($devFilm): ?>
        <article class="film-card dark-surface" aria-labelledby="film-<?= e((string) $devFilm['slug']) ?>">
          <div class="noir-art" aria-hidden="true">
            <div class="noir-art__moon"></div>
            <?php if (!empty($devFilm['title_en'])): ?><p class="noir-art__word" lang="en"><?= e($devFilm['title_en']) ?></p><?php endif; ?>
          </div>
          <div class="film-card__body">
            <p><span class="badge label"><?= e($devFilm['status_label']) ?></span></p>
            <h3 class="film-card__title" id="film-<?= e((string) $devFilm['slug']) ?>">
              <a href="<?= e(url((string) $devFilm['url'])) ?>"><?= e($devFilm['title']) ?></a>
            </h3>
            <?php if (!empty($devFilm['genre'])): ?><ul class="meta-row"><li><?= icon('film') ?><?= e($devFilm['genre']) ?></li></ul><?php endif; ?>
            <?php if (!empty($devFilm['logline'])): ?><p class="logline"><?= e($devFilm['logline']) ?></p><?php endif; ?>
            <?php if (!empty($devFilm['awards_list'])): ?>
              <ul class="awards" aria-label="Thành tích dự án">
                <?php foreach ($devFilm['awards_list'] as $award): ?><li><?= icon('star') ?><span><?= e((string) $award) ?></span></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <div class="film-card__foot">
              <a class="text-link" href="<?= e(url((string) $devFilm['url'])) ?>">Hồ sơ dự án <?= icon('arrow-r') ?></a>
              <?php if ($contactEmail !== ''): ?>
                <a class="text-link" href="mailto:<?= e($contactEmail) ?>?subject=<?= rawurlencode('[Pitching] Hợp tác dự án ' . $devFilm['title']) ?>">Liên hệ pitching <?= icon('mail') ?></a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endif; ?>
    </div>
  </div>
</section>
