<?php
/**
 * Danh sách bài: thẻ trên điện thoại, bảng trên desktop (admin.md: không bảng tràn ngang).
 *
 * @var array{q: string, status: string, category: int, source: string, trash: bool} $filters
 * @var array{items: list<array<string, mixed>>, total: int, pages: int} $result
 * @var int $page
 * @var list<array<string, mixed>> $categories
 * @var array<string, int> $counts
 * @var string $csrf
 * @var string $title
 */
$statusOptions = ['' => 'Mọi trạng thái', 'published' => 'Đã xuất bản', 'scheduled' => 'Hẹn giờ', 'draft' => 'Nháp', 'pending' => 'Chờ duyệt'];
$sourceOptions = ['' => 'Mọi nguồn', 'manual' => 'Tự viết', 'facebook' => 'Facebook', 'ai-draft' => 'AI'];
$query = static function (array $override) use ($filters): string {
    $params = array_filter(array_merge([
        'q' => $filters['q'], 'status' => $filters['status'], 'category' => $filters['category'] ?: '',
        'source' => $filters['source'], 'trash' => $filters['trash'] ? '1' : '',
    ], $override), static fn ($v) => $v !== '' && $v !== null);
    return url('/admin/posts') . ($params ? '?' . http_build_query($params) : '');
};
?>
<header class="admin-head">
  <h1 class="h-2"><?= e($title) ?> <span class="muted small">(<?= (int) $result['total'] ?>)</span></h1>
  <?php if ($filters['trash']): ?>
    <a class="small" href="<?= e(url('/admin/posts')) ?>">← Về danh sách</a>
  <?php else: ?>
    <a class="small" href="<?= e($query(['trash' => '1', 'status' => '', 'page' => ''])) ?>">Thùng rác (<?= (int) ($counts['trash'] ?? 0) ?>)</a>
  <?php endif; ?>
</header>

<form method="get" action="<?= e(url('/admin/posts')) ?>" class="filters" role="search">
  <?php if ($filters['trash']): ?><input type="hidden" name="trash" value="1"><?php endif; ?>
  <label class="sr-only" for="q">Tìm theo tiêu đề</label>
  <input id="q" name="q" type="search" placeholder="Tìm theo tiêu đề…" value="<?= e($filters['q']) ?>">
  <label class="sr-only" for="f-status">Trạng thái</label>
  <select id="f-status" name="status">
    <?php foreach ($statusOptions as $value => $label): ?>
      <option value="<?= e($value) ?>"<?= $filters['status'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <label class="sr-only" for="f-cat">Danh mục</label>
  <select id="f-cat" name="category">
    <option value="">Mọi danh mục</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= (int) $c['id'] ?>"<?= $filters['category'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <label class="sr-only" for="f-src">Nguồn</label>
  <select id="f-src" name="source">
    <?php foreach ($sourceOptions as $value => $label): ?>
      <option value="<?= e($value) ?>"<?= $filters['source'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn--outline">Lọc</button>
</form>

<?php if ($result['items'] === []): ?>
  <p class="empty-state">Không có bài nào khớp bộ lọc.</p>
<?php else: ?>
  <ul class="post-rows">
    <?php foreach ($result['items'] as $p): ?>
      <li class="post-row">
        <div class="post-row__main">
          <a class="post-row__title" href="<?= e(url('/admin/posts/' . $p['id'])) ?>"><?= e($p['title'] !== '' ? $p['title'] : '(chưa có tiêu đề)') ?></a>
          <p class="post-row__meta xs muted">
            <?= e($p['category_name'] ?? 'Chưa có danh mục') ?> ·
            <?= e(\App\Models\AdminPost::SOURCES[$p['source']] ?? $p['source']) ?> ·
            sửa <?= e(date('d/m/Y H:i', strtotime((string) $p['updated_at']) ?: time())) ?>
          </p>
        </div>
        <span class="status status--<?= e($p['status_key']) ?>"><?= e($p['status_label']) ?></span>
        <div class="post-row__actions">
          <?php if ($filters['trash']): ?>
            <form method="post" action="<?= e(url('/admin/posts/' . $p['id'] . '/restore')) ?>">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn--outline btn--sm">Khôi phục</button>
            </form>
          <?php else: ?>
            <a class="btn btn--outline btn--sm" href="<?= e(url('/admin/posts/' . $p['id'])) ?>">Sửa</a>
            <form method="post" action="<?= e(url('/admin/posts/' . $p['id'] . '/trash')) ?>" data-confirm="Chuyển bài “<?= e($p['title'] ?: 'chưa có tiêu đề') ?>” vào thùng rác?">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn--ghost btn--sm">Xóa</button>
            </form>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php if ($result['pages'] > 1): ?>
    <nav class="pagination" aria-label="Phân trang">
      <?php for ($n = 1; $n <= $result['pages']; $n++): ?>
        <?php if ($n === $page): ?><span aria-current="page"><?= $n ?></span>
        <?php else: ?><a href="<?= e($query(['page' => $n > 1 ? (string) $n : ''])) ?>"><?= $n ?></a><?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
