<?php
/**
 * Trang riêng: Không gian sáng tác (Kịch bản "ĐỢI" & Tủ sách của Biên kịch).
 *
 * @var list<array<string, mixed>> $featuredBooks
 * @var list<array{label: string, url?: string}> $breadcrumb
 * @var array<string, mixed> $site
 */
?>
<div class="studio-page">
  <header class="container page-head">
    <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
    <p class="eyebrow mt-2">KHÔNG GIAN SÁNG TÁC · WRITER'S STUDIO</p>
    <h1 class="h-1 mt-1">Kịch bản "ĐỢI" &amp; Tủ sách gối đầu giường</h1>
    <p class="lead mt-3" style="max-width: 48rem;">
      Không gian sáng tác độc bản của biên kịch Thanh Thanh: Nơi lưu giữ trích đoạn kịch bản phim ngắn <em>ĐỢI</em> (Top 7 Inspiring Asia 2026 tại Manila) và 10 cuốn sách nuôi dưỡng tư duy kể chuyện điện ảnh.
    </p>

    <!-- Thanh chuyển nhanh giữa 2 phần -->
    <div class="studio-nav-tabs mt-5">
      <a href="#script-section" class="studio-tab">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        <span>1. Trích đoạn kịch bản "ĐỢI"</span>
      </a>
      <a href="#shelf-section" class="studio-tab">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        <span>2. Tủ sách &amp; Vốn sống (10 cuốn)</span>
      </a>
    </div>
  </header>

  <!-- PHẦN 1: KỊCH BẢN ĐỢI -->
  <div id="script-section" class="studio-section">
    <?= partial('blocks/script-preview', get_defined_vars()) ?>
  </div>

  <!-- PHẦN 2: TỦ SÁCH GỐI ĐẦU GIƯỜNG -->
  <div id="shelf-section" class="studio-section">
    <?= partial('blocks/reading-shelf', get_defined_vars()) ?>
  </div>
</div>
