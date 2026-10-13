<?php
/**
 * Trình soạn bài (admin.md): Markdown + thanh công cụ, Viết/Xem trước, tự lưu 30 giây,
 * khung Xuất bản, khung SEO (bộ đếm, xem trước Google/Facebook, checklist).
 *
 * @var array<string, mixed> $post
 * @var array<string, string> $errors
 * @var list<array<string, mixed>> $categories
 * @var array<string, string> $statuses
 * @var string|null $publicUrl
 * @var array<string, mixed>|null $autosaveDraft  Bản nháp tự lưu của bài đã xuất bản
 * @var string $csrf
 * @var array<string, mixed> $site
 */
use App\Models\Media;

$id = (int) $post['id'];
$cover = is_array($post['cover'] ?? null) ? $post['cover'] : null;
$coverAlt = (string) ($post['cover_alt'] ?? ($cover['alt'] ?? ''));
$publishedAt = !empty($post['published_at']) ? date('Y-m-d\TH:i', strtotime((string) $post['published_at']) ?: time()) : '';
$isTrashed = !empty($post['deleted_at']);
$host = (string) parse_url(url('/'), PHP_URL_HOST);
$err = static fn (string $key): string => isset($errors[$key])
    ? '<p class="field__error" id="err-' . e($key) . '">' . e($errors[$key]) . '</p>' : '';
$describedBy = static fn (string $key): string => isset($errors[$key]) ? ' aria-describedby="err-' . e($key) . '" aria-invalid="true"' : '';
$selectedCategory = (int) ($post['category_id'] ?? 0);
?>
<header class="admin-head">
  <div>
    <p class="xs muted"><a href="<?= e(url('/admin/posts')) ?>">Bài viết</a> / Sửa</p>
    <h1 class="h-3 mt-2"><?= e($post['title'] !== '' ? $post['title'] : 'Bài mới') ?></h1>
  </div>
  <div class="admin-head__meta">
    <span class="status status--<?= e((string) ($post['status_key'] ?? $post['status'])) ?>"><?= e((string) ($post['status_label'] ?? '')) ?></span>
    <span class="xs muted" data-save-state aria-live="polite"></span>
    <?php if ($publicUrl !== null): ?><a class="small" href="<?= e(url($publicUrl)) ?>" target="_blank" rel="noopener">Xem trên web ↗</a><?php endif; ?>
  </div>
</header>

<?php if ($isTrashed): ?>
  <div class="notice notice--error">Bài đang trong thùng rác.
    <form method="post" action="<?= e(url('/admin/posts/' . $id . '/restore')) ?>" class="inline">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <button type="submit" class="btn btn--outline btn--sm">Khôi phục</button>
    </form>
  </div>
<?php endif; ?>

<?php if (!empty($autosaveDraft)): ?>
  <div class="notice" data-autosave-draft>
    Có bản nháp tự lưu lúc <?= e(date('H:i d/m', strtotime((string) $autosaveDraft['updated_at']) ?: time())) ?> chưa được đăng
    (bài trên web vẫn là bản cũ).
    <button type="button" class="btn btn--outline btn--sm" data-restore-autosave>Mở bản nháp để sửa tiếp</button>
    <textarea hidden data-draft-title><?= e((string) $autosaveDraft['title']) ?></textarea>
    <textarea hidden data-draft-body><?= e((string) $autosaveDraft['body_md']) ?></textarea>
  </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
  <div class="notice notice--error" role="alert">
    <strong>Chưa lưu được:</strong>
    <ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form id="post-form" method="post" action="<?= e(url('/admin/posts/' . $id)) ?>" class="editor"
      data-editor
      data-post-id="<?= $id ?>"
      data-autosave-url="<?= e(url('/admin/api/posts/' . $id . '/autosave')) ?>"
      data-preview-url="<?= e(url('/admin/api/preview')) ?>"
      data-base-url="<?= e(url('/')) ?>"
      data-site-name="<?= e($site['name'] ?? '') ?>"
      data-host="<?= e($host) ?>">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <div class="editor__main">
    <div class="field">
      <label for="title">Tiêu đề</label>
      <input id="title" name="title" type="text" maxlength="255" class="input--title" value="<?= e($post['title']) ?>"<?= $describedBy('title') ?> placeholder="Tiêu đề bài viết">
      <?= $err('title') ?>
    </div>

    <div class="md-editor">
      <div class="md-editor__bar">
        <div class="md-toolbar" role="toolbar" aria-label="Định dạng">
          <button type="button" data-md="bold" aria-label="In đậm"><strong>B</strong></button>
          <button type="button" data-md="italic" aria-label="In nghiêng"><em>I</em></button>
          <button type="button" data-md="h2" aria-label="Tiêu đề mục">H2</button>
          <button type="button" data-md="quote" aria-label="Trích dẫn">“</button>
          <button type="button" data-md="list" aria-label="Danh sách">•</button>
          <button type="button" data-md="link" aria-label="Chèn link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
          <button type="button" data-md="image" aria-label="Chèn ảnh từ thư viện"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></button>
        </div>
        <div class="tabs" role="tablist" aria-label="Chế độ soạn">
          <button type="button" role="tab" id="tab-write" aria-selected="true" aria-controls="body" data-tab="write">Viết</button>
          <button type="button" role="tab" id="tab-preview" aria-selected="false" aria-controls="body-preview" data-tab="preview">Xem trước</button>
        </div>
      </div>
      <label for="body" class="sr-only">Nội dung (Markdown)</label>
      <textarea id="body" name="body_md" rows="18" class="md-editor__input" role="tabpanel" aria-labelledby="tab-write"<?= $describedBy('body_md') ?>><?= e((string) $post['body_md']) ?></textarea>
      <div id="body-preview" class="md-editor__preview prose" role="tabpanel" aria-labelledby="tab-preview" hidden></div>
    </div>
    <?= $err('body_md') ?>
    <p class="xs muted"><span data-word-count>0</span> từ · Tự lưu mỗi 30 giây · Xuống dòng như khi viết Facebook</p>
  </div>

  <div class="editor__side">
    <section class="panel" aria-labelledby="pub-h">
      <h2 class="panel__title" id="pub-h">Xuất bản</h2>
      <div class="field">
        <label for="status">Trạng thái</label>
        <select id="status" name="status">
          <?php foreach ($statuses as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= $post['status'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="published_at">Ngày giờ đăng <span class="muted xs">(để tương lai = hẹn giờ)</span></label>
        <input id="published_at" name="published_at" type="datetime-local" value="<?= e($publishedAt) ?>"<?= $describedBy('published_at') ?>>
        <?= $err('published_at') ?>
      </div>
      <div class="field">
        <label for="category_id">Danh mục</label>
        <select id="category_id" name="category_id"<?= $describedBy('category_id') ?>>
          <option value="">— Chọn danh mục —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" data-slug="<?= e($c['slug']) ?>"<?= $selectedCategory === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $err('category_id') ?>
      </div>
      <div class="field">
        <label for="tags">Thẻ <span class="muted xs">(cách nhau bằng dấu phẩy)</span></label>
        <input id="tags" name="tags" type="text" value="<?= e((string) ($post['tags'] ?? '')) ?>" placeholder="hautruongbienkich, PhimngắnĐỢI">
      </div>
      <div class="field">
        <label for="excerpt">Tóm tắt <span class="muted xs">(hiện ở thẻ bài, mô tả SEO dự phòng)</span></label>
        <textarea id="excerpt" name="excerpt" rows="3"><?= e((string) ($post['excerpt'] ?? '')) ?></textarea>
      </div>

      <div class="field" data-cover>
        <span class="field__label">Ảnh bìa</span>
        <input type="hidden" name="cover_media_id" value="<?= $cover !== null ? (int) $cover['id'] : '' ?>" data-cover-id>
        <div class="cover-box">
          <img data-cover-img data-frame-ratio="<?= e((string) \App\Core\Image::FRAME_COVER) ?>"<?= $cover !== null ? ' data-share-pos="' . e(Media::framePosition($cover, \App\Core\Image::FRAME_SHARE)) . '"' : '' ?> alt="" width="160" height="90" style="aspect-ratio:16/9<?= $cover !== null ? ';object-position:' . e(Media::framePosition($cover, \App\Core\Image::FRAME_COVER)) : '' ?>" <?= $cover !== null ? 'src="' . e(url(Media::url($cover, 480))) . '"' : 'hidden' ?>>
          <div class="cover-box__actions">
            <button type="button" class="btn btn--outline btn--sm" data-pick-cover>Chọn ảnh</button>
            <button type="button" class="btn btn--ghost btn--sm" data-clear-cover<?= $cover === null ? ' hidden' : '' ?>>Bỏ ảnh</button>
          </div>
        </div>
        <?= $err('cover') ?>
        <label for="cover_alt" class="mt-2">Mô tả ảnh (alt) <span class="muted xs">– bắt buộc khi xuất bản</span></label>
        <input id="cover_alt" name="cover_alt" type="text" maxlength="255" value="<?= e($coverAlt) ?>"<?= $describedBy('cover_alt') ?> placeholder="Vd: Thanh Thanh tập vai An tại nhà">
        <?= $err('cover_alt') ?>
      </div>
    </section>

    <section class="panel" aria-labelledby="seo-h">
      <h2 class="panel__title" id="seo-h">SEO</h2>
      <div class="field">
        <label for="slug">Đường dẫn</label>
        <div class="input-prefix">
          <span data-slug-prefix>/<?= e((string) ($post['category_slug'] ?? '…')) ?>/</span>
          <input id="slug" name="slug" type="text" maxlength="150" value="<?= e((string) $post['slug']) ?>"<?= $describedBy('slug') ?>>
        </div>
        <?= $err('slug') ?>
        <?php if ($publicUrl !== null): ?><p class="xs muted">Đổi đường dẫn của bài đã đăng sẽ tự tạo chuyển hướng 301 từ link cũ.</p><?php endif; ?>
      </div>
      <div class="field">
        <div class="field__row"><label for="seo_title">Tiêu đề SEO</label><span class="counter" data-counter-for="seo_title" data-max="60"></span></div>
        <input id="seo_title" name="seo_title" type="text" maxlength="120" value="<?= e((string) ($post['seo_title'] ?? '')) ?>"<?= $describedBy('seo_title') ?> placeholder="Để trống = dùng tiêu đề bài">
        <?= $err('seo_title') ?>
      </div>
      <div class="field">
        <div class="field__row"><label for="seo_desc">Mô tả SEO</label><span class="counter" data-counter-for="seo_desc" data-max="155"></span></div>
        <textarea id="seo_desc" name="seo_desc" rows="3" maxlength="255"<?= $describedBy('seo_desc') ?> placeholder="Để trống = dùng tóm tắt"><?= e((string) ($post['seo_desc'] ?? '')) ?></textarea>
        <?= $err('seo_desc') ?>
      </div>

      <p class="field__label">Xem trước trên Google</p>
      <div class="serp" aria-hidden="true">
        <p class="serp__url"><?= e($host) ?> › <span data-serp-path></span></p>
        <p class="serp__title" data-serp-title></p>
        <p class="serp__desc" data-serp-desc></p>
      </div>
      <p class="field__label">Xem trước khi chia sẻ Facebook</p>
      <div class="fbcard" aria-hidden="true">
        <div class="fbcard__img" data-fb-img></div>
        <div class="fbcard__body"><p class="fbcard__host"><?= e(strtoupper($host)) ?></p><p class="fbcard__title" data-fb-title></p></div>
      </div>

      <p class="field__label">Checklist SEO</p>
      <ul class="checklist" data-checklist aria-live="polite"></ul>
      <label class="check"><input type="checkbox" name="noindex" value="1"<?= !empty($post['noindex']) ? ' checked' : '' ?>> Không cho Google index (noindex)</label>
    </section>

    <?php if (!$isTrashed): ?>
      <section class="panel">
        <button type="submit" form="trash-form" class="btn btn--ghost btn--block">Chuyển vào thùng rác</button>
      </section>
    <?php endif; ?>
  </div>

  <div class="action-bar">
    <button type="submit" class="btn btn--outline" name="intent" value="save">Lưu</button>
    <?php if ($post['status'] !== 'published'): ?>
      <button type="submit" class="btn btn--primary" name="intent" value="publish">Xuất bản</button>
    <?php else: ?>
      <button type="submit" class="btn btn--primary" name="intent" value="save">Cập nhật</button>
    <?php endif; ?>
  </div>
</form>

<form id="trash-form" method="post" action="<?= e(url('/admin/posts/' . $id . '/trash')) ?>" data-confirm="Chuyển bài này vào thùng rác? (khôi phục được trong 30 ngày)">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
</form>

<?= partial('admin/media-picker') ?>
