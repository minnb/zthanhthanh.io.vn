<?php
/**
 * Phim nổi bật.
 *
 * @var array<string, mixed>|null $featuredFilm
 */
if (!$featuredFilm) {
    return;
}
$filmUrl = url('/phim/' . $featuredFilm['slug']);
?>
<section class="container section" aria-labelledby="featured-film">
  <div class="section__head">
    <h2 class="h-2" id="featured-film">Phim nổi bật</h2>
    <a class="section__more" href="<?= e(url('/phim')) ?>">Tất cả phim →</a>
  </div>
  <article class="film-feature">
    <a class="film-feature__media" href="<?= e($filmUrl) ?>" tabindex="-1" aria-hidden="true">
      <?= partial('partials/art-poster', ['filmTitle' => (string) $featuredFilm['title'], 'caption' => (string) ($featuredFilm['caption'] ?? '')]) ?>
      <?php if (!empty($featuredFilm['badge'])): ?><span class="badge"><?= e($featuredFilm['badge']) ?></span><?php endif; ?>
    </a>
    <div class="film-feature__body">
      <div>
        <h3 class="film-feature__title"><a href="<?= e($filmUrl) ?>"><?= e($featuredFilm['title']) ?></a></h3>
        <?php if (!empty($featuredFilm['logline'])): ?><p class="lead film-feature__logline"><?= e($featuredFilm['logline']) ?></p><?php endif; ?>
      </div>
      <dl class="meta-list">
        <div><dt>Biên kịch & vai chính</dt><dd><?= e($site['authorName'] ?? '') ?></dd></div>
        <?php if (!empty($featuredFilm['genre'])): ?><div><dt>Thể loại</dt><dd><?= e($featuredFilm['genre']) ?></dd></div><?php endif; ?>
        <?php if (!empty($featuredFilm['duration_min'])): ?>
          <div><dt>Thời lượng</dt><dd><?= (int) $featuredFilm['duration_min'] ?> phút<?php if (!empty($featuredFilm['duration_note'])): ?> <span class="muted">(<?= e($featuredFilm['duration_note']) ?>)</span><?php endif; ?></dd></div>
        <?php endif; ?>
      </dl>
    </div>
  </article>
</section>
