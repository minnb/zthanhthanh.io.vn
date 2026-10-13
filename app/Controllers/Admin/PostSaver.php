<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Slug;
use App\Models\AdminPost;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;

/**
 * Kiểm tra + lưu bài viết, dùng chung cho trình soạn (form) và API của tool đăng bài (JSON).
 * Request::str()/raw()/int() đọc được cả form lẫn JSON nên hai đường vào dùng đúng một bộ quy tắc.
 */
final class PostSaver
{
    /**
     * Đọc + kiểm tra dữ liệu bài (tên trường giống form trình soạn).
     *
     * @param array<string, mixed> $post Bài hiện có; tạo mới qua API thì truyền newPostStub()
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, mixed>} [data, errors, form]
     */
    public static function validate(array $post): array
    {
        $errors = [];
        $form = [
            'title' => mb_substr(Request::str('title'), 0, 255),
            'slug' => Request::str('slug'),
            'excerpt' => Request::str('excerpt'),
            'body_md' => Request::raw('body_md'),
            'category_id' => Request::int('category_id'),
            'status' => Request::str('status', 'draft'),
            'published_at' => Request::str('published_at'),
            'seo_title' => Request::str('seo_title'),
            'seo_desc' => Request::str('seo_desc'),
            'noindex' => Request::bool('noindex') ? 1 : 0,
            'cover_media_id' => Request::int('cover_media_id'),
            'cover_alt' => mb_substr(Request::str('cover_alt'), 0, 255),
            'tags' => mb_substr(Request::str('tags'), 0, 500),
        ];

        $status = isset(AdminPost::STATUSES[$form['status']]) ? $form['status'] : 'draft';
        // Nút "Xuất bản" gửi intent=publish (ghi đè ô trạng thái)
        if (Request::str('intent') === 'publish') {
            $status = 'published';
        }
        $publishing = $status === 'published';

        $slug = Slug::make($form['slug'] !== '' ? $form['slug'] : $form['title'], 150);
        if ($slug === '') {
            $slug = (string) $post['slug'];
        }
        if ($slug === '') {
            $errors['slug'] = 'Cần tiêu đề hoặc đường dẫn cho bài.';
        } elseif (AdminPost::slugTaken($slug, (int) $post['id'])) {
            $errors['slug'] = 'Đường dẫn này đã có bài khác dùng. Hãy đổi một chút.';
        }

        $categoryIds = array_map(static fn (array $c): int => (int) $c['id'], Category::all());
        $categoryId = in_array($form['category_id'], $categoryIds, true) ? $form['category_id'] : null;

        $cover = $form['cover_media_id'] > 0 ? Media::find($form['cover_media_id']) : null;
        if ($form['cover_media_id'] > 0 && $cover === null) {
            $errors['cover'] = 'Ảnh bìa không còn tồn tại, hãy chọn lại.';
        }

        $publishedAt = null;
        if ($form['published_at'] !== '') {
            $ts = strtotime($form['published_at']);
            if ($ts === false) {
                $errors['published_at'] = 'Ngày giờ đăng không hợp lệ.';
            } else {
                $publishedAt = date('Y-m-d H:i:s', $ts);
            }
        }

        if ($publishing) {
            if ($form['title'] === '') {
                $errors['title'] = 'Cần tiêu đề trước khi xuất bản.';
            }
            if (trim($form['body_md']) === '') {
                $errors['body_md'] = 'Bài chưa có nội dung.';
            }
            if ($categoryId === null) {
                $errors['category_id'] = 'Chọn danh mục trước khi xuất bản.';
            }
            if ($cover !== null && $form['cover_alt'] === '') {
                $errors['cover_alt'] = 'Ảnh bìa cần mô tả (alt) trước khi xuất bản.';
            }
            $publishedAt ??= $post['published_at'] ?: date('Y-m-d H:i:s');
        }
        if (mb_strlen($form['seo_title']) > 120) {
            $errors['seo_title'] = 'Tiêu đề SEO tối đa 120 ký tự (nên ≤ 60).';
        }
        if (mb_strlen($form['seo_desc']) > 255) {
            $errors['seo_desc'] = 'Mô tả SEO tối đa 255 ký tự (nên ≤ 155).';
        }

        $data = [
            'title' => $form['title'],
            'slug' => $slug,
            'excerpt' => $form['excerpt'],
            'body_md' => $form['body_md'],
            'category_id' => $categoryId,
            'status' => $status,
            'published_at' => $publishedAt,
            'seo_title' => $form['seo_title'],
            'seo_desc' => $form['seo_desc'],
            'noindex' => (bool) $form['noindex'],
            'cover_media_id' => $cover !== null ? (int) $cover['id'] : null,
        ];
        // Giá trị để hiển thị lại form khi có lỗi
        $form['slug'] = $slug;
        $form['status'] = $status;
        $form['category_id'] = $categoryId;
        $form['cover'] = $cover;
        if ($cover !== null) {
            $form['cover']['alt'] = $form['cover_alt'];
        }
        return [$data, $errors, $form];
    }

    /**
     * Lưu bài đã qua validate(): nội dung, revision, tag, alt ảnh bìa, 301 khi bài đang hiện ra ngoài đổi URL.
     * Nơi gọi tự ghi audit_log + xóa cache (AdminController::changed).
     *
     * @param array<string, mixed> $post Bài hiện có (trước khi lưu)
     * @param array<string, mixed> $data
     * @param array<string, mixed> $form
     * @return array{saved: array<string, mixed>|null, justPublished: bool}
     */
    public static function persist(array $post, array $data, array $form, int $userId): array
    {
        $wasLive = self::isLive($post);
        $oldUrl = Post::url(['category_slug' => $post['category_slug'], 'slug' => $post['slug']]);

        AdminPost::save((int) $post['id'], $data);
        AdminPost::addRevision((int) $post['id'], $userId, $data['title'], $data['body_md']);
        AdminPost::clearAutosave((int) $post['id']); // nội dung đã lưu chính thức -> bỏ bản nháp tự lưu
        Tag::sync((int) $post['id'], explode(',', (string) $form['tags']));
        if ($data['cover_media_id'] !== null) {
            $cover = Media::find($data['cover_media_id']);
            Media::updateMeta($data['cover_media_id'], (string) $form['cover_alt'], (string) ($cover['caption'] ?? ''));
        }

        // Bài đang hiện ra ngoài mà đổi URL (slug/danh mục) -> 301 từ URL cũ (seo.md)
        $saved = AdminPost::find((int) $post['id']);
        if ($saved !== null && $wasLive) {
            $newUrl = Post::url(['category_slug' => $saved['category_slug'], 'slug' => $saved['slug']]);
            if ($newUrl !== $oldUrl) {
                Redirect::add($oldUrl, $newUrl);
            }
        }
        return ['saved' => $saved, 'justPublished' => $data['status'] === 'published' && $post['status'] !== 'published'];
    }

    /** Bài "ảo" để validate() trước khi tạo bài mới qua API (chưa có id, slug, ngày đăng). @return array<string, mixed> */
    public static function newPostStub(): array
    {
        return ['id' => 0, 'slug' => '', 'status' => 'draft', 'published_at' => null, 'category_slug' => null, 'deleted_at' => null];
    }

    /** Bài đang hiện ra ngoài (đã xuất bản, tới giờ, chưa xóa, có danh mục). @param array<string, mixed> $post */
    public static function isLive(array $post): bool
    {
        return $post['status'] === 'published'
            && empty($post['deleted_at'])
            && !empty($post['category_slug'])
            && !empty($post['published_at'])
            && strtotime((string) $post['published_at']) <= time();
    }
}
