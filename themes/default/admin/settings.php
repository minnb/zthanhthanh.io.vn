<?php
/**
 * Cài đặt site (chỉ admin).
 *
 * @var array<string, array{0: string, 1: string, 2: int}> $fields
 * @var array<string, string> $values
 * @var array<string, string> $errors
 * @var string $csrf
 */
?>
<header class="admin-head"><h1 class="h-2">Cài đặt</h1></header>

<?php if ($errors !== []): ?>
  <div class="notice notice--error" role="alert"><strong>Chưa lưu được:</strong> kiểm tra các ô báo lỗi bên dưới.</div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/settings')) ?>" class="panel form">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <?php foreach ($fields as $key => [$label, $type, $max]): ?>
    <div class="field">
      <label for="<?= e($key) ?>"><?= e($label) ?></label>
      <?php $aria = isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : ''; ?>
      <?php if ($type === 'textarea'): ?>
        <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3" maxlength="<?= (int) $max ?>"<?= $aria ?>><?= e($values[$key] ?? '') ?></textarea>
      <?php else: ?>
        <input id="<?= e($key) ?>" name="<?= e($key) ?>" type="<?= $type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text') ?>" maxlength="<?= (int) $max ?>" value="<?= e($values[$key] ?? '') ?>"<?= $aria ?>>
      <?php endif; ?>
      <?php if (isset($errors[$key])): ?><p class="field__error" id="err-<?= e($key) ?>"><?= e($errors[$key]) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <button type="submit" class="btn btn--primary">Lưu cài đặt</button>
</form>

<section class="panel mt-5">
  <h2 class="panel__title">Cache trang</h2>
  <p class="small muted">Cache tự xóa mỗi khi lưu bài/phim/trang. Chỉ cần bấm nút này nếu trang ngoài hiển thị chưa đúng.</p>
  <form method="post" action="<?= e(url('/admin/settings/cache')) ?>">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <button type="submit" class="btn btn--outline">Xóa toàn bộ cache</button>
  </form>
</section>
