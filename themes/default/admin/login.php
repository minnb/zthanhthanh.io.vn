<?php
/**
 * @var string $error
 * @var string $email
 * @var string $next
 * @var string $csrf
 * @var array<string, mixed> $site
 */
?>
<p class="eyebrow"><?= e($site['name'] ?? '') ?></p>
<h1 class="h-2 mt-2">Đăng nhập quản trị</h1>
<?php if ($error !== ''): ?>
  <p class="form-error mt-5" role="alert"><?= e($error) ?></p>
<?php endif; ?>
<form method="post" action="<?= e(url('/admin/login')) ?>" class="form mt-5" novalidate>
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="next" value="<?= e($next) ?>">
  <div class="field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" autofocus>
  </div>
  <div class="field">
    <label for="password">Mật khẩu</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
  </div>
  <label class="check"><input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập (30 ngày)</label>
  <button type="submit" class="btn btn--primary btn--block">Đăng nhập</button>
</form>
<p class="small muted mt-5"><a href="<?= e(url('/')) ?>">← Về trang chủ</a></p>
