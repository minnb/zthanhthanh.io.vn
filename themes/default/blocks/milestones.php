<?php
/**
 * Hành trình của phim nổi bật.
 *
 * @var array<string, mixed>|null $featuredFilm
 * @var list<array<string, mixed>> $milestones
 */
if (!$featuredFilm || $milestones === []) {
    return;
}
?>
<section class="container section section--line" aria-labelledby="journey">
  <h2 class="h-2" id="journey">Từ vài dòng ý tưởng đến Manila</h2>
  <p class="lead section__intro">Gần 3 tháng “thanh xuân bay màu” cho một phim ngắn đầu tay.</p>
  <?= partial('partials/timeline', ['milestones' => $milestones]) ?>
</section>
