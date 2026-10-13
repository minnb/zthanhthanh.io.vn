<?php
/**
 * Trang chẩn đoán (chỉ local).
 *
 * @var string $php
 * @var array<string, bool> $extensions
 * @var array<string, string> $limits
 * @var array{ok: bool, error: string, version: string, tables: array<string, int>} $db
 * @var string $baseUrl
 * @var string $slugSample
 */
?>
<h1 class="h-1">Chẩn đoán hệ thống</h1>
<p class="muted">Chỉ hiển thị khi env = local. Xem thêm: <a class="text-accent" href="<?= e(url('/_dev/ui')) ?>">thư viện giao diện</a>.</p>

<h2 class="h-3">PHP <?= e($php) ?></h2>
<table class="dev-table">
  <?php foreach ($extensions as $name => $loaded): ?>
    <tr><th><?= e($name) ?></th><td class="<?= $loaded ? 'ok' : 'bad' ?>"><?= $loaded ? 'Đã bật' : 'Chưa bật' ?></td></tr>
  <?php endforeach; ?>
</table>

<h2 class="h-3">Giới hạn PHP (upload ảnh)</h2>
<table class="dev-table">
  <?php foreach ($limits as $name => $value): ?>
    <tr><th><?= e($name) ?></th><td data-limit="<?= e($name) ?>"><?= e($value) ?></td></tr>
  <?php endforeach; ?>
</table>

<h2 class="h-3">Cơ sở dữ liệu</h2>
<?php if ($db['ok']): ?>
  <p class="ok">Kết nối thành công · <?= e($db['version']) ?> · <?= count($db['tables']) ?> bảng</p>
  <table class="dev-table">
    <tr><th>Bảng</th><th>Số dòng</th></tr>
    <?php foreach ($db['tables'] as $table => $count): ?>
      <tr><td><?= e($table) ?></td><td><?= (int) $count ?></td></tr>
    <?php endforeach; ?>
  </table>
<?php else: ?>
  <p class="bad">Không kết nối được: <?= e($db['error']) ?></p>
<?php endif; ?>

<h2 class="h-3">Khác</h2>
<table class="dev-table">
  <tr><th>base_url</th><td><?= e($baseUrl) ?></td></tr>
  <tr><th>Slug mẫu</th><td><?= e($slugSample) ?></td></tr>
</table>
