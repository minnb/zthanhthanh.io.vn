<?php
/**
 * Dải vinh danh (khối "achievements" trong home_blocks): huy hiệu + danh sách giải của phim nổi bật,
 * nền "phòng chiếu" tối, vòng nguyệt quế và poster. Các con số thành tích (setting achievements)
 * hiển thị trong hero-mission.
 *
 * @var array<string, mixed>|null $featuredFilm
 */
if (!$featuredFilm || (empty($featuredFilm['badge']) && empty($featuredFilm['awards_list']))) {
    return;
}
$badgeParts = array_map('trim', explode('·', (string) ($featuredFilm['badge'] ?? ''), 2));
$honorTitle = $badgeParts[0] !== '' ? $badgeParts[0] : (string) $featuredFilm['title'];
$honorWhere = $badgeParts[1] ?? '';
$filmName = (string) $featuredFilm['title'] . (!empty($featuredFilm['title_en']) ? ' (' . $featuredFilm['title_en'] . ')' : '');
?>
<section class="section section--flush-top" aria-labelledby="honor-title">
  <div class="container">
    <div class="honor dark-surface">
      <svg class="honor__laurel" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
        <g fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"><path d="M58 106C33 101 17 82 17 57c0-10 3-20 9-28"/><path d="M62 106c25-5 41-24 41-49 0-10-3-20-9-28"/></g>
        <g fill="currentColor">
          <ellipse rx="3.2" ry="8" transform="translate(29 31) rotate(35)"/><ellipse rx="3.2" ry="8" transform="translate(21 45) rotate(18)"/>
          <ellipse rx="3.2" ry="8" transform="translate(18.5 60) rotate(2)"/><ellipse rx="3.2" ry="8" transform="translate(21 75) rotate(-16)"/>
          <ellipse rx="3.2" ry="8" transform="translate(28 88) rotate(-36)"/><ellipse rx="3.2" ry="8" transform="translate(39 98) rotate(-58)"/>
          <ellipse rx="3.2" ry="8" transform="translate(91 31) rotate(-35)"/><ellipse rx="3.2" ry="8" transform="translate(99 45) rotate(-18)"/>
          <ellipse rx="3.2" ry="8" transform="translate(101.5 60) rotate(-2)"/><ellipse rx="3.2" ry="8" transform="translate(99 75) rotate(16)"/>
          <ellipse rx="3.2" ry="8" transform="translate(92 88) rotate(36)"/><ellipse rx="3.2" ry="8" transform="translate(81 98) rotate(58)"/>
        </g>
        <text x="60" y="66" text-anchor="middle" font-family="Lora, Georgia, serif" font-size="15" fill="currentColor">★</text>
      </svg>
      <div>
        <p class="eyebrow">Vinh danh<?= $honorWhere !== '' ? ' · ' . e($honorWhere) : '' ?></p>
        <h2 class="honor__title" id="honor-title"><?= e($honorTitle) ?></h2>
        <p class="muted mt-5"><strong><?= e($filmName) ?></strong><?= !empty($featuredFilm['caption']) ? ' · ' . e((string) $featuredFilm['caption']) : '' ?></p>
        <?php if (!empty($featuredFilm['awards_list'])): ?>
          <ul class="awards">
            <?php foreach ($featuredFilm['awards_list'] as $award): ?>
              <li><?= icon('star') ?><span><?= e((string) $award) ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <a class="text-link mt-5" href="<?= e(url((string) $featuredFilm['url'])) ?>">Hành trình của phim <?= e((string) $featuredFilm['title']) ?> <?= icon('arrow-r') ?></a>
      </div>
      <?php if (!empty($featuredFilm['poster'])): ?>
        <div class="honor__poster">
          <div class="media"><?= partial('partials/image', ['media' => $featuredFilm['poster'], 'ratio' => \App\Core\Image::FRAME_PORTRAIT, 'sizes' => '220px']) ?></div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
