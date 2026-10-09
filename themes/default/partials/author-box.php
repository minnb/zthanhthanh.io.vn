<?php
/**
 * Hộp tác giả cuối bài (tín hiệu E-E-A-T).
 *
 * @var array<string, mixed> $site
 */
$name = (string) ($site['authorName'] ?? $site['name'] ?? '');
?>
<aside class="author-box" aria-label="Về tác giả">
  <span class="avatar" aria-hidden="true"><?= e(mb_substr($name, 0, 1)) ?></span>
  <div>
    <p class="author-box__name"><?= e($name) ?></p>
    <?php if (!empty($site['authorBio'])): ?><p class="author-box__bio"><?= e($site['authorBio']) ?></p><?php endif; ?>
    <a class="author-box__link" href="<?= e(url('/gioi-thieu')) ?>">Về mình →</a>
  </div>
</aside>
