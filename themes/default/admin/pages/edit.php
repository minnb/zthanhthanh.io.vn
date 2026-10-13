<?php
/**
 * @var array<string, mixed> $page
 * @var array<string, string> $errors
 * @var list<array<string, mixed>> $translationOptions
 * @var string|null $publicUrl
 * @var string $csrf
 */
use App\Models\Page;

$v = static fn (string $key): string => e((string) ($page[$key] ?? ''));
$err = static fn (string $key): string => isset($errors[$key]) ? '<p class="field__error" id="err-' . e($key) . '">' . e($errors[$key]) . '</p>' : '';
?>
<header class="admin-head">
  <div>
    <p class="xs muted"><a href="<?= e(url('/admin/pages')) ?>">Trang tĩnh</a> / <?= e(Page::path($page)) ?> (<?= e(strtoupper((string) $page['lang'])) ?>)</p>
    <h1 class="h-3 mt-2"><?= $v('title') ?></h1>
  </div>
  <?php if ($publicUrl !== null): ?><a class="small" href="<?= e(url($publicUrl)) ?>" target="_blank" rel="noopener">Xem trên web ↗</a><?php endif; ?>
</header>

<?php if ($errors !== []): ?>
  <div class="notice notice--error" role="alert"><strong>Chưa lưu được:</strong>
    <ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/pages/' . $page['id'])) ?>" class="form-grid" data-editor-lite>
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <section class="panel">
    <div class="field"><label for="title">Tiêu đề</label><input id="title" name="title" maxlength="255" value="<?= $v('title') ?>"<?= isset($errors['title']) ? ' aria-invalid="true" aria-describedby="err-title"' : '' ?>><?= $err('title') ?></div>
    <div class="field"><label for="body_md">Nội dung (Markdown)</label><textarea id="body_md" name="body_md" rows="18" class="md-editor__input"><?= $v('body_md') ?></textarea></div>
  </section>
  <section class="panel">
    <div class="field">
      <label for="status">Trạng thái</label>
      <select id="status" name="status">
        <option value="published"<?= $page['status'] === 'published' ? ' selected' : '' ?>>Đã xuất bản</option>
        <option value="draft"<?= $page['status'] !== 'published' ? ' selected' : '' ?>>Nháp (ẩn khỏi web)</option>
      </select>
    </div>
    <div class="field">
      <label for="translation_of">Là bản dịch của <span class="xs muted">(sinh hreflang)</span></label>
      <select id="translation_of" name="translation_of"<?= isset($errors['translation_of']) ? ' aria-invalid="true" aria-describedby="err-translation_of"' : '' ?>>
        <option value="">— Không —</option>
        <?php foreach ($translationOptions as $o): ?>
          <option value="<?= (int) $o['id'] ?>"<?= (int) ($page['translation_of'] ?? 0) === (int) $o['id'] ? ' selected' : '' ?>><?= e($o['title'] . ' (' . strtoupper((string) $o['lang']) . ')') ?></option>
        <?php endforeach; ?>
      </select><?= $err('translation_of') ?>
    </div>
    <div class="field"><div class="field__row"><label for="seo_title">Tiêu đề SEO</label><span class="counter" data-counter-for="seo_title" data-max="60"></span></div><input id="seo_title" name="seo_title" maxlength="120" value="<?= $v('seo_title') ?>"></div>
    <div class="field"><div class="field__row"><label for="seo_desc">Mô tả SEO</label><span class="counter" data-counter-for="seo_desc" data-max="155"></span></div><textarea id="seo_desc" name="seo_desc" rows="3" maxlength="255"><?= $v('seo_desc') ?></textarea></div>
  </section>
  <div class="action-bar"><button type="submit" class="btn btn--primary">Lưu trang</button></div>
</form>
