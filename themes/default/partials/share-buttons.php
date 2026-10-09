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
  <span class="share__label">Chia sẻ:</span>
  <a class="share__btn" href="https://www.facebook.com/sharer/sharer.php?u=<?= e($u) ?>" target="_blank" rel="noopener">Facebook</a>
  <a class="share__btn" href="https://www.threads.net/intent/post?text=<?= e(rawurlencode($shareTitle . ' ' . $shareUrl)) ?>" target="_blank" rel="noopener">Threads</a>
  <button class="share__btn" type="button" data-copy-link="<?= e($shareUrl) ?>">Sao chép link</button>
</div>
