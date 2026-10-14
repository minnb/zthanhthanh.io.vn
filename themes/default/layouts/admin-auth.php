<?php
/**
 * Layout trang đăng nhập quản trị (chưa có sidebar/tab).
 *
 * @var string $content
 * @var string $title
 * @var array<string, mixed> $site
 */
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> – <?= e($site['name'] ?? '') ?></title>
<link rel="icon" href="<?= e(url('/favicon.svg')) ?>" type="image/svg+xml">
<link rel="icon" href="<?= e(url('/favicon-32x32.png')) ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= e(url('/favicon-16x16.png')) ?>" type="image/png" sizes="16x16">
<link rel="apple-touch-icon" href="<?= e(url('/apple-touch-icon.png')) ?>">
<script>try{var t=localStorage.getItem('tt-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body class="admin admin--auth">
<main id="admin-main" class="auth-card">
<?= $content ?>
</main>
</body>
</html>
