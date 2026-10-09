<?php
/** Ảnh chân dung minh họa (SVG), thay bằng ảnh thật khi có. Tỉ lệ 4:5. Màu lấy từ token --art-*. */
?>
<svg viewBox="0 0 400 500" role="img" aria-label="Ảnh chân dung tác giả (minh họa)" preserveAspectRatio="xMidYMid slice">
  <defs>
    <linearGradient id="tt-portrait-bg" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" style="stop-color:var(--art-gold);stop-opacity:.35"/>
      <stop offset="1" style="stop-color:var(--art-red);stop-opacity:.55"/>
    </linearGradient>
  </defs>
  <rect width="400" height="500" fill="url(#tt-portrait-bg)"/>
  <circle cx="200" cy="190" r="62" style="fill:var(--art-ink)" opacity=".85"/>
  <path d="M95 500c8-120 55-190 105-190s97 70 105 190z" style="fill:var(--art-ink)" opacity=".85"/>
  <circle cx="300" cy="420" r="58" style="fill:none;stroke:var(--art-paper)" stroke-width="6" opacity=".7"/>
</svg>
