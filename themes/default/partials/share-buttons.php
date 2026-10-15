<?php
/**
 * Nút chia sẻ: link thuần (không nhúng SDK mạng xã hội để trang nhẹ).
 *
 * @var string $shareUrl  URL tuyệt đối của trang
 * @var string $shareTitle
 */
$u = rawurlencode($shareUrl);
?>
<div class="share">
  <span>Chia sẻ</span>
  <a class="icon-btn" href="https://www.facebook.com/sharer/sharer.php?u=<?= e($u) ?>" target="_blank" rel="noopener" aria-label="Chia sẻ lên Facebook (mở tab mới)"><?= icon('fb', '') ?></a>
  <a class="icon-btn" href="https://www.threads.net/intent/post?text=<?= e(rawurlencode($shareTitle . ' ' . $shareUrl)) ?>" target="_blank" rel="noopener" aria-label="Chia sẻ lên Threads (mở tab mới)"><?= icon('threads', '') ?></a>
  <button class="icon-btn" type="button" data-copy-link="<?= e($shareUrl) ?>" aria-label="Sao chép liên kết bài viết"><?= icon('link', '') ?></button>
</div>
