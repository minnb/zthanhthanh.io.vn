<?php
/**
 * Hộp tác giả cuối bài (tín hiệu E-E-A-T).
 *
 * @var array<string, mixed> $site
 */
$name = (string) ($site['authorName'] ?? $site['name'] ?? '');
$avatar = (string) ($site['authorAvatar'] ?? '');
?>
<aside class="author-box" aria-label="Về tác giả">
  <?php if ($avatar !== ''): ?>
    <img class="avatar" src="<?= e(url($avatar)) ?>" alt="<?= e($name) ?>" width="52" height="52">
  <?php else: ?>
    <span class="avatar" aria-hidden="true"><?= e(mb_substr($name, 0, 1)) ?></span>
  <?php endif; ?>
  <div>
    <p class="h-3"><?= e($name) ?></p>
    <?php if (!empty($site['authorBio'])): ?><p class="author-box__bio"><?= e($site['authorBio']) ?></p><?php endif; ?>
    <a class="text-link" href="<?= e(url('/gioi-thieu')) ?>">Về mình <?= icon('arrow-r') ?></a>
  </div>
</aside>
