<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Image;
use App\Core\Request;
use App\Models\Media;
use RuntimeException;

/** Thư viện ảnh: upload (WebP 480/960/1440), sửa alt/chú thích, xóa ảnh không còn dùng. */
final class MediaController extends AdminController
{
    public function index(): string
    {
        $this->guard('editor');
        $page = max(1, (int) Request::query('page', '1'));
        $q = mb_substr(Request::query('q'), 0, 100);
        $result = Media::paginate($page, 24, $q);
        $result['items'] = self::libraryItems($result['items']);
        return $this->render('media/index', [
            'title' => 'Thư viện ảnh',
            'result' => $result,
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / 24)),
            'q' => $q,
        ]);
    }

    /** Upload một hoặc nhiều ảnh. Gọi từ form (redirect + toast) hoặc fetch (JSON, khi Accept: application/json). */
    public function upload(): string
    {
        $this->guard('editor');
        $wantsJson = str_contains(Request::header('Accept'), 'application/json');
        $alt = mb_substr(Request::str('alt'), 0, 255);
        $files = self::normalizeFiles($_FILES['files'] ?? $_FILES['file'] ?? null);

        $created = [];
        $errors = [];
        $small = [];
        if ($files === []) {
            $errors[] = 'Chưa chọn ảnh nào.';
        }
        foreach ($files as $file) {
            $name = (string) $file['name'];
            if ((int) $file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = $name . ': ' . self::uploadError((int) $file['error']);
                continue;
            }
            try {
                $stored = Image::storeUpload((string) $file['tmp_name'], $name, APP_ROOT . '/public/uploads');
                $id = Media::create($stored, $name, $alt, (int) $this->user['id']);
                $created[] = Media::find($id);
                $this->changed('upload', 'media', $id);
                if ($stored['width'] < Image::SHARE_WIDTH) { // vẫn nhận, chỉ nhắc
                    $small[] = $name . ' (rộng ' . $stored['width'] . 'px)';
                }
            } catch (RuntimeException $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            }
        }

        $smallNote = $small === [] ? '' : ' Ảnh nhỏ, có thể mờ trên màn hình lớn và Facebook (nên rộng ≥ ' . Image::SHARE_WIDTH . 'px): ' . implode(', ', $small) . '.';
        if ($wantsJson) {
            $items = array_map([self::class, 'apiItem'], array_values(array_filter($created)));
            return $items === []
                ? $this->json(['ok' => false, 'error' => implode(' ', $errors)], 422)
                : $this->json(['ok' => true, 'data' => $items, 'warnings' => array_merge($errors, $small === [] ? [] : [trim($smallNote)])]);
        }
        $message = count($created) > 0 ? 'Đã tải lên ' . count($created) . ' ảnh.' : '';
        if ($errors !== []) {
            $this->redirect('/admin/media', trim($message . ' Lỗi: ' . implode(' ', $errors) . $smallNote), 'error');
        }
        $this->redirect('/admin/media', $message . ' Nhớ điền mô tả (alt) và kiểm điểm tâm cho từng ảnh.' . $smallNote);
    }

    public function update(string $id): string
    {
        $this->guard('editor');
        $media = Media::find((int) $id) ?? $this->notFound();
        Media::updateMeta((int) $media['id'], mb_substr(Request::str('alt'), 0, 255), mb_substr(Request::str('caption'), 0, 255));

        // Điểm tâm (bấm chọn trên ảnh): đổi thì lưu và tạo lại ảnh chia sẻ Facebook cắt quanh điểm mới
        $message = 'Đã lưu ảnh.';
        $type = 'success';
        $fx = Media::clampFocus(Request::int('focus_x', (int) $media['focus_x']));
        $fy = Media::clampFocus(Request::int('focus_y', (int) $media['focus_y']));
        if ($fx !== (int) $media['focus_x'] || $fy !== (int) $media['focus_y']) {
            Media::updateFocus((int) $media['id'], $fx, $fy);
            try {
                Image::rebuildShareImage(['focus_x' => $fx, 'focus_y' => $fy] + $media, APP_ROOT . '/public/uploads');
            } catch (RuntimeException $e) {
                $message = 'Đã lưu, nhưng chưa tạo lại được ảnh chia sẻ Facebook: ' . $e->getMessage();
                $type = 'error';
            }
        }
        $this->changed('update', 'media', (int) $media['id'], ['focus' => [$fx, $fy]]);
        $this->redirect('/admin/media?page=' . max(1, Request::int('page', 1)) . '#media-' . $media['id'], $message, $type);
    }

    public function delete(string $id): string
    {
        $this->guard('editor');
        $media = Media::find((int) $id) ?? $this->notFound();
        $usage = Media::usage($media);
        if ($usage !== []) {
            $this->redirect('/admin/media', 'Không xóa được: ảnh đang được dùng (' . implode('; ', $usage) . ').', 'error');
        }
        Media::delete($media, APP_ROOT . '/public/uploads');
        $this->changed('delete', 'media', (int) $media['id'], ['path' => $media['path']]);
        $this->redirect('/admin/media', 'Đã xóa ảnh.');
    }

    /** Khung hiển thị của site, xem trước trong Thư viện ảnh (nhãn => tỉ lệ rộng/cao). */
    private const PREVIEW_FRAMES = ['Ảnh bìa' => Image::FRAME_COVER, 'Poster' => Image::FRAME_POSTER, 'Facebook' => Image::FRAME_SHARE];

    /**
     * Chuẩn bị dữ liệu thẻ ảnh cho view Thư viện (template chỉ còn if/foreach – theme-ui.md):
     * ảnh 480, chiều cao tương ứng, điểm tâm, và object-position của từng khung xem trước.
     *
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    public static function libraryItems(array $items): array
    {
        return array_map(static function (array $m): array {
            $m['thumb'] = url(Media::url($m, 480));
            $m['thumb_height'] = (int) round((int) $m['height'] * 480 / max(1, (int) $m['width']));
            $m['focus_x'] = (int) ($m['focus_x'] ?? 50);
            $m['focus_y'] = (int) ($m['focus_y'] ?? 50);
            $m['previews'] = [];
            foreach (self::PREVIEW_FRAMES as $label => $ratio) {
                $m['previews'][] = [
                    'label' => $label,
                    'ratio' => number_format($ratio, 4, '.', ''),
                    'position' => Media::framePosition($m, $ratio),
                ];
            }
            return $m;
        }, $items);
    }

    /**
     * Dữ liệu ảnh gọn cho API/JS chọn ảnh.
     *
     * @param array<string, mixed> $m
     * @return array<string, mixed>
     */
    public static function apiItem(array $m): array
    {
        return [
            'id' => (int) $m['id'],
            'thumb' => url(Media::url($m, 480)),
            'url' => Media::url($m, 960),
            'alt' => (string) $m['alt'],
            'width' => (int) $m['width'],
            'height' => (int) $m['height'],
            'focus_x' => (int) ($m['focus_x'] ?? 50),
            'focus_y' => (int) ($m['focus_y'] ?? 50),
        ];
    }

    /**
     * $_FILES dạng một file hoặc mảng nhiều file -> danh sách thống nhất.
     *
     * @param mixed $input
     * @return list<array{name: string, tmp_name: string, error: int}>
     */
    private static function normalizeFiles(mixed $input): array
    {
        if (!is_array($input) || !isset($input['name'])) {
            return [];
        }
        if (!is_array($input['name'])) {
            return $input['error'] === UPLOAD_ERR_NO_FILE ? [] : [['name' => (string) $input['name'], 'tmp_name' => (string) $input['tmp_name'], 'error' => (int) $input['error']]];
        }
        $out = [];
        foreach (array_keys($input['name']) as $i) {
            if ((int) $input['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = ['name' => (string) $input['name'][$i], 'tmp_name' => (string) $input['tmp_name'][$i], 'error' => (int) $input['error'][$i]];
        }
        return array_slice($out, 0, 20); // tối đa 20 ảnh mỗi lần
    }

    private static function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'file quá lớn so với giới hạn máy chủ.',
            UPLOAD_ERR_PARTIAL => 'tải lên bị gián đoạn, hãy thử lại.',
            default => 'không tải lên được (mã ' . $code . ').',
        };
    }
}
