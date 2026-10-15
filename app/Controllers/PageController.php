<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Markdown;
use App\Core\View;
use App\Models\Book;
use App\Models\Film;
use App\Models\Page;
use App\Models\Setting;

/** Trang tĩnh lấy từ bảng pages. */
final class PageController
{
    public function about(): string
    {
        $about = Setting::json('about_page', []);
        return $this->render('gioi-thieu', 'vi', 'pages/about', [
            // 3 chương, câu trích, dòng thời gian sự nghiệp (setting JSON; thiếu/sai định dạng -> template bỏ qua khối đó)
            'about' => is_array($about) ? $about : [],
            'styles' => ['about'],
        ]);
    }

    public function contact(): string
    {
        return $this->render('lien-he', 'vi', 'pages/contact', ['styles' => ['contact']]);
    }

    /** Không gian sáng tác: Kịch bản ĐỢI và Tủ sách gối đầu giường của Biên kịch. */
    public function studio(): string
    {
        $film = Film::featured();
        $books = Book::allVisible();
        $shelf = $books !== [] ? ' và ' . count($books) . ' cuốn sách nuôi dưỡng tư duy kể chuyện điện ảnh' : '';

        return View::render('pages/studio', [
            'title' => 'Không gian sáng tác: Kịch bản ĐỢI & Tủ sách',
            'description' => 'Trích đoạn kịch bản phim ngắn ĐỢI (Top 7 Inspiring Asia 2026 tại Manila)' . $shelf . ' của biên kịch Thanh Thanh.',
            'canonicalPath' => '/goc-sang-tac',
            'styles' => ['studio'],
            'featuredBooks' => $books,
            'featuredFilm' => $film,
            'milestones' => $film !== null ? Film::milestones((int) $film['id']) : [],
            'breadcrumb' => [
                ['label' => 'Trang chủ', 'url' => '/'],
                ['label' => 'Không gian sáng tác'],
            ],
        ]);
    }

    /** Press kit tiếng Anh cho ban tổ chức liên hoan phim, báo chí. */
    public function pressKit(): string
    {
        $page = Page::findPublished('press-kit', 'en') ?? throw HttpException::notFound();

        return View::render('pages/press-kit', [
            'lang' => 'en',
            'title' => (string) ($page['seo_title'] ?: 'Press kit'),
            'description' => (string) ($page['seo_desc'] ?: Markdown::excerpt(Markdown::htmlToText((string) $page['html']), 155)),
            'canonicalPath' => Page::path($page),
            'alternates' => Page::alternates($page),
            'page' => $page,
            'films' => Film::all(),
            'breadcrumb' => [['label' => 'Home', 'url' => '/'], ['label' => 'Press kit']],
        ]);
    }

    /**
     * Trang tĩnh từ bảng pages; $template cho phép trang có bố cục riêng (Giới thiệu, Liên hệ),
     * $extra là dữ liệu riêng của template đó (khối nội dung, CSS theo trang).
     *
     * @param array<string, mixed> $extra
     */
    private function render(string $slug, string $lang, string $template = 'pages/page', array $extra = []): string
    {
        $page = Page::findPublished($slug, $lang) ?? throw HttpException::notFound();

        return View::render($template, $extra + [
            'title' => (string) ($page['seo_title'] ?: $page['title']),
            'description' => (string) ($page['seo_desc'] ?: Markdown::excerpt(Markdown::htmlToText((string) $page['html']), 155)),
            'canonicalPath' => Page::path($page),
            'alternates' => Page::alternates($page),
            'page' => $page,
            'breadcrumb' => [['label' => 'Trang chủ', 'url' => '/'], ['label' => (string) $page['title']]],
        ]);
    }
}
