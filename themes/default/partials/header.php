<?php
/**
 * Header + menu chính + menu di động (lớp phủ).
 *
 * @var array<string, mixed> $site
 * @var string $currentPath
 */
$menu = $site['menu'] ?? [];
?>
<header class="site-header">
  <div class="container site-header__bar">
    <a class="brand" href="<?= e(url('/')) ?>"<?= $currentPath === '/' ? ' aria-current="page"' : '' ?>>
      <span class="brand__name"><?= e($site['name'] ?? '') ?></span>
      <?php if (!empty($site['tagline'])): ?><span class="brand__tag"><?= e($site['tagline']) ?></span><?php endif; ?>
    </a>

    <nav class="site-nav" aria-label="Menu chính">
      <ul class="site-nav__list">
        <?php foreach ($menu as $item): ?>
          <li>
            <a href="<?= e(link_url((string) $item['url'])) ?>"
               <?= ($item['style'] ?? '') === 'button' ? 'class="site-nav__cta"' : '' ?>
               <?= is_current((string) $item['url'], $currentPath) ? 'aria-current="page"' : '' ?>><?= e((string) $item['label']) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="header-actions">
      <button class="icon-btn" type="button" data-theme-toggle aria-label="Bật/tắt chế độ tối">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
      </button>
      <button class="icon-btn menu-toggle" type="button" data-menu-open aria-expanded="false" aria-controls="mobile-menu" aria-label="Mở menu">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h14M3 10h14M3 14h9"/></svg>
      </button>
    </div>
  </div>

  <div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu" hidden>
    <div class="container mobile-menu__bar">
      <span class="brand__name"><?= e($site['name'] ?? '') ?></span>
      <button class="icon-btn" type="button" data-menu-close aria-label="Đóng menu">
        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4l12 12M16 4L4 16"/></svg>
      </button>
    </div>
    <ul class="container mobile-menu__list">
      <li><a href="<?= e(url('/')) ?>"<?= $currentPath === '/' ? ' aria-current="page"' : '' ?>>Trang chủ</a></li>
      <?php foreach ($menu as $item): ?>
        <li><a href="<?= e(link_url((string) $item['url'])) ?>"<?= is_current((string) $item['url'], $currentPath) ? ' aria-current="page"' : '' ?>><?= e((string) $item['label']) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <?php if (!empty($site['social'])): ?>
      <p class="container mobile-menu__foot">
        <?php foreach ($site['social'] as $label => $href): ?>
          <a href="<?= e((string) $href) ?>" rel="noopener" target="_blank"><?= e((string) $label) ?></a>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>
  </div>
</header>
