<?php
/**
 * Thư viện giao diện (chỉ local): xem trước các partial/thành phần của Theme 2026 với dữ liệu mẫu.
 *
 * @var array<string, mixed> $site
 * @var list<array<string, mixed>> $samplePosts
 * @var list<array<string, mixed>> $milestones
 */
?>
<h1 class="h-1">Thư viện giao diện</h1>
<p class="muted">Dữ liệu mẫu, chỉ hiển thị ở local. Mockup gốc: docs/theme/preview.html.</p>

<h2 class="h-3 mt-8">Breadcrumb</h2>
<div class="mt-2">
  <?= partial('partials/breadcrumb', ['items' => [
      ['label' => 'Trang chủ', 'url' => '/'],
      ['label' => 'Hậu trường biên kịch', 'url' => '/hau-truong-bien-kich'],
      ['label' => 'Không để người khác quyết định giới hạn…'],
  ]]) ?>
</div>

<h2 class="h-3 mt-8">Nút, link & nhãn</h2>
<div class="btn-row mt-5">
  <a class="btn btn--primary" href="#">Nút chính <?= icon('arrow-r', 'i--sm i--move') ?></a>
  <a class="btn btn--outline" href="#">Nút phụ</a>
  <a class="text-link" href="#">Link chữ <?= icon('arrow-r') ?></a>
</div>
<p class="btn-row mt-5">
  <span class="badge label">Hậu trường biên kịch</span>
  <span class="badge badge--gold label"><?= icon('star') ?>Top 7 Grand Finalists</span>
  <span class="badge badge--neutral label">Draft 3</span>
</p>

<h2 class="h-3 mt-8">Thẻ bài viết</h2>
<ul class="grid grid--3 mt-5" role="list">
  <?php foreach ($samplePosts as $post): ?>
    <li><?= partial('partials/post-card', ['post' => $post]) ?></li>
  <?php endforeach; ?>
</ul>

<h2 class="h-3 mt-8">Nội dung bài (prose)</h2>
<div class="prose mt-5">
  <p>Hôm qua ngồi xem series <em>Agency</em>, nghe câu thoại của nhân vật chính, mình phải note lại liền: <strong>“Không để người khác quyết định giới hạn của bản thân.”</strong></p>
  <h2>Nhận vai, rồi tập như thật</h2>
  <p>Nhưng vai An mình vẫn nhận. Thứ nhất là vì kinh phí làm phim ít, thứ hai là mình muốn trải nghiệm một lần ở vị trí mới. <a href="#">Link trong bài</a>.</p>
  <blockquote><p>Giới hạn của bản thân đâu thể nằm ở mồm người khác. Giới hạn của mình nằm ở việc mình có dám dấn thân.</p></blockquote>
  <ul><li>Mục danh sách một</li><li>Mục danh sách hai</li></ul>
</div>

<div class="post-foot">
  <ul class="tag-list"><li><a class="tag" href="#">#hautruongbienkich</a></li><li><a class="tag" href="#">#PhimngắnĐỢI</a></li></ul>
  <?= partial('partials/share-buttons', ['shareUrl' => url('/hau-truong-bien-kich/vi-du'), 'shareTitle' => 'Bài viết mẫu']) ?>
</div>
<div class="article-aside"><?= partial('partials/author-box') ?></div>

<h2 class="h-3 mt-8">Dòng thời gian</h2>
<div class="mt-5"><?= partial('partials/timeline', ['milestones' => $milestones]) ?></div>

<h2 class="h-3 mt-8">Hộp CTA</h2>
<div class="cta-box mt-5">
  <div><p class="h-3">Báo chí &amp; chiếu phim</p><p>Press kit tiếng Anh, poster độ phân giải cao.</p></div>
  <a class="btn btn--primary" href="#">Tải press kit (EN)</a>
</div>
