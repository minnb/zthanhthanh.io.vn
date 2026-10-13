<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Markdown;
use App\Core\View;
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
