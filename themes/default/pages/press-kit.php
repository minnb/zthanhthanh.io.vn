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
<article class="container article">
  <header class="article__head">
    <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
    <p class="eyebrow">Press kit</p>
    <h1 class="h-1"><?= e($site['authorName'] ?? $site['name'] ?? '') ?> – Screenwriter</h1>
  </header>
  <div class="prose article__body"><?= $page['html'] ?></div>

  <div class="article__foot">
    <h2 class="h-2">Films</h2>
    <div class="list-box">
      <ul>
        <?php foreach ($films as $film): ?>
          <li>
            <p><strong><?= e($film['title_en'] ?: $film['title']) ?></strong><?php if (!empty($film['title_en'])): ?> <span class="muted">(<?= e($film['title']) ?>)</span><?php endif; ?></p>
            <?php if (!empty($film['badge'])): ?><p class="small muted"><?= e($film['badge']) ?></p><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php if (!empty($site['contactEmail'])): ?>
      <p>Contact: <a class="text-accent" href="mailto:<?= e($site['contactEmail']) ?>"><?= e($site['contactEmail']) ?></a></p>
    <?php endif; ?>
  </div>
</article>
