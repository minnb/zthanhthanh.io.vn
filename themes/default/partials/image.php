<?php
/**
 * Ảnh responsive từ thư viện (seo.md): srcset WebP 480/960/1440, width/height chống nhảy bố cục.
 * Ảnh LCP (ảnh bìa đầu trang): $eager = true -> không lazy, fetchpriority="high".
 * Ảnh trong khung cố định (CSS aspect-ratio + object-fit: cover): truyền $ratio (Image::FRAME_*) để khung
 * giữ điểm tâm của ảnh ở giữa (object-position) thay vì luôn cắt chính giữa.
 *
 * @var array<string, mixed> $media  Bản ghi media
 * @var string|null $sizes
 * @var bool|null $eager
 * @var string|null $class
 * @var float|null $ratio
 */
use App\Models\Media;

$img = Media::imageData($media, isset($ratio) ? (float) $ratio : null);
$eager = !empty($eager);
?>
<img src="<?= e($img['src']) ?>" srcset="<?= e($img['srcset']) ?>" sizes="<?= e($sizes ?? '100vw') ?>"
     width="<?= (int) $img['width'] ?>" height="<?= (int) $img['height'] ?>" alt="<?= e($img['alt']) ?>"
     <?= $eager ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async"<?= !empty($class) ? ' class="' . e($class) . '"' : '' ?><?= $img['position'] !== '' ? ' style="object-position:' . e($img['position']) . '"' : '' ?>>
