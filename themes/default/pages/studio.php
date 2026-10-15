<?php
/**
 * Góc sáng tác: Kịch bản ĐỢI & Tủ sách gối đầu giường, chuyển bằng tab ARIA (app.js).
 * Không có JS: thanh tab ẩn, cả hai phần hiển thị nối tiếp. /tu-sach mở sẵn tab Tủ sách.
 *
 * @var list<array<string, mixed>> $featuredBooks
 * @var list<array{label: string, url?: string}> $breadcrumb
 * @var array<string, mixed> $site
 * @var string $currentPath
 */
$active = $currentPath === '/tu-sach' ? 'books' : 'script';
?>
<header class="container page-head">
  <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
  <p class="eyebrow"><span>Không gian sáng tác · <span lang="en">Writer’s Studio</span></span></p>
  <h1 class="h-1">Kịch bản “ĐỢI” &amp; <em>Tủ sách gối đầu giường</em></h1>
  <p class="lead">Góc nhỏ lưu giữ trích đoạn kịch bản phim ngắn ĐỢI (Top 7 Inspiring Asia 2026 tại Manila)<?= !empty($featuredBooks) ? ' và ' . count($featuredBooks) . ' cuốn sách đã đồng hành cùng Thanh Thanh trong hành trình tự học viết kịch bản' : '' ?>.</p>
  <div class="tabs" role="tablist" aria-label="Không gian sáng tác" data-tabs data-active="<?= e($active) ?>" hidden>
    <button class="tab" type="button" role="tab" id="tab-script" aria-controls="panel-script" aria-selected="false" data-tab="script"><?= icon('script') ?>Kịch bản “ĐỢI”</button>
    <?php if (!empty($featuredBooks)): ?>
      <button class="tab" type="button" role="tab" id="tab-books" aria-controls="panel-books" aria-selected="false" data-tab="books"><?= icon('book') ?>Tủ sách · <?= count($featuredBooks) ?> cuốn</button>
    <?php endif; ?>
  </div>
</header>

<div class="container section section--flush-top">
  <?= partial('blocks/script-preview', ['inStudio' => true] + get_defined_vars()) ?>
  <?= partial('blocks/reading-shelf', ['inStudio' => true] + get_defined_vars()) ?>
</div>
