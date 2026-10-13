<?php
/**
 * @var list<array<string, mixed>> $pages
 */
use App\Models\Page;
?>
<header class="admin-head"><h1 class="h-2">Trang tĩnh</h1></header>
<ul class="post-rows">
  <?php foreach ($pages as $p): ?>
    <li class="post-row">
      <div class="post-row__main">
        <a class="post-row__title" href="<?= e(url('/admin/pages/' . $p['id'])) ?>"><?= e($p['title']) ?></a>
        <p class="post-row__meta xs muted"><?= e(Page::path($p)) ?> · <?= e(strtoupper((string) $p['lang'])) ?></p>
      </div>
      <span class="status status--<?= $p['status'] === 'published' ? 'published' : 'draft' ?>"><?= $p['status'] === 'published' ? 'Đã xuất bản' : 'Nháp' ?></span>
      <div class="post-row__actions"><a class="btn btn--outline btn--sm" href="<?= e(url('/admin/pages/' . $p['id'])) ?>">Sửa</a></div>
    </li>
  <?php endforeach; ?>
</ul>
