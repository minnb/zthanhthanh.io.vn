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
 * @var bool|null $trackable        false = không gắn beacon thống kê (mặc định true)
 * @var list<string>|null $styles   CSS riêng của trang (bundle trong build_assets.php), vd ['home']
 */
use App\Core\Seo;

$pageTitle = Seo::title($title ?? null, (string) ($site['name'] ?? 'Thanh Thanh'), (string) ($site['tagline'] ?? ''));
// Chỉ nhận tên bundle hợp lệ (a-z, 0-9, -), bỏ qua giá trị lạ
$pageStyles = array_values(array_filter(
    is_array($styles ?? null) ? $styles : [],
    static fn (mixed $s): bool => is_string($s) && preg_match('/^[a-z0-9-]+$/', $s) === 1
));
?><!doctype html>
<html lang="<?= e($lang ?? 'vi') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<?= Seo::headTags(get_defined_vars(), $pageTitle) ?>
<?php /* Ngoại lệ được viết hex (theme-ui.md): thẻ meta không đọc được biến CSS – khớp --paper sáng/tối */ ?>
<meta name="theme-color" content="#FAFAF8" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#12100E" media="(prefers-color-scheme: dark)">
<link rel="icon" href="<?= e(url('/favicon.svg')) ?>" type="image/svg+xml">
<link rel="icon" href="<?= e(url('/favicon-32x32.png')) ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= e(url('/favicon-16x16.png')) ?>" type="image/png" sizes="16x16">
<link rel="apple-touch-icon" href="<?= e(url('/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('/site.webmanifest')) ?>">
<script>try{var t=localStorage.getItem('tt-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
<link rel="preload" href="<?= e(asset('fonts/be-vietnam-pro-400-vietnamese.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/be-vietnam-pro-400-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(asset('fonts/lora-normal-vietnamese.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<?php if (($currentPath ?? '') === '/'): /* tiêu đề hero trang chủ có chữ Lora nghiêng ngay màn hình đầu */ ?>
<link rel="preload" href="<?= e(asset('fonts/lora-italic-vietnamese.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<?php foreach ($pageStyles as $style): ?>
<?php /* CSS riêng của trang (vài KB) nhúng inline: bớt một request chặn render (LCP) so với thêm <link> */ ?>
<?php $pageCss = asset_inline_css($style . '.css'); ?>
<?php if ($pageCss !== ''): ?><style><?= $pageCss /* CSS build từ theme, đã chặn "</style" */ ?></style>
<?php endif; ?>
<?php endforeach; ?>
<?php if (!isset($status) && ($trackable ?? true)): /* trang lỗi, đăng nhập admin, dev: không gửi beacon thống kê */ ?>
<meta name="tt-stats" content="<?= e(url('/t')) ?>">
<?php endif; ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
</head>
<body>
<?= partial('partials/icons') ?>
<a class="skip-link" href="#main">Bỏ qua menu</a>
<?= partial('partials/header') ?>
<main id="main">
<?= $content ?>
</main>
<?= partial('partials/footer') ?>
</body>
</html>
