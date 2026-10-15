<?php
/**
 * Trang lỗi chung (404, 405, 403...). Mã HTTP đã được front controller đặt sẵn.
 *
 * @var int $status
 * @var string $message
 */
?>
<div class="container error-page">
  <p class="eyebrow eyebrow--accent">Lỗi <?= (int) $status ?></p>
  <h1 class="h-1"><?= $status === 404 ? 'Cảnh này không có trong <em>kịch bản</em>' : 'Đã có lỗi xảy ra' ?></h1>
  <p class="lead">
    <?= $status === 404
        ? 'Trang bạn tìm có thể đã đổi địa chỉ hoặc không còn tồn tại.'
        : e($message) ?>
  </p>
  <div class="btn-row btn-row--stack">
    <a class="btn btn--primary" href="<?= e(url('/')) ?>">Về trang chủ</a>
    <a class="btn btn--outline" href="<?= e(url('/bai-viet')) ?>">Đọc bài viết</a>
  </div>
</div>
