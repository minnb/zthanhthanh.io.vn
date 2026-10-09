<?php
/**
 * Trang phim (nền "phòng chiếu").
 *
 * @var array<string, mixed> $film   Có thêm: synopsis_html, credits_html, awards_html (đã render an toàn)
 * @var list<array<string, mixed>> $milestones
 * @var list<array<string, mixed>> $related
 * @var list<array{label: string, url?: string}> $breadcrumb
 * @var array<string, mixed> $site
 */
?>
<section class="film-hero">
  <div class="container">
    <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
    <div class="film-hero__media">
      <?= partial('partials/art-poster', ['filmTitle' => (string) $film['title'], 'caption' => (string) $film['caption']]) ?>
    </div>
    <div class="film-hero__grid">
      <div>
        <p class="eyebrow"><?= e($film['caption'] !== '' ? $film['caption'] : $film['status_label']) ?></p>
        <h1 class="film-hero__title"><?= e($film['title']) ?></h1>
        <?php if (!empty($film['logline'])): ?><p class="film-hero__logline"><?= e($film['logline']) ?></p><?php endif; ?>
        <?php if (!empty($film['badge'])): ?><span class="badge">★ <?= e($film['badge']) ?></span><?php endif; ?>
      </div>
      <dl class="meta-list">
        <div><dt>Biên kịch</dt><dd><?= e($site['authorName'] ?? '') ?></dd></div>
        <div><dt>Trạng thái</dt><dd><?= e($film['status_label']) ?></dd></div>
        <?php if (!empty($film['genre'])): ?><div><dt>Thể loại</dt><dd><?= e($film['genre']) ?></dd></div><?php endif; ?>
        <?php if (!empty($film['duration_min'])): ?>
          <div><dt>Thời lượng</dt><dd><?= (int) $film['duration_min'] ?> phút<?php if (!empty($film['duration_note'])): ?><br><span class="muted"><?= e($film['duration_note']) ?></span><?php endif; ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($film['title_en'])): ?><div class="wide"><dt>Tên tiếng Anh</dt><dd><?= e($film['title_en']) ?></dd></div><?php endif; ?>
      </dl>
    </div>
  </div>
</section>

<?php if ($film['awards_html'] !== '' || $film['credits_html'] !== '' || $film['synopsis_html'] !== ''): ?>
  <section class="container section">
    <div class="grid grid--2">
      <?php if ($film['awards_html'] !== ''): ?>
        <div>
          <h2 class="h-2">Hành trình liên hoan & giải thưởng</h2>
          <div class="list-box prose mt-5"><?= $film['awards_html'] ?></div>
        </div>
      <?php endif; ?>
      <?php if ($film['credits_html'] !== '' || $film['synopsis_html'] !== ''): ?>
        <div>
          <h2 class="h-2">Về bộ phim</h2>
          <div class="prose mt-5"><?= $film['synopsis_html'] ?><?= $film['credits_html'] ?></div>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($milestones !== []): ?>
  <section class="container section section--line" aria-labelledby="journey">
    <h2 class="h-2" id="journey">Dòng thời gian sản xuất</h2>
    <div class="mt-5"><?= partial('partials/timeline', ['milestones' => $milestones]) ?></div>
  </section>
<?php endif; ?>

<?php if ($related !== []): ?>
  <section class="section section--tint" aria-labelledby="related">
    <div class="container">
      <h2 class="h-2" id="related">Hậu trường <?= e($film['title']) ?></h2>
      <div class="grid grid--3 mt-5">
        <?php foreach ($related as $item): ?>
          <?= partial('partials/post-card', ['post' => $item]) ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<section class="container section">
  <div class="cta-box">
    <div><h2 class="h-3">Báo chí & chiếu phim</h2><p class="muted">Press kit tiếng Anh, thông tin liên hệ tổ chức chiếu.</p></div>
    <a class="btn btn--primary" href="<?= e(url('/en/press-kit')) ?>">Press kit (EN)</a>
  </div>
</section>
