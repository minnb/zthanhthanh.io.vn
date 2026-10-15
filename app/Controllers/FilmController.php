<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Markdown;
use App\Core\Seo;
use App\Core\View;
use App\Models\Film;
use App\Models\Media;
use App\Models\Post;
use App\Models\Site;

final class FilmController
{
    public function index(): string
    {
        return View::render('pages/film-list', [
            'title' => 'Phim',
            'description' => 'Các phim và dự án phim của biên kịch Thanh Thanh: phim ngắn ĐỢI và những dự án đang phát triển.',
            'films' => Film::all(),
            'styles' => ['film'],
            'breadcrumb' => [['label' => 'Trang chủ', 'url' => '/'], ['label' => 'Phim']],
        ]);
    }

    public function show(string $slug): string
    {
        $film = Film::findBySlug($slug) ?? throw HttpException::notFound();
        $share = $film['poster'] ? Media::shareImage($film['poster']) : null; // 1200×630 cắt quanh điểm tâm

        return View::render('pages/film-single', [
            'title' => (string) ($film['seo_title'] ?: 'Phim ' . $film['title']),
            // Dự phòng khi admin để trống logline: mô tả phim, rồi câu mặc định (trang được index luôn cần description)
            'description' => (string) ($film['seo_desc'] ?: $film['logline']
                ?: Markdown::htmlToText((string) $film['synopsis_html'])
                ?: 'Phim ' . $film['title'] . ' của biên kịch ' . (Site::info()['authorName'] ?? '') . '.'),
            'canonicalPath' => (string) $film['url'],
            'ogType' => 'video.movie',
            'image' => $share['url'] ?? '',
            'imageWidth' => $share['width'] ?? 0,
            'imageHeight' => $share['height'] ?? 0,
            'imageAlt' => $film['poster'] ? (string) $film['poster']['alt'] : '',
            'jsonLd' => [Seo::movieSchema($film, Site::info())],
            'film' => $film,
            'styles' => ['film'],
            'milestones' => Film::milestones((int) $film['id']),
            'related' => !empty($film['related_tag']) ? Post::byTagSlug((string) $film['related_tag'], 3) : [],
            'breadcrumb' => [
                ['label' => 'Trang chủ', 'url' => '/'],
                ['label' => 'Phim', 'url' => '/phim'],
                ['label' => (string) $film['title']],
            ],
        ]);
    }
}
