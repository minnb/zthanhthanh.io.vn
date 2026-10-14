<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Markdown;
use App\Core\View;
use App\Models\Book;
use App\Models\Film;
use App\Models\Page;

/** Trang tĩnh lấy từ bảng pages. */
final class PageController
{
    public function about(): string
    {
        return $this->render('gioi-thieu', 'vi');
    }

    public function contact(): string
    {
        return $this->render('lien-he', 'vi');
    }

    /** Không gian sáng tác: Kịch bản ĐỢI và Tủ sách gối đầu giường của Biên kịch. */
    public function studio(): string
    {
        return View::render('pages/studio', [
            'title' => 'Không gian sáng tác: Kịch bản ĐỢI & Tủ sách',
            'description' => 'Trích đoạn kịch bản phim ngắn ĐỢI (Top 7 Inspiring Asia 2026 tại Manila) và 10 cuốn sách nuôi dưỡng tư duy kể chuyện điện ảnh của biên kịch Thanh Thanh.',
            'canonicalPath' => '/goc-sang-tac',
            'featuredBooks' => Book::allVisible(),
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

    private function render(string $slug, string $lang): string
    {
        $page = Page::findPublished($slug, $lang) ?? throw HttpException::notFound();

        return View::render('pages/page', [
            'title' => (string) ($page['seo_title'] ?: $page['title']),
            'description' => (string) ($page['seo_desc'] ?: Markdown::excerpt(Markdown::htmlToText((string) $page['html']), 155)),
            'canonicalPath' => Page::path($page),
            'alternates' => Page::alternates($page),
            'page' => $page,
            'breadcrumb' => [['label' => 'Trang chủ', 'url' => '/'], ['label' => (string) $page['title']]],
        ]);
    }
}
