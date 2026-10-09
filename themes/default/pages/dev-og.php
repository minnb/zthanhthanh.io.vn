<?php
/**
 * Khung 1200×630 cho ảnh chia sẻ mặc định (chỉ local, chụp bằng Edge headless).
 *
 * @var array<string, mixed> $site
 * @var array<string, mixed>|null $film
 */
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex">
<title>OG</title>
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<style>
  /* Chỉ dùng cho trang chụp ảnh, không nằm trong app.css công khai */
  .og-card { width: 1200px; height: 630px; margin: 0; overflow: hidden; position: relative; background: var(--art-bg); }
  .og-card__art { position: absolute; inset: 0 0 150px 0; }
  .og-card__art svg { width: 100%; height: 100%; }
  .og-card__text { position: absolute; left: 0; right: 0; bottom: 0; height: 150px; padding: 26px 64px;
    background: var(--accent); color: var(--on-accent); }
  .og-card__name { font-size: 34px; font-weight: 600; line-height: 1.2; }
  .og-card__name span { font-size: 18px; letter-spacing: .2em; text-transform: uppercase; opacity: .85; margin-left: 12px; }
  .og-card__mission { margin-top: 10px; font-size: 26px; line-height: 1.35; }
</style>
</head>
<body class="og-card">
  <div class="og-card__art"><?= partial('partials/art-poster', ['filmTitle' => (string) ($film['title'] ?? ''), 'caption' => (string) ($film['caption'] ?? '')]) ?></div>
  <div class="og-card__text">
    <p class="og-card__name"><?= e($site['name'] ?? '') ?> <span><?= e($site['tagline'] ?? '') ?></span></p>
    <p class="og-card__mission"><?= e($site['mission'] ?? '') ?></p>
  </div>
</body>
</html>
