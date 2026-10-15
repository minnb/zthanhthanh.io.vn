<?php
/**
 * Hero trang chủ (bố cục bất đối xứng 7/5): câu sứ mệnh + giới thiệu + 2 nút + các chỉ số thành tích,
 * bên phải chân dung khung vòm kèm câu tâm niệm. Cụm "người ngồi xe lăn" được nhấn nghiêng màu nhấn.
 *
 * @var array<string, mixed> $site
 * @var array<string, mixed>|null $featuredFilm
 * @var array<string, mixed>|null $heroImage Ảnh chân dung (Cài đặt → hero_media_id); null = hình minh họa của theme
 * @var list<array{value: string, label: string}> $achievements
 */
$mission = (string) ($site['mission'] ?? '');
$highlight = 'người ngồi xe lăn';
$missionHtml = str_contains($mission, $highlight)
    ? str_replace(e($highlight), '<em>' . e($highlight) . '</em>', e($mission))
    : e($mission);
?>
<section class="container hero" aria-labelledby="home-title">
  <div class="hero__text">
    <p class="eyebrow eyebrow--accent">Biên kịch · Vai An trong phim ngắn ĐỢI</p>
    <h1 class="h-display" id="home-title"><?= $missionHtml ?></h1>
    <?php if (!empty($site['missionIntro'])): ?>
      <p class="lead"><?= e($site['missionIntro']) ?></p>
    <?php endif; ?>
    <div class="btn-row btn-row--stack">
      <a class="btn btn--primary" href="<?= e(url('/goc-sang-tac')) ?>">Khám phá kịch bản &amp; Phim <?= icon('arrow-r', 'i--sm i--move') ?></a>
      <a class="btn btn--outline" href="<?= e(url('/lien-he')) ?>">Gặp gỡ Pitching</a>
    </div>
    <?php if (!empty($achievements)): ?>
      <ul class="stats" aria-label="Thành tích nổi bật">
        <?php foreach ($achievements as $a): ?>
          <li class="stats__item card">
            <p class="stats__value"><?= e((string) ($a['value'] ?? '')) ?></p>
            <p class="stats__label"><?= e((string) ($a['label'] ?? '')) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <figure class="portrait">
    <div class="media">
      <?php if (!empty($heroImage)): ?>
        <?php /* Ảnh đầu trang là ứng viên LCP -> eager + fetchpriority high; khung 4:5 cắt quanh điểm tâm của ảnh */ ?>
        <?= partial('partials/image', ['media' => $heroImage, 'ratio' => \App\Core\Image::FRAME_PORTRAIT, 'eager' => true, 'sizes' => '(min-width: 960px) 440px, (min-width: 480px) 440px, 100vw']) ?>
      <?php else: ?>
        <?= partial('partials/art-portrait') ?>
      <?php endif; ?>
    </div>
    <figcaption class="portrait__note card">
      <p class="portrait__quote">“Không để người khác quyết định giới hạn của bản thân.”</p>
      <p class="portrait__by"><strong><?= e((string) ($site['authorName'] ?? '')) ?></strong> · Biên kịch &amp; vai chính phim ĐỢI</p>
    </figcaption>
  </figure>
</section>
