<?php
/**
 * @var array<string, mixed> $site
 */
$menu = $site['menu'] ?? [];
?>
<footer class="site-footer">
  <div class="container site-footer__grid">
    <div>
      <p class="brand__name"><?= e($site['name'] ?? '') ?></p>
      <?php if (!empty($site['authorBio'])): ?>
        <p class="site-footer__bio"><?= e($site['authorBio']) ?></p>
      <?php endif; ?>
    </div>
    <nav aria-label="Liên kết chân trang">
      <p class="site-footer__title">Khám phá</p>
      <ul class="site-footer__links">
        <?php foreach ($menu as $item): ?>
          <li><a href="<?= e(link_url((string) $item['url'])) ?>"><?= e((string) $item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div>
      <p class="site-footer__title">Kết nối</p>
      <ul class="site-footer__links">
        <?php foreach ($site['social'] ?? [] as $label => $href): ?>
          <li><a href="<?= e((string) $href) ?>" rel="noopener" target="_blank"><?= e((string) $label) ?></a></li>
        <?php endforeach; ?>
        <?php if (!empty($site['contactEmail'])): ?>
          <li><a href="mailto:<?= e($site['contactEmail']) ?>"><?= e($site['contactEmail']) ?></a></li>
        <?php endif; ?>
        <li><a href="<?= e(url('/lien-he')) ?>">Liên hệ</a></li>
      </ul>
    </div>
  </div>
  <p class="site-footer__copy">© <?= date('Y') ?> <?= e($site['name'] ?? '') ?>.</p>
</footer>
