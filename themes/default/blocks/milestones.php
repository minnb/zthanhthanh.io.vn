<?php
/**
 * Hành trình của phim nổi bật (dòng thời gian ngang).
 *
 * @var array<string, mixed>|null $featuredFilm
 * @var list<array<string, mixed>> $milestones
 */
if (!$featuredFilm || $milestones === []) {
    return;
}
?>
<section class="section section--tint" aria-labelledby="journey">
  <div class="container">
    <div class="section-head">
      <div class="section-head__text">
        <p class="eyebrow">Hành trình phim <?= e((string) $featuredFilm['title']) ?></p>
        <h2 class="h-2" id="journey">Từ vài dòng ý tưởng đến Manila</h2>
        <p class="muted">Gần 3 tháng “thanh xuân bay màu” cho một phim ngắn đầu tay.</p>
      </div>
    </div>
    <?= partial('partials/timeline', ['milestones' => $milestones]) ?>
  </div>
</section>
