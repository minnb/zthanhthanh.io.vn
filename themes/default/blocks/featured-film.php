<?php
/**
 * Phim nổi bật & Dự án kịch bản đang phát triển.
 *
 * @var array<string, mixed>|null $featuredFilm
 * @var list<array<string, mixed>>|null $allFilms
 * @var array<string, mixed> $site
 */
if (!$featuredFilm) {
    return;
}
$filmUrl = url('/phim/' . $featuredFilm['slug']);
$devFilm = null;
if (!empty($allFilms)) {
    foreach ($allFilms as $f) {
        if ($f['slug'] === 'dua-con-den-tu-coi-am' || ($f['status'] ?? '') === 'in_development') {
            $devFilm = $f;
            break;
        }
    }
}
?>
<section class="container section" id="featured-works" aria-labelledby="featured-heading">
  <div class="section__head">
    <div>
      <p class="eyebrow">DANH SÁCH TÁC PHẨM · SCREENWRITING PORTFOLIO</p>
      <h2 class="h-2 mt-2" id="featured-heading">Dự án Phim & Kịch bản</h2>
    </div>
    <a class="section__more" href="<?= e(url('/phim')) ?>">Tất cả phim →</a>
  </div>

  <div class="works-showcase">
    <!-- Phim tiêu điểm: ĐỢI -->
    <article class="film-feature">
      <a class="film-feature__media" href="<?= e($filmUrl) ?>" tabindex="-1" aria-hidden="true">
        <?php if (!empty($featuredFilm['poster'])): ?>
          <?= partial('partials/image', ['media' => $featuredFilm['poster'], 'ratio' => \App\Core\Image::FRAME_POSTER, 'sizes' => '(min-width: 1152px) 1104px, 100vw']) ?>
        <?php else: ?>
          <?= partial('partials/art-poster', ['filmTitle' => (string) $featuredFilm['title'], 'caption' => (string) ($featuredFilm['caption'] ?? '')]) ?>
        <?php endif; ?>
        <?php if (!empty($featuredFilm['badge'])): ?><span class="badge"><?= e($featuredFilm['badge']) ?></span><?php endif; ?>
      </a>
      <div class="film-feature__body">
        <div>
          <span class="scene-no">Tác phẩm tiêu biểu · Phim ngắn</span>
          <h3 class="film-feature__title"><a href="<?= e($filmUrl) ?>"><?= e($featuredFilm['title']) ?></a></h3>
          <?php if (!empty($featuredFilm['logline'])): ?><p class="lead film-feature__logline"><?= e($featuredFilm['logline']) ?></p><?php endif; ?>
        </div>
        <dl class="meta-list">
          <div><dt>Biên kịch & vai chính</dt><dd><?= e($site['authorName'] ?? 'Thanh Thanh') ?></dd></div>
          <?php if (!empty($featuredFilm['genre'])): ?><div><dt>Thể loại</dt><dd><?= e($featuredFilm['genre']) ?></dd></div><?php endif; ?>
          <?php if (!empty($featuredFilm['duration_min'])): ?>
            <div><dt>Thời lượng</dt><dd><?= (int) $featuredFilm['duration_min'] ?> phút<?php if (!empty($featuredFilm['duration_note'])): ?> <span class="muted">(<?= e($featuredFilm['duration_note']) ?>)</span><?php endif; ?></dd></div>
          <?php endif; ?>
        </dl>
        <div class="film-feature__actions">
          <a class="btn btn--primary btn--sm" href="<?= e($filmUrl) ?>">Xem chi tiết phim & Hậu trường →</a>
          <a class="btn btn--outline btn--sm" href="<?= e(url('/goc-sang-tac')) ?>">Đọc trích đoạn kịch bản →</a>
        </div>
      </div>
    </article>

    <?php if ($devFilm): ?>
      <!-- Dự án đang phát triển / Pitching: Đứa con đến từ cõi âm -->
      <article class="project-pitch-card">
        <div class="project-pitch-card__badge-row">
          <span class="badge badge--gold"><?= e($devFilm['badge'] ?? 'Top 7 From Script to Screen') ?></span>
          <span class="project-pitch-card__status">⚡ Dự án điện ảnh đang phát triển · Mời gọi hợp tác</span>
        </div>
        <div class="project-pitch-card__content">
          <div class="project-pitch-card__main">
            <h3 class="project-pitch-card__title">
              <a href="<?= e(url('/phim/' . $devFilm['slug'])) ?>"><?= e($devFilm['title']) ?></a>
            </h3>
            <p class="project-pitch-card__logline"><?= e($devFilm['logline']) ?></p>
          </div>
          <div class="project-pitch-card__cta">
            <a class="btn btn--outline btn--sm" href="<?= e(url('/phim/' . $devFilm['slug'])) ?>">Xem hồ sơ phim & Pitch deck →</a>
            <a class="btn btn--primary btn--sm" href="mailto:<?= e($site['contactEmail'] ?? 'doicho147@gmail.com') ?>?subject=<?= rawurlencode('[Pitching] Hợp tác dự án ' . $devFilm['title']) ?>">Liên hệ Pitching dự án</a>
          </div>
        </div>
      </article>
    <?php endif; ?>
  </div>
</section>
