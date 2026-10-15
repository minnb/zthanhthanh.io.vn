<?php
/**
 * Trang Giới thiệu (/gioi-thieu): đầu trang + 3 chương câu chuyện + nội dung soạn trong admin (pages)
 * + câu trích + dòng thời gian sự nghiệp (mở/đóng bằng <details>, không cần JS) + lời mời.
 * Chương, câu trích, dòng thời gian lấy từ setting JSON "about_page" (PageController::about);
 * khối nào thiếu dữ liệu thì không hiển thị.
 *
 * @var array<string, mixed> $page  Có thêm: html (đã render an toàn)
 * @var array{chapters?: list<array<string, string>>, quote?: array<string, string>, career?: list<array{year: string, items: list<array<string, mixed>>}>} $about
 * @var list<array{label: string, url?: string}> $breadcrumb
 * @var array<string, mixed> $site
 */
$chapters = is_array($about['chapters'] ?? null) ? $about['chapters'] : [];
$quote = is_array($about['quote'] ?? null) ? $about['quote'] : [];
$career = is_array($about['career'] ?? null) ? $about['career'] : [];
?>
<header class="container page-head">
  <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
  <p class="eyebrow">Về tác giả</p>
  <h1 class="h-1"><?= e((string) $page['title']) ?></h1>
  <?php if (!empty($site['authorBio'])): ?><p class="lead"><?= e((string) $site['authorBio']) ?></p><?php endif; ?>
</header>

<?php if ($chapters !== []): ?>
  <section class="section section--tint" aria-labelledby="story-title">
    <div class="container">
      <div class="section-head">
        <div class="section-head__text">
          <p class="eyebrow">Câu chuyện</p>
          <h2 class="h-2" id="story-title">Những chương của một hành trình</h2>
        </div>
      </div>
      <ol class="chapters">
        <?php foreach ($chapters as $ch): ?>
          <?php if (!is_array($ch)) { continue; } ?>
          <li class="chapter card">
            <p class="chapter__mark"><?= e((string) ($ch['mark'] ?? '')) ?><?php if (!empty($ch['unit'])): ?><small><?= e((string) $ch['unit']) ?></small><?php endif; ?></p>
            <h3><?= e((string) ($ch['title'] ?? '')) ?></h3>
            <p><?= e((string) ($ch['text'] ?? '')) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>
<?php endif; ?>

<div class="container">
  <div class="prose article__body"><?= $page['html'] /* đã render qua Markdown safeMode */ ?></div>
</div>

<?php if (!empty($quote['text'])): ?>
  <section class="section" aria-label="Câu nói tâm đắc">
    <figure class="container quote-band">
      <blockquote><p><?= e((string) $quote['text']) ?></p></blockquote>
      <?php if (!empty($quote['cite'])): ?><figcaption><cite class="label"><?= e((string) $quote['cite']) ?></cite></figcaption><?php endif; ?>
    </figure>
  </section>
<?php endif; ?>

<?php if ($career !== []): ?>
  <section class="section section--tint" aria-labelledby="career-title">
    <div class="container">
      <div class="section-head section-head--center">
        <div class="section-head__text">
          <p class="eyebrow">Dòng thời gian</p>
          <h2 class="h-2" id="career-title">Hành trình sự nghiệp</h2>
          <p class="muted small">Bấm vào từng mốc để đọc thêm.</p>
        </div>
      </div>
      <div class="timeline timeline--career">
        <?php foreach ($career as $group): ?>
          <?php if (!is_array($group) || !is_array($group['items'] ?? null)) { continue; } ?>
          <h3 class="timeline__year"><?= e((string) ($group['year'] ?? '')) ?></h3>
          <?php foreach ($group['items'] as $item): ?>
            <?php if (!is_array($item)) { continue; } $key = !empty($item['highlight']); ?>
            <div class="timeline__item<?= $key ? ' timeline__item--highlight' : '' ?>">
              <details class="career card"<?= $key ? ' open' : '' ?>>
                <summary>
                  <span class="label text-accent"><?= e((string) ($item['when'] ?? '')) ?></span>
                  <span class="career__title"><?= e((string) ($item['title'] ?? '')) ?></span>
                  <span class="career__toggle" aria-hidden="true"><?= icon('plus') ?></span>
                </summary>
                <p class="career__body"><?= e((string) ($item['body'] ?? '')) ?></p>
              </details>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<section class="section" aria-labelledby="about-cta">
  <div class="container invite">
    <p class="eyebrow">Hợp tác</p>
    <h2 class="h-2" id="about-cta">Cùng kể những câu chuyện chân thật về con người</h2>
    <div class="btn-row btn-row--stack">
      <a class="btn btn--primary" href="<?= e(url('/lien-he')) ?>">Liên hệ hợp tác <?= icon('arrow-r', 'i--sm i--move') ?></a>
      <a class="btn btn--outline" href="<?= e(url('/en/press-kit')) ?>" hreflang="en">EN · Press kit</a>
    </div>
  </div>
</section>
