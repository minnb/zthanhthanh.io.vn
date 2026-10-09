<?php
/**
 * Trang lỗi chung (404, 405, 403...). Mã HTTP đã được front controller đặt sẵn.
 *
 * @var int $status
 * @var string $message
 */
?>
<div class="container error-page">
  <p class="eyebrow">Lỗi <?= (int) $status ?></p>
  <h1 class="h-1"><?= $status === 404 ? 'Cảnh này không có trong kịch bản' : 'Đã có lỗi xảy ra' ?></h1>
  <p class="lead">
    <?= $status === 404
        ? 'Trang bạn tìm có thể đã đổi địa chỉ hoặc không còn tồn tại.'
        : e($message) ?>
  </p>
  <div class="btn-row mt-5">
    <a class="btn btn--primary" href="<?= e(url('/')) ?>">Về trang chủ</a>
    <a class="btn btn--outline" href="<?= e(url('/hau-truong-bien-kich')) ?>">Đọc hậu trường</a>
  </div>
</div>
