<?php
/**
 * @var list<array<string, mixed>> $films
 */
use App\Models\AdminFilm;
?>
<header class="admin-head"><h1 class="h-2">Phim</h1></header>
<ul class="post-rows">
  <?php foreach ($films as $f): ?>
    <li class="post-row">
      <div class="post-row__main">
        <a class="post-row__title" href="<?= e(url('/admin/films/' . $f['id'])) ?>"><?= e($f['title']) ?></a>
        <p class="post-row__meta xs muted"><?= e(AdminFilm::STATUSES[$f['status']] ?? $f['status']) ?><?= $f['year'] ? ' · ' . (int) $f['year'] : '' ?><?= $f['is_featured'] ? ' · Nổi bật trên trang chủ' : '' ?></p>
      </div>
      <div class="post-row__actions">
        <a class="btn btn--outline btn--sm" href="<?= e(url('/admin/films/' . $f['id'])) ?>">Sửa</a>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/phim/' . $f['slug'])) ?>" target="_blank" rel="noopener">Xem ↗</a>
      </div>
    </li>
  <?php endforeach; ?>
</ul>
