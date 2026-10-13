<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Cache;
use App\Core\Image;
use App\Core\Request;
use App\Models\Media;
use App\Models\Setting;

/** Cài đặt site – chỉ admin (admin.md). */
final class SettingsController extends AdminController
{
    /** Khóa setting sửa được => [nhãn, kiểu, độ dài tối đa]. */
    public const FIELDS = [
        'site_name' => ['Tên site', 'text', 60],
        'site_tagline' => ['Dòng mô tả ngắn', 'text', 60],
        'mission' => ['Câu sứ mệnh (tiêu đề trang chủ)', 'text', 160],
        'mission_intro' => ['Đoạn giới thiệu trang chủ', 'textarea', 400],
        'author_name' => ['Tên tác giả', 'text', 120],
        'author_bio' => ['Tiểu sử ngắn (hộp tác giả)', 'textarea', 300],
        'contact_email' => ['Email liên hệ', 'email', 190],
        'social_facebook' => ['Facebook (https://…)', 'url', 255],
        'social_threads' => ['Threads (https://…)', 'url', 255],
        'default_og_image' => ['Ảnh chia sẻ mặc định (đường dẫn ảnh trong thư viện, để trống = ảnh của theme)', 'path', 255],
        // Chọn từ Thư viện ảnh; lưu id ảnh. Để trống = hình minh họa của theme
        'hero_media_id' => ['Ảnh chân dung đầu trang chủ (khung dọc 4:5)', 'media', 10],
    ];

    public function edit(): string
    {
        $this->guard('admin');
        $values = [];
        foreach (array_keys(self::FIELDS) as $key) {
            $values[$key] = (string) Setting::get($key, '');
        }
        return $this->form($values, []);
    }

    public function update(): string
    {
        $this->guard('admin');
        $values = [];
        $errors = [];
        foreach (self::FIELDS as $key => [$label, $type, $max]) {
            $value = $type === 'textarea' ? trim(Request::raw($key)) : Request::str($key);
            $values[$key] = $value;
            if (mb_strlen($value) > $max) {
                $errors[$key] = "Tối đa {$max} ký tự.";
            } elseif ($value !== '' && $type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$key] = 'Email không hợp lệ.';
            } elseif ($value !== '' && $type === 'url' && !preg_match('#^https://#i', $value)) {
                $errors[$key] = 'Link phải bắt đầu bằng https://';
            } elseif ($value !== '' && $type === 'path' && !preg_match('#^/uploads/[a-z0-9/_-]+\.webp$#', $value)) {
                $errors[$key] = 'Dán đường dẫn ảnh dạng /uploads/…webp (lấy ở Thư viện ảnh).';
            } elseif ($value !== '' && $type === 'media' && (!ctype_digit($value) || Media::find((int) $value) === null)) {
                $errors[$key] = 'Ảnh không còn tồn tại, hãy chọn lại.';
            }
        }
        if ($values['site_name'] === '') {
            $errors['site_name'] = 'Cần tên site.';
        }
        if ($errors !== []) {
            http_response_code(422);
            return $this->form($values, $errors);
        }
        foreach ($values as $key => $value) {
            Setting::set($key, $value);
        }
        $this->changed('update', 'settings', null);
        $this->redirect('/admin/settings', 'Đã lưu cài đặt.');
    }

    public function flushCache(): string
    {
        $this->guard('admin');
        $count = Cache::flush();
        $this->changed('flush_cache', 'settings', null, ['files' => $count]);
        $this->redirect('/admin/settings', "Đã xóa {$count} trang trong cache.");
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function form(array $values, array $errors): string
    {
        return $this->render('settings', [
            'title' => 'Cài đặt',
            'fields' => self::FIELDS,
            'values' => $values,
            'errors' => $errors,
            'previews' => self::previews($values),
        ]);
    }

    /**
     * Ảnh đã chọn cho các ô kiểu 'media': xem trước đúng khung 4:5 + điểm tâm (template chỉ if/foreach – theme-ui.md).
     * Public để trang xem thử /_dev/admin/settings dùng chung.
     *
     * @param array<string, string> $values
     * @return array<string, array{thumb: string, position: string, share: string}>
     */
    public static function previews(array $values): array
    {
        $previews = [];
        foreach (self::FIELDS as $key => [, $type]) {
            $media = $type === 'media' && ($values[$key] ?? '') !== '' ? Media::find((int) $values[$key]) : null;
            if ($media !== null) {
                $previews[$key] = [
                    'thumb' => url(Media::url($media, 480)),
                    'position' => Media::framePosition($media, Image::FRAME_PORTRAIT),
                    'share' => Media::framePosition($media, Image::FRAME_SHARE),
                ];
            }
        }
        return $previews;
    }
}
