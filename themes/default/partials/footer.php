<?php
/**
 * @var array<string, mixed> $site
 */
$menu = $site['menu'] ?? [];
$contactEmail = (string) ($site['contactEmail'] ?? 'doicho147@gmail.com');
$contactAddress = (string) ($site['contactAddress'] ?? 'Thôn Vân Sa, xã Cổ Đô, Hà Nội');
?>
<footer class="site-footer">
  <div class="container site-footer__grid">
    <div class="site-footer__col-brand">
      <p class="brand__name"><?= e($site['name'] ?? 'Thanh Thanh') ?></p>
      <p class="site-footer__tagline">Biên kịch · Visual Storyteller</p>
      <?php if (!empty($site['authorBio'])): ?>
        <p class="site-footer__bio"><?= e($site['authorBio']) ?></p>
      <?php else: ?>
        <p class="site-footer__bio">Kể những câu chuyện độc bản từ lăng kính thấu cảm, đưa lát cắt đời sống chân thực vào điện ảnh Việt Nam.</p>
      <?php endif; ?>
      <div class="site-footer__contact-info">
        <p class="site-footer__address">
          <svg class="footer-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <span><?= e($contactAddress) ?></span>
        </p>
        <p class="site-footer__mail">
          <svg class="footer-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          <a href="mailto:<?= e($contactEmail) ?>" class="site-footer__mail-link"><?= e($contactEmail) ?></a>
        </p>
      </div>
    </div>

    <nav class="site-footer__col-nav" aria-label="Liên kết chân trang">
      <p class="site-footer__title">Khám phá</p>
      <ul class="site-footer__links">
        <?php foreach ($menu as $item): ?>
          <li><a href="<?= e(link_url((string) $item['url'])) ?>"><?= e((string) $item['label']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= e(url('/press-kit')) ?>">Press Kit</a></li>
      </ul>
    </nav>

    <div class="site-footer__col-social">
      <p class="site-footer__title">Hợp tác & Kết nối</p>
      <p class="site-footer__text">Mở lời cho kịch bản phim, pitching dự án, hoặc trao đổi góc nhìn điện ảnh.</p>
      <div class="social-icon-row" aria-label="Mạng xã hội và liên hệ">
        <?php if (!empty($site['social']['Facebook'])): ?>
          <a class="social-icon-btn" href="<?= e($site['social']['Facebook']) ?>" target="_blank" rel="noopener" aria-label="Facebook Thanh Thanh" title="Facebook">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
        <?php endif; ?>
        <?php if (!empty($site['social']['Threads'])): ?>
          <a class="social-icon-btn" href="<?= e($site['social']['Threads']) ?>" target="_blank" rel="noopener" aria-label="Threads Thanh Thanh" title="Threads">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.186 24C5.467 24 0 18.663 0 12.186 0 5.617 5.385 0 12.094 0 18.647 0 24 5.28 24 11.979c0 7.02-5.46 12.021-11.814 12.021zm-.093-21.947c-5.433 0-9.84 4.32-9.84 9.947 0 5.541 4.494 10.026 9.84 10.026 4.793 0 8.877-3.486 9.686-8.291H18.96c-.689 3.255-3.543 5.688-6.96 5.688-4.041 0-7.306-3.266-7.306-7.423 0-4.07 3.177-7.337 7.213-7.337 3.518 0 6.136 2.454 6.784 5.922h2.721c-.702-4.887-4.67-8.532-9.319-8.532z"/></svg>
          </a>
        <?php endif; ?>
        <?php if (!empty($site['social']['TikTok'])): ?>
          <a class="social-icon-btn" href="<?= e($site['social']['TikTok']) ?>" target="_blank" rel="noopener" aria-label="TikTok Thanh Thanh" title="TikTok">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.298-.002.595.042.88.13V9.4a6.33 6.33 0 0 0-1-.08A6.34 6.34 0 0 0 3 15.66a6.34 6.34 0 0 0 10.82 4.47 6.27 6.27 0 0 0 1.86-4.47V8.72a8.28 8.28 0 0 0 4.91 1.6V6.87a4.8 4.8 0 0 1-1-.18z"/></svg>
          </a>
        <?php endif; ?>
        <a class="social-icon-btn" href="mailto:<?= e($contactEmail) ?>" aria-label="Gửi email cho Thanh Thanh" title="Gửi Email">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        </a>
      </div>
      <div class="mt-2">
        <a class="btn btn--outline btn--sm" href="<?= e(url('/lien-he')) ?>">Trang Liên hệ chi tiết →</a>
      </div>
    </div>
  </div>

  <div class="site-footer__bottom">
    <div class="container site-footer__bottom-inner">
      <p class="site-footer__copy">© <?= date('Y') ?> <?= e($site['name'] ?? 'Thanh Thanh') ?> · Screenwriter & Storyteller.</p>
      <p class="site-footer__quote"><em>"Không để người khác quyết định giới hạn của bản thân."</em></p>
    </div>
  </div>
</footer>
