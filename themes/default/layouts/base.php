<?php
/**
 * Layout chung trang công khai. Mọi thẻ SEO (title, description, canonical, robots, hreflang,
 * Open Graph, Twitter, JSON-LD) sinh qua Core/Seo – không viết tay ở đây (seo.md).
 *
 * @var string $content
 * @var array<string, mixed> $site
 * @var string $currentPath
 * @var string|null $title          Tiêu đề trang (không gồm tên site)
 * @var string|null $description
 * @var bool|null $noindex
 * @var string|null $lang           Mặc định "vi"
 * @var string|null $canonicalPath  Mặc định = $currentPath
 */
use App\Core\Seo;

$pageTitle = Seo::title($title ?? null, (string) ($site['name'] ?? 'Thanh Thanh'), (string) ($site['tagline'] ?? ''));
?><!doctype html>
<html lang="<?= e($lang ?? 'vi') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<?= Seo::headTags(get_defined_vars(), $pageTitle) ?>
<meta name="theme-color" content="#F7F1E8" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1C1714" media="(prefers-color-scheme: dark)">
<link rel="icon" href="<?= e(url('/favicon.svg')) ?>" type="image/svg+xml">
<link rel="icon" href="<?= e(url('/favicon-32x32.png')) ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= e(url('/favicon-16x16.png')) ?>" type="image/png" sizes="16x16">
<link rel="apple-touch-icon" href="<?= e(url('/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('/site.webmanifest')) ?>">
<script>try{var t=localStorage.getItem('tt-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
<link rel="preload" href="<?= e(asset('fonts/be-vietnam-pro-400-vietnamese.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/be-vietnam-pro-400-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<script src="<?= e(asset('app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Bỏ qua menu</a>
<?= partial('partials/header') ?>
<main id="main">
<?= $content ?>
</main>
<?= partial('partials/footer') ?>
</body>
</html>
