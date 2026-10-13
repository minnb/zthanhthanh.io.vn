<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\AdminPost;
use App\Models\Category;
use App\Models\Post;

/** Danh sách + trình soạn bài viết. */
final class PostController extends AdminController
{
    public function index(): string
    {
        $this->guard('editor');
        $filters = [
            'q' => mb_substr(Request::query('q'), 0, 100),
            'status' => Request::query('status'),
            'category' => (int) Request::query('category'),
            'source' => Request::query('source'),
            'trash' => Request::query('trash') === '1',
        ];
        $page = max(1, (int) Request::query('page', '1'));
        $result = AdminPost::paginate($filters, $page);

        return $this->render('posts/index', [
            'title' => $filters['trash'] ? 'Thùng rác' : 'Bài viết',
            'filters' => $filters,
            'result' => $result,
            'page' => $page,
            'categories' => Category::all(),
            'counts' => AdminPost::counts(),
        ]);
    }

    /** "＋ Viết": tạo bản nháp rồi mở trình soạn (có id ngay để tự lưu). */
    public function create(): string
    {
        $this->guard('editor');
        $id = AdminPost::createDraft((int) $this->user['id']);
        $this->changed('create', 'post', $id);
        $this->redirect('/admin/posts/' . $id);
    }

    public function edit(string $id): string
    {
        $this->guard('editor');
        $post = AdminPost::find((int) $id) ?? $this->notFound();
        return $this->editor($post, []);
    }

    public function update(string $id): string
    {
        $this->guard('editor');
        $post = AdminPost::find((int) $id) ?? $this->notFound();
        if ($post['deleted_at'] !== null) {
            $this->redirect('/admin/posts/' . $post['id'], 'Bài đang trong thùng rác. Khôi phục trước khi sửa.', 'error');
        }

        [$data, $errors, $form] = PostSaver::validate($post);
        if ($errors !== []) {
            http_response_code(422);
            return $this->editor(array_merge($post, $form), $errors);
        }

        ['saved' => $saved, 'justPublished' => $justPublished] = PostSaver::persist($post, $data, $form, (int) $this->user['id']);
        $this->changed($justPublished ? 'publish' : 'update', 'post', (int) $post['id'], ['status' => $data['status']]);
        $message = match (true) {
            $justPublished && $saved !== null && $saved['status_key'] === 'scheduled' => 'Đã hẹn giờ đăng bài.',
            $justPublished => 'Đã xuất bản bài viết.',
            default => 'Đã lưu.',
        };
        $this->redirect('/admin/posts/' . $post['id'], $message);
    }

    public function trash(string $id): string
    {
        $this->guard('editor');
        $post = AdminPost::find((int) $id) ?? $this->notFound();
        AdminPost::trash((int) $post['id']);
        $this->changed('trash', 'post', (int) $post['id']);
        $this->redirect('/admin/posts', 'Đã chuyển bài vào thùng rác (khôi phục được trong 30 ngày).');
    }

    public function restore(string $id): string
    {
        $this->guard('editor');
        $post = AdminPost::find((int) $id) ?? $this->notFound();
        AdminPost::restore((int) $post['id']);
        $this->changed('restore', 'post', (int) $post['id']);
        $this->redirect('/admin/posts/' . $post['id'], 'Đã khôi phục bài viết.');
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, string> $errors
     */
    private function editor(array $post, array $errors): string
    {
        // Bài đã xuất bản có bản nháp tự lưu khác nội dung đang đăng -> đề nghị khôi phục
        $draft = $post['status'] === 'published' ? AdminPost::autosaveDraft((int) $post['id']) : null;
        if ($draft !== null && $draft['title'] === $post['title'] && $draft['body_md'] === $post['body_md']) {
            $draft = null;
        }
        return $this->render('posts/edit', [
            'autosaveDraft' => $draft,
            'title' => $post['title'] !== '' ? $post['title'] : 'Bài mới',
            'post' => $post,
            'errors' => $errors,
            'categories' => Category::all(),
            'statuses' => AdminPost::STATUSES,
            'publicUrl' => PostSaver::isLive($post) ? Post::url(['category_slug' => $post['category_slug'], 'slug' => $post['slug']]) : null,
        ]);
    }
}
