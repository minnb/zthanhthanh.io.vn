<?php
/**
 * Sửa phim + dòng thời gian (lên/xuống bằng nút – admin.md).
 *
 * @var array<string, mixed> $film
 * @var array<string, string> $errors
 * @var array<string, string> $statuses
 * @var list<array<string, mixed>> $milestones
 * @var string $csrf
 */
use App\Models\Media;

$poster = is_array($film['poster'] ?? null) ? $film['poster'] : null;
$v = static fn (string $key): string => e((string) ($film[$key] ?? ''));
$err = static fn (string $key): string => isset($errors[$key]) ? '<p class="field__error" id="err-' . e($key) . '">' . e($errors[$key]) . '</p>' : '';
$inv = static fn (string $key): string => isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
?>
<header class="admin-head">
  <div>
    <p class="xs muted"><a href="<?= e(url('/admin/films')) ?>">Phim</a> / Sửa</p>
    <h1 class="h-3 mt-2"><?= $v('title') ?></h1>
  </div>
  <a class="small" href="<?= e(url('/phim/' . $film['slug'])) ?>" target="_blank" rel="noopener">Xem trên web ↗</a>
</header>

<?php if ($errors !== []): ?>
  <div class="notice notice--error" role="alert"><strong>Chưa lưu được:</strong>
    <ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/films/' . $film['id'])) ?>" class="form-grid">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <section class="panel">
    <h2 class="panel__title">Thông tin phim</h2>
    <div class="field"><label for="title">Tên phim</label><input id="title" name="title" maxlength="160" value="<?= $v('title') ?>"<?= $inv('title') ?>><?= $err('title') ?></div>
    <div class="field"><label for="title_en">Tên tiếng Anh</label><input id="title_en" name="title_en" maxlength="160" value="<?= $v('title_en') ?>"></div>
    <div class="field"><label for="logline">Logline (1–2 câu)</label><textarea id="logline" name="logline" rows="3"><?= $v('logline') ?></textarea></div>
    <div class="field"><label for="synopsis_md">Mô tả phim (Markdown) <span class="xs muted">– có thể kể ekip đặc biệt ở đây</span></label><textarea id="synopsis_md" name="synopsis_md" rows="6"><?= $v('synopsis_md') ?></textarea></div>
    <div class="field-row">
      <div class="field"><label for="genre">Thể loại</label><input id="genre" name="genre" maxlength="120" value="<?= $v('genre') ?>"></div>
      <div class="field"><label for="year">Năm</label><input id="year" name="year" inputmode="numeric" value="<?= $v('year') ?>"<?= $inv('year') ?>><?= $err('year') ?></div>
    </div>
    <div class="field-row">
      <div class="field"><label for="duration_min">Thời lượng (phút)</label><input id="duration_min" name="duration_min" inputmode="numeric" value="<?= $v('duration_min') ?>"<?= $inv('duration_min') ?>><?= $err('duration_min') ?></div>
      <div class="field"><label for="duration_note">Ghi chú thời lượng</label><input id="duration_note" name="duration_note" maxlength="160" value="<?= $v('duration_note') ?>" placeholder="6 phút (bản dự thi)"></div>
    </div>
    <div class="field"><label for="actors">Diễn viên <span class="xs muted">(cách nhau dấu phẩy)</span></label><input id="actors" name="actors" maxlength="255" value="<?= $v('actors') ?>"></div>
    <div class="field"><label for="credits_md">Ekip (Markdown)</label><textarea id="credits_md" name="credits_md" rows="4"><?= $v('credits_md') ?></textarea></div>
    <div class="field"><label for="awards_md">Liên hoan & giải thưởng (mỗi dòng bắt đầu bằng “- ”)</label><textarea id="awards_md" name="awards_md" rows="4"><?= $v('awards_md') ?></textarea></div>
  </section>

  <section class="panel">
    <h2 class="panel__title">Hiển thị</h2>
    <div class="field">
      <label for="status">Trạng thái</label>
      <select id="status" name="status"<?= $inv('status') ?>>
        <?php foreach ($statuses as $value => $label): ?>
          <option value="<?= e($value) ?>"<?= ($film['status'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select><?= $err('status') ?>
    </div>
    <label class="check"><input type="checkbox" name="is_featured" value="1"<?= !empty($film['is_featured']) ? ' checked' : '' ?>> Phim nổi bật trên trang chủ</label>
    <div class="field"><label for="badge">Nhãn trên poster</label><input id="badge" name="badge" maxlength="120" value="<?= $v('badge') ?>" placeholder="Chung kết Inspiring Asia 2026 · Manila"></div>
    <div class="field" data-cover>
      <span class="field__label">Poster</span>
      <input type="hidden" name="poster_media_id" value="<?= $poster !== null ? (int) $poster['id'] : '' ?>" data-cover-id>
      <div class="cover-box">
        <img data-cover-img data-frame-ratio="<?= e((string) \App\Core\Image::FRAME_POSTER) ?>" alt="" width="160" height="67" style="aspect-ratio:2.39<?= $poster !== null ? ';object-position:' . e(Media::framePosition($poster, \App\Core\Image::FRAME_POSTER)) : '' ?>" <?= $poster !== null ? 'src="' . e(url(Media::url($poster, 480))) . '"' : 'hidden' ?>>
        <div class="cover-box__actions">
          <button type="button" class="btn btn--outline btn--sm" data-pick-cover>Chọn ảnh</button>
          <button type="button" class="btn btn--ghost btn--sm" data-clear-cover<?= $poster === null ? ' hidden' : '' ?>>Bỏ ảnh</button>
        </div>
      </div>
      <?= $err('poster_media_id') ?>
      <p class="xs muted">Mô tả (alt) của poster sửa ở Thư viện ảnh.</p>
    </div>
    <div class="field"><label for="trailer_url">Link trailer (https://…)</label><input id="trailer_url" name="trailer_url" type="url" maxlength="255" value="<?= $v('trailer_url') ?>"<?= $inv('trailer_url') ?>><?= $err('trailer_url') ?></div>
    <div class="field"><label for="related_tag">Tag bài hậu trường liên quan</label><input id="related_tag" name="related_tag" maxlength="120" value="<?= $v('related_tag') ?>" placeholder="phimngandoi"<?= $inv('related_tag') ?>><?= $err('related_tag') ?></div>
    <div class="field"><div class="field__row"><label for="seo_title">Tiêu đề SEO</label><span class="counter" data-counter-for="seo_title" data-max="60"></span></div><input id="seo_title" name="seo_title" maxlength="120" value="<?= $v('seo_title') ?>"></div>
    <div class="field"><div class="field__row"><label for="seo_desc">Mô tả SEO</label><span class="counter" data-counter-for="seo_desc" data-max="155"></span></div><textarea id="seo_desc" name="seo_desc" rows="3" maxlength="255"><?= $v('seo_desc') ?></textarea></div>
  </section>

  <div class="action-bar"><button type="submit" class="btn btn--primary">Lưu phim</button></div>
</form>

<section class="panel mt-5" id="milestones" aria-labelledby="ms-h">
  <h2 class="panel__title" id="ms-h">Dòng thời gian (<?= count($milestones) ?> mốc)</h2>
  <ol class="milestone-list">
    <?php foreach ($milestones as $i => $m): ?>
      <li class="milestone-item" id="milestone-<?= (int) $m['id'] ?>">
        <form method="post" action="<?= e(url('/admin/milestones/' . $m['id'])) ?>" class="milestone-item__form">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <div class="field-row">
            <div class="field"><label for="ml-<?= (int) $m['id'] ?>">Nhãn</label><input id="ml-<?= (int) $m['id'] ?>" name="label" maxlength="60" value="<?= e($m['label']) ?>" required></div>
            <div class="field"><label for="mt-<?= (int) $m['id'] ?>">Tiêu đề</label><input id="mt-<?= (int) $m['id'] ?>" name="title" maxlength="160" value="<?= e($m['title']) ?>" required></div>
          </div>
          <div class="field"><label for="md-<?= (int) $m['id'] ?>">Mô tả ngắn</label><input id="md-<?= (int) $m['id'] ?>" name="description" maxlength="255" value="<?= e((string) $m['description']) ?>"></div>
          <label class="check"><input type="checkbox" name="is_highlight" value="1"<?= $m['is_highlight'] ? ' checked' : '' ?>> Mốc nổi bật (tô màu)</label>
          <div class="milestone-item__actions">
            <button type="submit" class="btn btn--outline btn--sm">Lưu mốc</button>
            <button type="submit" form="mv-up-<?= (int) $m['id'] ?>" class="btn btn--ghost btn--sm"<?= $i === 0 ? ' disabled' : '' ?> aria-label="Đưa mốc “<?= e($m['title']) ?>” lên">↑</button>
            <button type="submit" form="mv-down-<?= (int) $m['id'] ?>" class="btn btn--ghost btn--sm"<?= $i === count($milestones) - 1 ? ' disabled' : '' ?> aria-label="Đưa mốc “<?= e($m['title']) ?>” xuống">↓</button>
            <button type="submit" form="mv-del-<?= (int) $m['id'] ?>" class="btn btn--ghost btn--sm">Xóa</button>
          </div>
        </form>
        <?php foreach (['up', 'down'] as $dir): ?>
          <form id="mv-<?= $dir ?>-<?= (int) $m['id'] ?>" method="post" action="<?= e(url('/admin/milestones/' . $m['id'] . '/move')) ?>">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="direction" value="<?= $dir ?>">
          </form>
        <?php endforeach; ?>
        <form id="mv-del-<?= (int) $m['id'] ?>" method="post" action="<?= e(url('/admin/milestones/' . $m['id'] . '/delete')) ?>" data-confirm="Xóa mốc “<?= e($m['title']) ?>”?">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        </form>
      </li>
    <?php endforeach; ?>
  </ol>

  <form method="post" action="<?= e(url('/admin/films/' . $film['id'] . '/milestones')) ?>" class="milestone-add">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <h3 class="h-3">Thêm mốc</h3>
    <div class="field-row">
      <div class="field"><label for="new-label">Nhãn</label><input id="new-label" name="label" maxlength="60" placeholder="2 tuần" required></div>
      <div class="field"><label for="new-title">Tiêu đề</label><input id="new-title" name="title" maxlength="160" placeholder="Tìm ý tưởng" required></div>
    </div>
    <div class="field"><label for="new-desc">Mô tả ngắn</label><input id="new-desc" name="description" maxlength="255"></div>
    <label class="check"><input type="checkbox" name="is_highlight" value="1"> Mốc nổi bật</label>
    <button type="submit" class="btn btn--outline">Thêm mốc</button>
  </form>
</section>

<?= partial('admin/media-picker') ?>
