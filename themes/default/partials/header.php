<?php
/**
 * Header: thương hiệu "Thanh Thanh · Biên kịch", menu chính, nút pill (mục menu style=button),
 * nút sáng/tối, menu di động (lớp phủ toàn màn hình, app.js quản lý inert + Esc).
 *
 * @var array<string, mixed> $site
 * @var string $currentPath
 */
$menu = is_array($site['menu'] ?? null) ? $site['menu'] : [];
$links = array_values(array_filter($menu, static fn (array $i): bool => ($i['style'] ?? '') !== 'button'));
$pills = array_values(array_filter($menu, static fn (array $i): bool => ($i['style'] ?? '') === 'button'));
$current = static fn (array $i): string => is_current((string) $i['url'], $currentPath) ? ' aria-current="page"' : '';
$brand = static function () use ($site, $currentPath): string {
    return '<a class="brand" href="' . e(url('/')) . '"' . ($currentPath === '/' ? ' aria-current="page"' : '') . '>'
        . '<span class="brand__name">' . e((string) ($site['name'] ?? '')) . '</span>'
        . (!empty($site['tagline']) ? '<span class="brand__sep" aria-hidden="true">·</span><span class="brand__tag">' . e((string) $site['tagline']) . '</span>' : '')
        . '</a>';
};
?>
<header class="site-header" data-header>
  <div class="container site-header__bar">
    <?= $brand() ?>

    <nav class="site-nav" aria-label="Menu chính">
      <ul class="site-nav__list">
        <?php foreach ($links as $item): ?>
          <li><a href="<?= e(link_url((string) $item['url'])) ?>"<?= $current($item) ?>><?= e((string) $item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="header-actions">
      <?php foreach ($pills as $item): ?>
        <a class="pill-link" href="<?= e(link_url((string) $item['url'])) ?>"<?= $current($item) ?>><?= e((string) $item['label']) ?></a>
      <?php endforeach; ?>
      <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="Chế độ tối">
        <?= icon('sun', 'i-sun') ?><?= icon('moon', 'i-moon') ?>
      </button>
      <button class="icon-btn menu-toggle" type="button" data-menu-open aria-expanded="false" aria-controls="mobile-menu" aria-label="Mở menu">
        <?= icon('menu', '') ?>
      </button>
    </div>
  </div>

  <div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu" hidden>
    <div class="container mobile-menu__bar">
      <?= $brand() ?>
      <button class="icon-btn" type="button" data-menu-close aria-label="Đóng menu"><?= icon('close', '') ?></button>
    </div>
    <ul class="container mobile-menu__list">
      <li><a href="<?= e(url('/')) ?>"<?= $currentPath === '/' ? ' aria-current="page"' : '' ?>>Trang chủ</a></li>
      <?php foreach ($menu as $item): ?>
        <li><a href="<?= e(link_url((string) $item['url'])) ?>"<?= $current($item) ?>><?= e((string) $item['label']) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <?php if (!empty($site['social'])): ?>
      <p class="container mobile-menu__foot">
        <?php foreach ($site['social'] as $label => $href): ?>
          <a href="<?= e(link_url((string) $href)) ?>" rel="noopener" target="_blank"><?= e((string) $label) ?></a>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>
  </div>
</header>
