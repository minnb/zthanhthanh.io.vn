<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Seo;
use App\Core\View;
use App\Models\Film;
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
            'breadcrumb' => [['label' => 'Trang chủ', 'url' => '/'], ['label' => 'Phim']],
        ]);
    }

    public function show(string $slug): string
    {
        $film = Film::findBySlug($slug) ?? throw HttpException::notFound();

        return View::render('pages/film-single', [
            'title' => (string) ($film['seo_title'] ?: 'Phim ' . $film['title']),
            'description' => (string) ($film['seo_desc'] ?: $film['logline']),
            'canonicalPath' => (string) $film['url'],
            'ogType' => 'video.movie',
            'jsonLd' => [Seo::movieSchema($film, Site::info())],
            'film' => $film,
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
