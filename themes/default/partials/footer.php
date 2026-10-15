<?php
/**
 * Footer tinh giản: thương hiệu + giới thiệu ngắn, liên kết khám phá (menu chính), kênh kết nối.
 *
 * @var array<string, mixed> $site
 */
$menu = is_array($site['menu'] ?? null) ? $site['menu'] : [];
$social = is_array($site['social'] ?? null) ? $site['social'] : [];
$socialIcons = ['Facebook' => 'fb', 'Threads' => 'threads', 'TikTok' => 'tiktok'];
$contactEmail = (string) ($site['contactEmail'] ?? '');
?>
<footer class="site-footer">
  <div class="container">
    <div class="site-footer__grid">
      <div>
        <a class="brand" href="<?= e(url('/')) ?>">
          <span class="brand__name"><?= e((string) ($site['name'] ?? 'Thanh Thanh')) ?></span>
          <?php if (!empty($site['tagline'])): ?><span class="brand__sep" aria-hidden="true">·</span><span class="brand__tag"><?= e((string) $site['tagline']) ?></span><?php endif; ?>
        </a>
        <p class="site-footer__bio"><?= e((string) (($site['authorBio'] ?? '') ?: ($site['mission'] ?? ''))) ?></p>
      </div>

      <nav aria-labelledby="footer-explore">
        <h2 class="site-footer__title" id="footer-explore">Khám phá</h2>
        <ul class="site-footer__links">
          <?php foreach ($menu as $item): ?>
            <li><a href="<?= e(link_url((string) $item['url'])) ?>"><?= e((string) $item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div>
        <h2 class="site-footer__title">Kết nối</h2>
        <ul class="site-footer__links">
          <?php foreach ($social as $label => $href): ?>
            <li><a href="<?= e(link_url((string) $href)) ?>" target="_blank" rel="noopener"><?= icon($socialIcons[$label] ?? 'link') ?><?= e((string) $label) ?></a></li>
          <?php endforeach; ?>
          <?php if ($contactEmail !== ''): ?>
            <li><a href="mailto:<?= e($contactEmail) ?>"><?= icon('mail') ?>Email</a></li>
          <?php endif; ?>
          <li><a href="<?= e(url('/lien-he')) ?>"><?= icon('arrow-r') ?>Liên hệ &amp; Pitching</a></li>
        </ul>
      </div>
    </div>
    <div class="site-footer__bottom">
      <p>© <?= date('Y') ?> <?= e((string) ($site['name'] ?? 'Thanh Thanh')) ?><?= !empty($site['tagline']) ? ' · ' . e((string) $site['tagline']) : '' ?></p>
      <p class="site-footer__quote">“Không để người khác quyết định giới hạn của bản thân.”</p>
    </div>
  </div>
</footer>
