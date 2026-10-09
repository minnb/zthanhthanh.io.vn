<?php
/**
 * Poster minh họa (SVG) khi phim chưa có ảnh poster thật. Tỉ lệ 2.39:1.
 * Màu lấy từ token --art-* trong tokens.css.
 *
 * @var string $filmTitle
 * @var string|null $caption  Dòng nhỏ dưới tên phim
 */
?>
<svg viewBox="0 0 956 400" role="img" aria-label="Poster minh họa phim <?= e($filmTitle) ?>" preserveAspectRatio="xMidYMid slice">
  <rect width="956" height="400" style="fill:var(--art-bg)"/>
  <rect x="520" y="40" width="356" height="320" style="fill:none;stroke:var(--art-frame)" stroke-width="2" opacity=".55"/>
  <rect x="580" y="90" width="236" height="220" style="fill:none;stroke:var(--art-frame)" stroke-width="2" opacity=".8"/>
  <rect x="640" y="140" width="116" height="120" style="fill:var(--art-red)" opacity=".55"/>
  <circle cx="698" cy="182" r="14" style="fill:var(--art-light)"/>
  <path d="M682 236c2-22 8-34 16-34s14 12 16 34z" style="fill:var(--art-light)"/>
  <text x="70" y="245" font-family="Be Vietnam Pro, sans-serif" font-weight="600" font-size="110" style="fill:var(--art-light)"><?= e($filmTitle) ?></text>
  <?php if (!empty($caption)): ?>
    <text x="74" y="290" font-family="Be Vietnam Pro, sans-serif" font-size="16" letter-spacing="4" style="fill:var(--art-gold)"><?= e(mb_strtoupper($caption)) ?></text>
  <?php endif; ?>
</svg>
