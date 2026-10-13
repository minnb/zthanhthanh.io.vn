<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Markdown;
use App\Core\Request;
use App\Models\AdminPost;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use PDOException;
use Throwable;

/**
 * API JSON cho trình soạn (admin.md): luôn trả {ok, data|error} với mã HTTP đúng,
 * kể cả khi chưa đăng nhập hoặc sai CSRF (không trả trang HTML).
 */
final class ApiController extends AdminController
{
    /** Xem trước Markdown bằng đúng bộ render của trang công khai. */
    public function preview(): string
    {
        return $this->handle(function (): array {
            if (!array_key_exists('body', Request::json() + $_POST)) {
                throw new HttpException(422, 'Thiếu nội dung để xem trước.');
            }
            $body = Request::raw('body');
            if (mb_strlen($body) > 500_000) {
                throw new HttpException(413, 'Nội dung quá dài.');
            }
            return ['html' => Markdown::toHtml($body)];
        });
    }

    /** Tự lưu tiêu đề + nội dung (mỗi 30 giây khi có thay đổi). */
    public function autosave(string $id): string
    {
        return $this->handle(function () use ($id): array {
            $post = AdminPost::find((int) $id);
            if ($post === null || $post['deleted_at'] !== null) {
                throw HttpException::notFound();
            }
            // Thiếu trường (JSON hỏng/cắt cụt) -> từ chối, KHÔNG được ghi rỗng đè lên bài
            $input = Request::json() + $_POST;
            if (!array_key_exists('title', $input) || !array_key_exists('body_md', $input)) {
                throw new HttpException(422, 'Dữ liệu tự lưu không hợp lệ, chưa lưu.');
            }
            // Bài đã xuất bản: chỉ lưu bản nháp, web không đổi cho tới khi bấm "Cập nhật"
            $mode = AdminPost::autosave(
                (int) $post['id'],
                (string) $post['status'],
                (int) $this->user['id'],
                mb_substr(Request::str('title'), 0, 255),
                Request::raw('body_md')
            );
            return ['savedAt' => date('H:i'), 'mode' => $mode];
        });
    }

    /** Danh sách ảnh cho hộp chọn ảnh. */
    public function media(): string
    {
        return $this->handle(function (): array {
            $page = max(1, (int) Request::query('page', '1'));
            $result = Media::paginate($page, 24, mb_substr(Request::query('q'), 0, 100));
            return [
                'items' => array_map([MediaController::class, 'apiItem'], $result['items']),
                'total' => $result['total'],
                'page' => $page,
            ];
        });
    }

    /**
     * Phiên làm việc cho tool đăng bài (tools/contents/fbpost): người dùng, token CSRF, danh mục.
     * Tool gửi token qua header X-CSRF-Token ở mọi POST, không phải đọc HTML.
     */
    public function session(): string
    {
        return $this->handle(fn (): array => [
            'user' => ['id' => (int) $this->user['id'], 'name' => (string) $this->user['name'], 'role' => (string) $this->user['role']],
            'csrf' => Csrf::token(),
            'categories' => array_map(
                static fn (array $c): array => ['id' => (int) $c['id'], 'slug' => (string) $c['slug'], 'name' => (string) $c['name']],
                Category::all()
            ),
            'statuses' => array_keys(AdminPost::STATUSES),
        ]);
    }

    /**
     * Tạo bài từ tool đăng bài. Trường giống form trình soạn (PostSaver::validate) + source, source_ref, approved_by_human.
     * 201 {id, status, url, edit_url} | 409 trùng source_ref {id, url, edit_url} | 422 {errors} | 403 thiếu CSRF.
     */
    public function createPost(): string
    {
        return $this->handle(function (): array {
            $this->checkPayloadSize();
            ['source' => $source, 'ref' => $ref] = $this->sourceInput();
            if ($ref !== '' && ($existing = AdminPost::findBySourceRef($ref)) !== null) {
                throw self::duplicate($existing);
            }
            if (trim(Request::str('title')) === '') {
                throw new ApiException(422, 'Dữ liệu chưa hợp lệ.', ['errors' => ['title' => 'Cần tiêu đề.']]);
            }
            [$data, $errors, $form] = PostSaver::validate(PostSaver::newPostStub());
            $this->checkApproval($data, $source);
            if ($errors !== []) {
                // Yêu cầu song song cùng bài gốc: yêu cầu kia có thể vừa đăng xong GIỮA bước kiểm source_ref ở trên và bước kiểm
                // đường dẫn (cùng tiêu đề -> cùng slug) -> lỗi "trùng đường dẫn" thực chất là "bài này đã đăng": kiểm lại.
                if ($ref !== '' && ($existing = AdminPost::findBySourceRef($ref)) !== null) {
                    throw self::duplicate($existing);
                }
                throw new ApiException(422, 'Dữ liệu chưa hợp lệ.', ['errors' => $errors]);
            }

            // Tạo bài trong 1 transaction: lỗi giữa chừng không để lại bản nháp rỗng. Hai yêu cầu đồng thời (bấm Duyệt
            // 2 lần) cùng lọt qua bước kiểm ở trên -> ràng buộc UNIQUE (source_ref, slug) chặn ở DB -> trả 409/422.
            $pdo = Db::pdo();
            $pdo->beginTransaction();
            try {
                $id = AdminPost::createDraft((int) $this->user['id']);
                AdminPost::setSource($id, $source, $ref);
                $post = AdminPost::find($id) ?? throw HttpException::notFound();
                ['saved' => $saved, 'justPublished' => $justPublished] = PostSaver::persist($post, $data, $form, (int) $this->user['id']);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($e instanceof PDOException && (string) $e->getCode() === '23000') {
                    if (str_contains($e->getMessage(), 'uq_posts_source_ref') && ($existing = AdminPost::findBySourceRef($ref)) !== null) {
                        throw self::duplicate($existing);
                    }
                    if (str_contains($e->getMessage(), 'uq_posts_slug')) {
                        throw new ApiException(422, 'Dữ liệu chưa hợp lệ.', ['errors' => ['slug' => 'Đường dẫn này đã có bài khác dùng. Hãy đổi một chút.']]);
                    }
                }
                throw $e;
            }
            $this->changed($justPublished ? 'publish' : 'create', 'post', $id, ['via' => 'api-tool', 'source' => $source, 'status' => $data['status']]);
            return self::postRefs($saved ?? $post);
        }, 201);
    }

    /** Cập nhật bài đã đăng từ tool (vd sửa lại sau khi 409). Cùng quy tắc với createPost(). */
    public function updatePost(string $id): string
    {
        return $this->handle(function () use ($id): array {
            $this->checkPayloadSize();
            $post = AdminPost::find((int) $id);
            if ($post === null) {
                throw HttpException::notFound();
            }
            if ($post['deleted_at'] !== null) {
                throw new ApiException(409, 'Bài đang trong thùng rác. Khôi phục trong admin trước khi sửa.', self::postRefs($post));
            }
            [$data, $errors, $form] = PostSaver::validate($post);
            $this->checkApproval($data, (string) $post['source']);
            if ($errors !== []) {
                throw new ApiException(422, 'Dữ liệu chưa hợp lệ.', ['errors' => $errors]);
            }
            ['saved' => $saved, 'justPublished' => $justPublished] = PostSaver::persist($post, $data, $form, (int) $this->user['id']);
            $this->changed($justPublished ? 'publish' : 'update', 'post', (int) $post['id'], ['via' => 'api-tool', 'status' => $data['status']]);
            return self::postRefs($saved ?? $post);
        });
    }

    /** 409 cho bài trùng source_ref; bài đó đang trong thùng rác thì nói rõ (cần khôi phục trước khi ghi đè). @param array<string, mixed> $existing */
    private static function duplicate(array $existing): ApiException
    {
        $trashed = $existing['deleted_at'] !== null;
        $message = $trashed
            ? 'Bài này đã đăng trước đó nhưng đang trong thùng rác (bài #' . (int) $existing['id'] . '). Khôi phục trong admin trước.'
            : 'Bài này đã được đăng trước đó.';
        return new ApiException(409, $message, self::postRefs($existing) + ['trashed' => $trashed]);
    }

    /** @return array{source: string, ref: string} */
    private function sourceInput(): array
    {
        $source = Request::str('source', 'manual');
        if (!isset(AdminPost::SOURCES[$source])) {
            throw new ApiException(422, 'Dữ liệu chưa hợp lệ.', ['errors' => ['source' => 'Nguồn chỉ nhận: ' . implode(', ', array_keys(AdminPost::SOURCES)) . '.']]);
        }
        return ['source' => $source, 'ref' => mb_substr(Request::str('source_ref'), 0, 255)];
    }

    /**
     * admin.md: bài từ Facebook/AI chỉ được xuất bản khi NGƯỜI đã duyệt trong tool (approved_by_human = true).
     *
     * @param array<string, mixed> $data
     */
    private function checkApproval(array $data, string $source): void
    {
        if ($data['status'] === 'published' && in_array($source, ['facebook', 'ai-draft'], true) && !Request::bool('approved_by_human')) {
            throw new ApiException(422, 'Bài từ Facebook/AI cần người duyệt trước khi xuất bản.', ['errors' => ['status' => 'Thiếu xác nhận người duyệt (approved_by_human).']]);
        }
    }

    private function checkPayloadSize(): void
    {
        if (mb_strlen(Request::raw('body_md')) > 500_000) {
            throw new ApiException(413, 'Nội dung quá dài.');
        }
    }

    /**
     * @param array<string, mixed> $post
     * @return array{id: int, slug: string, status: string, status_key: string, url: string|null, edit_url: string}
     */
    private static function postRefs(array $post): array
    {
        $hasUrl = !empty($post['category_slug']) && !empty($post['slug']);
        return [
            'id' => (int) $post['id'],
            'slug' => (string) $post['slug'], // tool gửi lại khi cập nhật để URL bài đã đăng không đổi theo tiêu đề

            'status' => (string) $post['status'],
            'status_key' => (string) ($post['status_key'] ?? $post['status']),
            'url' => $hasUrl ? url(Post::url(['category_slug' => $post['category_slug'], 'slug' => $post['slug']])) : null,
            'edit_url' => url('/admin/posts/' . (int) $post['id']),
        ];
    }

    /** @param callable(): array<string, mixed> $action */
    private function handle(callable $action, int $okStatus = 200): string
    {
        try {
            $this->guard('editor'); // POST -> kiểm CSRF (header X-CSRF-Token)
            return $this->json(['ok' => true, 'data' => $action()], $okStatus);
        } catch (ApiException $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()] + $e->extra, $e->status);
        } catch (HttpException $e) {
            // 303 (chưa đăng nhập) -> 401 cho API
            $status = $e->status >= 400 ? $e->status : 401;
            $message = $status === 401 ? 'Phiên đăng nhập đã hết. Hãy đăng nhập lại (nội dung vẫn được giữ trên máy).' : $e->getMessage();
            return $this->json(['ok' => false, 'error' => $message], $status);
        } catch (Throwable $e) {
            \App\Core\ErrorHandler::log($e);
            return $this->json(['ok' => false, 'error' => 'Lỗi máy chủ, chưa lưu được.'], 500);
        }
    }
}
