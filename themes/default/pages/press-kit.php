<?php
/**
 * Press kit tiếng Anh (lang="en").
 *
 * @var array<string, mixed> $page
 * @var list<array<string, mixed>> $films
 * @var list<array{label: string, url?: string}> $breadcrumb
 * @var array<string, mixed> $site
 */
?>
<article>
  <header class="container article-head">
    <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
    <p class="eyebrow">Press kit</p>
    <h1 class="h-1"><?= e($site['authorName'] ?? $site['name'] ?? '') ?> – <em>Screenwriter</em></h1>
  </header>
  <div class="container section section--flush-top">
    <div class="prose"><?= $page['html'] ?></div>
    <div class="prose mt-8">
      <h2>Films</h2>
      <ul class="list-box card" role="list">
        <?php foreach ($films as $film): ?>
          <li>
            <strong><?= e($film['title_en'] ?: $film['title']) ?></strong><?php if (!empty($film['title_en'])): ?> <span class="muted" lang="vi">(<?= e($film['title']) ?>)</span><?php endif; ?>
            <?php if (!empty($film['badge'])): ?><br><span class="small muted"><?= e($film['badge']) ?></span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if (!empty($site['contactEmail'])): ?>
        <p>Contact: <a href="mailto:<?= e($site['contactEmail']) ?>"><?= e($site['contactEmail']) ?></a></p>
      <?php endif; ?>
    </div>
  </div>
</article>
