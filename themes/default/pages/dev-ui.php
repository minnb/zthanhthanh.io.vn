<?php
/**
 * Thư viện giao diện (chỉ local): xem trước các partial sẽ dùng ở Bước 3 với dữ liệu mẫu.
 *
 * @var array<string, mixed> $site
 * @var list<array<string, mixed>> $samplePosts
 * @var list<array<string, mixed>> $milestones
 */
?>
<h1 class="h-1">Thư viện giao diện</h1>
<p class="muted">Dữ liệu mẫu, chỉ hiển thị ở local.</p>

<h2 class="h-3">Breadcrumb</h2>
<div class="mt-2">
  <?= partial('partials/breadcrumb', ['items' => [
      ['label' => 'Trang chủ', 'url' => '/'],
      ['label' => 'Hậu trường biên kịch', 'url' => '/hau-truong-bien-kich'],
      ['label' => 'Không để người khác quyết định giới hạn…'],
  ]]) ?>
</div>

<h2 class="h-3">Nút & nhãn</h2>
<div class="btn-row mt-2">
  <a class="btn btn--primary" href="#">Nút chính</a>
  <a class="btn btn--outline" href="#">Nút viền</a>
</div>
<p class="mt-5"><span class="badge">Chung kết Inspiring Asia 2026 · Manila</span></p>

<h2 class="h-3">Thẻ bài viết</h2>
<div class="grid grid--3 mt-5">
  <?php foreach ($samplePosts as $i => $post): ?>
    <?= partial('partials/post-card', ['post' => $post, 'index' => $i + 1]) ?>
  <?php endforeach; ?>
</div>

<h2 class="h-3">Nội dung bài (prose)</h2>
<div class="prose mt-5">
  <p>Hôm qua ngồi xem series <em>Agency</em>, nghe câu thoại của nhân vật chính, mình phải note lại liền: <strong>“Không để người khác quyết định giới hạn của bản thân.”</strong></p>
  <h2>Nhận vai, rồi tập như thật</h2>
  <p>Nhưng vai An mình vẫn nhận. Thứ nhất là vì kinh phí làm phim ít, thứ hai là mình muốn trải nghiệm một lần ở vị trí mới. <a href="#">Link trong bài</a>.</p>
  <blockquote>Giới hạn của bản thân đâu thể nằm ở mồm người khác. Giới hạn của mình nằm ở việc mình có dám dấn thân.</blockquote>
  <ul><li>Mục danh sách một</li><li>Mục danh sách hai</li></ul>
</div>

<div class="prose mt-5">
  <div class="tag-list">
    <a class="tag" href="#">#hautruongbienkich</a>
    <a class="tag" href="#">#PhimngắnĐỢI</a>
  </div>
  <div class="mt-5"><?= partial('partials/share-buttons', ['shareUrl' => url('/hau-truong-bien-kich/vi-du'), 'shareTitle' => 'Bài viết mẫu']) ?></div>
  <div class="mt-5"><?= partial('partials/author-box') ?></div>
</div>

<h2 class="h-3">Dòng thời gian</h2>
<div class="mt-5"><?= partial('partials/timeline', ['milestones' => $milestones]) ?></div>

<h2 class="h-3">Hộp CTA</h2>
<div class="cta-box mt-5">
  <div><p class="h-3">Báo chí & chiếu phim</p><p class="muted">Press kit tiếng Anh, poster độ phân giải cao.</p></div>
  <a class="btn btn--primary" href="#">Tải press kit (EN)</a>
</div>
