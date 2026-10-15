<?php
/**
 * Màn "Thêm" trên điện thoại.
 *
 * @var array<string, mixed> $user
 * @var string $csrf
 */
$isAdmin = \App\Core\Auth::can($user, 'admin');
?>
<header class="admin-head"><h1 class="h-2">Thêm</h1></header>
<ul class="menu-list">
  <li><a href="<?= e(url('/admin/stats')) ?>">Thống kê lượt truy cập</a></li>
  <li><a href="<?= e(url('/admin/films')) ?>">Phim & dòng thời gian</a></li>
  <li><a href="<?= e(url('/admin/pages')) ?>">Trang tĩnh (Giới thiệu, Liên hệ, Press kit)</a></li>
  <li><a href="<?= e(url('/admin/posts?trash=1')) ?>">Thùng rác</a></li>
  <?php if ($isAdmin): ?><li><a href="<?= e(url('/admin/settings')) ?>">Cài đặt</a></li><?php endif; ?>
  <li><a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Xem website ↗</a></li>
</ul>
<p class="small muted mt-5"><?= e($user['name']) ?> · <?= $isAdmin ? 'Quản trị viên' : 'Biên tập' ?></p>
<form method="post" action="<?= e(url('/admin/logout')) ?>" class="mt-2">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <button type="submit" class="btn btn--outline btn--block">Đăng xuất</button>
</form>
<form method="post" action="<?= e(url('/admin/logout-all')) ?>" class="mt-2" data-confirm="Đăng xuất khỏi mọi thiết bị (điện thoại, máy tính đã chọn “Ghi nhớ đăng nhập”)?">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <button type="submit" class="btn btn--ghost btn--block">Đăng xuất mọi thiết bị</button>
</form>
