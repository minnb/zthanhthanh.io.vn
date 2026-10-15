<?php
/**
 * Lời mời dành cho đạo diễn & nhà sản xuất (khối cuối trang chủ).
 *
 * @var array<string, mixed> $site
 */
$contactEmail = (string) ($site['contactEmail'] ?? '');
?>
<section class="section" aria-labelledby="invite-title">
  <div class="container invite">
    <p class="eyebrow">Dành cho đạo diễn &amp; nhà sản xuất</p>
    <h2 class="h-2" id="invite-title">Mình luôn sẵn lòng gửi trọn bộ Full Script, Treatment và Pitch Deck.</h2>
    <p class="lead">Cho các đạo diễn, nhà sản xuất và liên hoan phim quan tâm đến những câu chuyện chân thật, không bi kịch hóa, không thương hại.</p>
    <div class="btn-row btn-row--stack">
      <?php if ($contactEmail !== ''): ?>
        <a class="btn btn--primary" href="mailto:<?= e($contactEmail) ?>?subject=<?= rawurlencode('[Screenplay Request] Yêu cầu kịch bản hoàn chỉnh: ĐỢI') ?>"><?= icon('mail') ?>Nhận Full Script qua Email</a>
      <?php endif; ?>
      <a class="btn btn--outline" href="<?= e(url('/lien-he')) ?>">Gặp gỡ Pitching</a>
    </div>
  </div>
</section>
