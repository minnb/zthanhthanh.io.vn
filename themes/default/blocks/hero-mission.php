<?php
/**
 * Hero trang chủ: câu sứ mệnh + giới thiệu + 2 nút + ảnh chân dung.
 * Cụm "người ngồi xe lăn" trong câu sứ mệnh được nhấn bằng chữ nghiêng màu nhấn.
 *
 * @var array<string, mixed> $site
 * @var array<string, mixed>|null $featuredFilm
 */
$mission = (string) ($site['mission'] ?? '');
$highlight = 'người ngồi xe lăn';
$missionHtml = str_contains($mission, $highlight)
    ? str_replace(e($highlight), '<em>' . e($highlight) . '</em>', e($mission))
    : e($mission);
?>
<section class="container hero">
  <div>
    <p class="eyebrow">Biên kịch · Diễn viên · Người kể chuyện</p>
    <h1 class="h-display mt-5"><?= $missionHtml ?></h1>
    <?php if (!empty($site['missionIntro'])): ?>
      <p class="lead hero__lead"><?= e($site['missionIntro']) ?></p>
    <?php endif; ?>
    <div class="btn-row">
      <?php if ($featuredFilm): ?>
        <a class="btn btn--primary" href="<?= e(url('/phim/' . $featuredFilm['slug'])) ?>">Xem phim <?= e($featuredFilm['title']) ?></a>
      <?php endif; ?>
      <a class="btn btn--outline" href="<?= e(url('/hau-truong-bien-kich')) ?>">Đọc hậu trường →</a>
    </div>
  </div>
  <figure class="portrait">
    <div class="portrait__frame"><?= partial('partials/art-portrait') ?></div>
    <?php if ($featuredFilm && !empty($featuredFilm['badge'])): ?>
      <figcaption class="portrait__note"><span>Mới nhất</span><?= e($featuredFilm['title'] . ' vào ' . $featuredFilm['badge']) ?></figcaption>
    <?php endif; ?>
  </figure>
</section>
