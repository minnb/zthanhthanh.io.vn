<?php
/**
 * Trang phim (nền "phòng chiếu" tối ở cả hai chế độ).
 *
 * @var array<string, mixed> $film   Có thêm: synopsis_html, credits_html, awards_html (đã render an toàn)
 * @var list<array<string, mixed>> $milestones
 * @var list<array<string, mixed>> $related
 * @var list<array{label: string, url?: string}> $breadcrumb
 * @var array<string, mixed> $site
 */
?>
<section class="film-hero dark-surface">
  <div class="container">
    <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
    <div class="film-hero__media media">
      <?php if (!empty($film['poster'])): ?>
        <?= partial('partials/image', ['media' => $film['poster'], 'ratio' => \App\Core\Image::FRAME_POSTER, 'eager' => true, 'sizes' => '(min-width: 1240px) 1200px, 100vw']) ?>
      <?php else: ?>
        <?= partial('partials/art-poster', ['filmTitle' => (string) $film['title'], 'caption' => (string) $film['caption']]) ?>
      <?php endif; ?>
    </div>
    <div class="film-hero__grid">
      <div>
        <p class="eyebrow"><?= e($film['caption'] !== '' ? $film['caption'] : $film['status_label']) ?></p>
        <h1 class="film-hero__title"><?= e($film['title']) ?></h1>
        <?php if (!empty($film['logline'])): ?><p class="logline film-hero__logline"><?= e($film['logline']) ?></p><?php endif; ?>
        <?php if (!empty($film['badge'])): ?><p><span class="badge label"><?= icon('star') ?><?= e($film['badge']) ?></span></p><?php endif; ?>
      </div>
      <dl class="meta-list">
        <div><dt>Biên kịch</dt><dd><?= e($site['authorName'] ?? '') ?></dd></div>
        <div><dt>Trạng thái</dt><dd><?= e($film['status_label']) ?></dd></div>
        <?php if (!empty($film['genre'])): ?><div><dt>Thể loại</dt><dd><?= e($film['genre']) ?></dd></div><?php endif; ?>
        <?php if (!empty($film['duration_min'])): ?>
          <div><dt>Thời lượng</dt><dd><?= (int) $film['duration_min'] ?> phút<?php if (!empty($film['duration_note'])): ?><br><span class="muted"><?= e($film['duration_note']) ?></span><?php endif; ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($film['title_en'])): ?><div class="wide"><dt>Tên tiếng Anh</dt><dd lang="en"><?= e($film['title_en']) ?></dd></div><?php endif; ?>
      </dl>
    </div>
  </div>
</section>

<section class="container section">
  <?php if ($film['awards_html'] !== '' || $film['credits_html'] !== '' || $film['synopsis_html'] !== ''): ?>
    <div class="grid grid--2">
      <?php if ($film['credits_html'] !== '' || $film['synopsis_html'] !== ''): ?>
        <div>
          <p class="eyebrow">Về bộ phim</p>
          <h2 class="h-2 mt-2">Câu chuyện &amp; ekip</h2>
          <div class="prose mt-5"><?= $film['synopsis_html'] ?><?= $film['credits_html'] ?></div>
        </div>
      <?php endif; ?>
      <?php if ($film['awards_html'] !== ''): ?>
        <div>
          <p class="eyebrow">Liên hoan &amp; giải thưởng</p>
          <h2 class="h-2 mt-2">Hành trình liên hoan</h2>
          <div class="list-box card prose mt-5"><?= $film['awards_html'] ?></div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="cta-box mt-8">
    <div><h2 class="h-3">Báo chí &amp; chiếu phim</h2><p>Press kit tiếng Anh, thông tin liên hệ tổ chức chiếu.</p></div>
    <a class="btn btn--primary" href="<?= e(url('/en/press-kit')) ?>" hreflang="en">Press kit (EN)</a>
  </div>
</section>

<?php if ($milestones !== []): ?>
  <section class="section section--tint" aria-labelledby="journey">
    <div class="container">
      <div class="section-head"><div class="section-head__text"><p class="eyebrow">Hậu trường</p><h2 class="h-2" id="journey">Dòng thời gian sản xuất</h2></div></div>
      <?= partial('partials/timeline', ['milestones' => $milestones]) ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($related !== []): ?>
  <section class="section" aria-labelledby="related">
    <div class="container">
      <div class="section-head">
        <div class="section-head__text"><p class="eyebrow">Đọc thêm</p><h2 class="h-2" id="related">Hậu trường <?= e($film['title']) ?></h2></div>
        <a class="text-link" href="<?= e(url('/bai-viet')) ?>">Tất cả bài viết <?= icon('arrow-r') ?></a>
      </div>
      <ul class="grid grid--3" role="list">
        <?php foreach ($related as $item): ?>
          <li><?= partial('partials/post-card', ['post' => $item]) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
<?php endif; ?>
