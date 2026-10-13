<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\AdminPage;
use App\Models\Page;

/** Trang tĩnh: Giới thiệu, Liên hệ, Press kit. */
final class PageController extends AdminController
{
    public function index(): string
    {
        $this->guard('editor');
        return $this->render('pages/index', ['title' => 'Trang tĩnh', 'pages' => AdminPage::all()]);
    }

    public function edit(string $id): string
    {
        $this->guard('editor');
        $page = AdminPage::find((int) $id) ?? $this->notFound();
        return $this->editor($page, []);
    }

    public function update(string $id): string
    {
        $this->guard('editor');
        $page = AdminPage::find((int) $id) ?? $this->notFound();

        $translationOf = Request::int('translation_of');
        $data = [
            'title' => mb_substr(Request::str('title'), 0, 255),
            'body_md' => Request::raw('body_md'),
            'status' => Request::str('status') === 'published' ? 'published' : 'draft',
            'seo_title' => mb_substr(Request::str('seo_title'), 0, 120),
            'seo_desc' => mb_substr(Request::str('seo_desc'), 0, 255),
            'translation_of' => $translationOf > 0 ? $translationOf : null,
        ];
        $errors = [];
        if ($data['title'] === '') {
            $errors['title'] = 'Trang cần có tiêu đề.';
        }
        if ($data['translation_of'] !== null) {
            $target = AdminPage::find($data['translation_of']);
            // Bản dịch phải trỏ tới trang gốc khác ngôn ngữ (không trỏ chính nó, không trỏ một bản dịch khác)
            if ($target === null || (int) $target['id'] === (int) $page['id'] || $target['lang'] === $page['lang'] || $target['translation_of'] !== null) {
                $errors['translation_of'] = 'Chọn trang gốc ở ngôn ngữ khác.';
            }
        }
        if ($errors !== []) {
            http_response_code(422);
            return $this->editor(array_merge($page, $data), $errors);
        }

        AdminPage::update((int) $page['id'], $data);
        $this->changed('update', 'page', (int) $page['id']);
        $this->redirect('/admin/pages/' . $page['id'], 'Đã lưu trang.');
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, string> $errors
     */
    private function editor(array $page, array $errors): string
    {
        $others = array_values(array_filter(
            AdminPage::all(),
            static fn (array $p): bool => $p['lang'] !== $page['lang'] && $p['translation_of'] === null
        ));
        return $this->render('pages/edit', [
            'title' => 'Trang: ' . $page['title'],
            'page' => $page,
            'errors' => $errors,
            'translationOptions' => $others,
            'publicUrl' => $page['status'] === 'published' ? Page::path($page) : null,
        ]);
    }
}
