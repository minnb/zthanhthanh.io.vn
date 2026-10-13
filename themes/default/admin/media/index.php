<?php
/**
 * Thư viện ảnh: upload (chụp/chọn từ điện thoại), sửa alt/chú thích, xóa ảnh không còn dùng.
 *
 * @var array{items: list<array<string, mixed>>, total: int} $result  items đã qua MediaController::libraryItems()
 *      (thumb, thumb_height, focus_x, focus_y, previews[label, ratio, position])
 * @var int $page
 * @var int $pages
 * @var string $q
 * @var string $csrf
 */
use App\Models\Media;
?>
<header class="admin-head">
  <h1 class="h-2">Thư viện ảnh <span class="muted small">(<?= (int) $result['total'] ?>)</span></h1>
</header>

<form method="post" action="<?= e(url('/admin/media/upload')) ?>" enctype="multipart/form-data" class="panel upload-form">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <div class="field">
    <label for="files">Chọn ảnh (JPG, PNG, WebP, GIF · tối đa 15MB/ảnh · tối đa 20 ảnh)</label>
    <input id="files" name="files[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
  </div>
  <div class="field">
    <label for="alt">Mô tả chung (alt) <span class="muted xs">– có thể sửa riêng từng ảnh sau</span></label>
    <input id="alt" name="alt" type="text" maxlength="255">
  </div>
  <button type="submit" class="btn btn--primary">Tải lên</button>
  <p class="xs muted">Ảnh tỉ lệ nào cũng được (ảnh điện thoại dùng thẳng), nên rộng ≥ 1440px. Hệ thống tự thu nhỏ, xoay đúng chiều,
    tự tìm chủ thể và cắt theo từng khung (ảnh bìa, poster, Facebook). Nếu khung cắt chưa đúng: bấm vào chủ thể trên ảnh (mặt người, chữ trên poster) rồi Lưu.</p>
</form>

<form method="get" action="<?= e(url('/admin/media')) ?>" class="filters" role="search">
  <label class="sr-only" for="mq">Tìm ảnh</label>
  <input id="mq" name="q" type="search" placeholder="Tìm theo mô tả hoặc tên file…" value="<?= e($q) ?>">
  <button type="submit" class="btn btn--outline">Tìm</button>
</form>

<?php if ($result['items'] === []): ?>
  <p class="empty-state">Chưa có ảnh nào.</p>
<?php else: ?>
  <ul class="media-grid">
    <?php foreach ($result['items'] as $m): ?>
      <li class="media-card" id="media-<?= (int) $m['id'] ?>" data-focus-card data-w="<?= (int) $m['width'] ?>" data-h="<?= (int) $m['height'] ?>">
        <div class="focus-picker" data-focus-picker tabindex="0" role="group"
             aria-label="Điểm tâm: bấm vào chủ thể trên ảnh, hoặc dùng phím mũi tên" aria-describedby="fp-<?= (int) $m['id'] ?>">
          <img src="<?= e($m['thumb']) ?>" alt="<?= e($m['alt']) ?>" width="480" height="<?= (int) $m['thumb_height'] ?>" loading="lazy" decoding="async">
          <span class="focus-picker__dot" data-focus-dot style="left:<?= (int) $m['focus_x'] ?>%;top:<?= (int) $m['focus_y'] ?>%" aria-hidden="true"></span>
        </div>
        <ul class="focus-frames" aria-label="Xem trước cách cắt theo khung">
          <?php foreach ($m['previews'] as $pv): ?>
            <li class="focus-frame">
              <span class="focus-frame__box" style="aspect-ratio:<?= e($pv['ratio']) ?>">
                <img src="<?= e($m['thumb']) ?>" alt="" loading="lazy" decoding="async" data-frame-ratio="<?= e($pv['ratio']) ?>"
                     style="object-position:<?= e($pv['position']) ?>">
              </span>
              <span class="xs muted"><?= e($pv['label']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <form method="post" action="<?= e(url('/admin/media/' . $m['id'])) ?>" class="media-card__form">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="page" value="<?= (int) $page ?>">
          <input type="hidden" name="focus_x" value="<?= (int) $m['focus_x'] ?>" data-focus-x>
          <input type="hidden" name="focus_y" value="<?= (int) $m['focus_y'] ?>" data-focus-y>
          <p class="xs muted" id="fp-<?= (int) $m['id'] ?>" data-focus-status aria-live="polite">Điểm tâm: <?= (int) $m['focus_x'] ?>% ngang, <?= (int) $m['focus_y'] ?>% dọc</p>
          <label for="alt-<?= (int) $m['id'] ?>" class="xs">Mô tả (alt)<?= trim((string) $m['alt']) === '' ? ' <strong class="text-accent">– còn thiếu</strong>' : '' ?></label>
          <input id="alt-<?= (int) $m['id'] ?>" name="alt" type="text" maxlength="255" value="<?= e($m['alt']) ?>">
          <label for="cap-<?= (int) $m['id'] ?>" class="xs">Chú thích</label>
          <input id="cap-<?= (int) $m['id'] ?>" name="caption" type="text" maxlength="255" value="<?= e((string) $m['caption']) ?>">
          <p class="xs muted"><?= (int) $m['width'] ?>×<?= (int) $m['height'] ?> · <code data-copy-text><?= e(Media::url($m, 960)) ?></code></p>
          <div class="media-card__actions">
            <button type="submit" class="btn btn--outline btn--sm">Lưu</button>
            <button type="submit" form="del-<?= (int) $m['id'] ?>" class="btn btn--ghost btn--sm">Xóa</button>
          </div>
        </form>
        <form id="del-<?= (int) $m['id'] ?>" method="post" action="<?= e(url('/admin/media/' . $m['id'] . '/delete')) ?>" data-confirm="Xóa vĩnh viễn ảnh này? (chỉ xóa được khi không còn bài/phim nào dùng)">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Phân trang">
      <?php for ($n = 1; $n <= $pages; $n++): ?>
        <?php if ($n === $page): ?><span aria-current="page"><?= $n ?></span>
        <?php else: ?><a href="<?= e(url('/admin/media') . '?' . http_build_query(array_filter(['page' => $n, 'q' => $q]))) ?>"><?= $n ?></a><?php endif; ?>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
