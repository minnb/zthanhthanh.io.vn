<?php
/**
 * Hộp chọn ảnh dùng chung (ảnh bìa, chèn ảnh vào bài, poster phim).
 * admin.js nạp danh sách từ /admin/api/media và upload qua /admin/media/upload (JSON).
 */
?>
<dialog class="picker" id="media-picker" aria-labelledby="picker-h"
        data-list-url="<?= e(url('/admin/api/media')) ?>"
        data-upload-url="<?= e(url('/admin/media/upload')) ?>"
        data-base-url="<?= e(rtrim(url('/'), '/')) ?>">
  <div class="picker__head">
    <h2 class="h-3" id="picker-h">Chọn ảnh</h2>
    <button type="button" class="icon-btn" data-picker-close aria-label="Đóng">✕</button>
  </div>
  <div class="picker__upload">
    <label class="btn btn--outline btn--sm">
      Tải ảnh mới lên
      <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-picker-upload hidden>
    </label>
    <span class="xs muted" data-picker-status aria-live="polite"></span>
  </div>
  <ul class="picker__grid" data-picker-grid></ul>
  <p class="small muted" data-picker-empty hidden>Chưa có ảnh nào trong thư viện.</p>
</dialog>
