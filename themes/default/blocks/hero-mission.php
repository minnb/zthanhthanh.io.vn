<?php
/**
 * Hero trang chủ: câu sứ mệnh + giới thiệu + 2 nút + ảnh chân dung.
 * Cụm "người ngồi xe lăn" trong câu sứ mệnh được nhấn bằng chữ nghiêng màu nhấn.
 *
 * @var array<string, mixed> $site
 * @var array<string, mixed>|null $featuredFilm
 * @var array<string, mixed>|null $heroImage Ảnh chân dung (Cài đặt → hero_media_id); null = hình minh họa của theme
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
    <div class="portrait__frame">
      <?php if (!empty($heroImage)): ?>
        <?php /* Ảnh đầu trang là ứng viên LCP -> eager + fetchpriority high; khung 4:5 cắt quanh điểm tâm của ảnh */ ?>
        <?= partial('partials/image', ['media' => $heroImage, 'ratio' => \App\Core\Image::FRAME_PORTRAIT, 'eager' => true, 'sizes' => '(min-width: 768px) 460px, (min-width: 400px) 384px, 100vw']) ?>
      <?php else: ?>
        <?= partial('partials/art-portrait') ?>
      <?php endif; ?>
    </div>
    <?php if ($featuredFilm && !empty($featuredFilm['badge'])): ?>
      <figcaption class="portrait__note"><span>Mới nhất</span><?= e($featuredFilm['title'] . ' vào ' . $featuredFilm['badge']) ?></figcaption>
    <?php endif; ?>
  </figure>
</section>
