<?php
/**
 * Layout trang quản trị (admin.md): sidebar từ 960px, thanh tab đáy trên điện thoại, toast.
 *
 * @var string $content
 * @var string $title
 * @var array<string, mixed> $user
 * @var list<array{type: string, message: string}> $flash
 * @var string $csrf
 * @var string $currentPath
 * @var array<string, mixed> $site
 */
use App\Core\Auth;

$isAdmin = Auth::can($user, 'admin');
$nav = [
    ['/admin', 'Tổng quan', 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    ['/admin/posts', 'Bài viết', 'M4 6h16M4 12h16M4 18h10'],
    ['/admin/media', 'Thư viện ảnh', 'M4 5h16v14H4zM4 15l4-4 4 4 3-3 5 5'],
    ['/admin/films', 'Phim', 'M4 4h16v16H4zM8 4v16M16 4v16M4 9h4M16 9h4M4 15h4M16 15h4'],
    ['/admin/pages', 'Trang tĩnh', 'M6 3h9l4 4v14H6zM15 3v4h4'],
];
if ($isAdmin) {
    $nav[] = ['/admin/settings', 'Cài đặt', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19 12l2-1-2-4-2 1-2-2V4h-4v2l-2 2-2-1-2 4 2 1v0l-2 1 2 4 2-1 2 2v2h4v-2l2-2 2 1 2-4z'];
}
$active = static function (string $href) use ($currentPath): bool {
    return $href === '/admin' ? $currentPath === '/admin' : ($currentPath === $href || str_starts_with($currentPath, $href . '/'));
};
$icon = static fn (string $d): string => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true"><path d="' . e($d) . '"/></svg>';
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e($csrf) ?>">
<title><?= e($title) ?> – Quản trị <?= e($site['name'] ?? '') ?></title>
<script>try{var t=localStorage.getItem('tt-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
<link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
<script src="<?= e(asset('admin.js')) ?>" defer></script>
</head>
<body class="admin">
<a class="skip-link" href="#admin-main">Bỏ qua menu</a>

<aside class="admin-sidebar" aria-label="Menu quản trị">
  <div class="admin-sidebar__brand">
    <a href="<?= e(url('/admin')) ?>"><?= e($site['name'] ?? '') ?></a>
    <span>Quản trị</span>
  </div>
  <form method="post" action="<?= e(url('/admin/posts/new')) ?>" class="admin-sidebar__new">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <button type="submit" class="btn btn--primary">＋ Viết bài mới</button>
  </form>
  <nav>
    <ul>
      <?php foreach ($nav as [$href, $label, $path]): ?>
        <li><a href="<?= e(url($href)) ?>"<?= $active($href) ? ' aria-current="page"' : '' ?>><?= $icon($path) ?><?= e($label) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>
  <div class="admin-sidebar__foot">
    <p class="xs muted"><?= e($user['name']) ?> · <?= $isAdmin ? 'Quản trị viên' : 'Biên tập' ?></p>
    <a class="small" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Xem website ↗</a>
    <form method="post" action="<?= e(url('/admin/logout')) ?>">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <button type="submit" class="admin-link-btn small">Đăng xuất</button>
    </form>
    <form method="post" action="<?= e(url('/admin/logout-all')) ?>" data-confirm="Đăng xuất khỏi mọi thiết bị?">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <button type="submit" class="admin-link-btn xs">Đăng xuất mọi thiết bị</button>
    </form>
  </div>
</aside>

<div class="admin-shell">
  <main id="admin-main" class="admin-main">
    <?= $content ?>
  </main>
</div>

<nav class="admin-tabbar" aria-label="Menu quản trị (điện thoại)">
  <a href="<?= e(url('/admin')) ?>"<?= $active('/admin') ? ' aria-current="page"' : '' ?>><?= $icon($nav[0][2]) ?><span>Tổng quan</span></a>
  <a href="<?= e(url('/admin/posts')) ?>"<?= $active('/admin/posts') ? ' aria-current="page"' : '' ?>><?= $icon($nav[1][2]) ?><span>Bài viết</span></a>
  <form method="post" action="<?= e(url('/admin/posts/new')) ?>">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <button type="submit" class="admin-tabbar__new" aria-label="Viết bài mới">＋</button>
  </form>
  <a href="<?= e(url('/admin/media')) ?>"<?= $active('/admin/media') ? ' aria-current="page"' : '' ?>><?= $icon($nav[2][2]) ?><span>Ảnh</span></a>
  <a href="<?= e(url('/admin/them')) ?>"<?= $active('/admin/them') ? ' aria-current="page"' : '' ?>><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg><span>Thêm</span></a>
</nav>

<div class="toasts" role="status" aria-live="polite">
  <?php foreach ($flash as $item): ?>
    <p class="toast toast--<?= e($item['type']) ?>"><?= e($item['message']) ?></p>
  <?php endforeach; ?>
</div>
</body>
</html>
