<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Seo;
use App\Core\View;
use App\Models\Film;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Site;

final class HomeController
{
    /** Trang chủ ghép từ các khối (thứ tự theo setting home_blocks). */
    public function index(): string
    {
        $film = Film::featured();
        $blocks = Setting::json('home_blocks', []);
        $site = Site::info();

        return View::render('pages/home', [
            'title' => '',
            'jsonLd' => [Seo::websiteSchema($site), Seo::personSchema($site)],
            'description' => (string) Setting::get('mission_intro', ''),
            'blocks' => is_array($blocks) ? $blocks : [],
            'featuredFilm' => $film,
            'milestones' => $film !== null ? Film::milestones((int) $film['id']) : [],
            'achievements' => (array) Setting::json('achievements', []),
            'latestPosts' => Post::latest(3),
        ]);
    }
}
