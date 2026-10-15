<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Seo;
use App\Core\View;
use App\Models\Book;
use App\Models\Film;
use App\Models\Media;
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
        $blocks = is_array($blocks) ? array_values(array_filter($blocks, 'is_string')) : [];
        // Khối của Góc sáng tác có thể bật trên trang chủ (tools/activate_reading_shelf.php) -> nạp thêm studio.css
        $hasStudioBlocks = array_intersect(['script-preview', 'reading-shelf'], $blocks) !== [];
        $site = Site::info();
        // Ảnh chân dung đầu trang (Cài đặt → hero_media_id); chỉ tra DB ở trang chủ. Không có/đã xóa -> hình minh họa của theme
        $heroId = (int) Setting::get('hero_media_id', '');
        $heroImage = $heroId > 0 ? Media::find($heroId) : null;

        return View::render('pages/home', [
            'heroImage' => $heroImage,
            'styles' => $hasStudioBlocks ? ['home', 'studio'] : ['home'],
            'title' => '',
            'jsonLd' => [Seo::websiteSchema($site), Seo::personSchema($site)],
            'description' => (string) Setting::get('mission_intro', ''),
            'blocks' => $blocks,
            'featuredFilm' => $film,
            'allFilms' => Film::all(),
            'milestones' => $film !== null ? Film::milestones((int) $film['id']) : [],
            'achievements' => (array) Setting::json('achievements', []),
            'latestPosts' => Post::latest(3),
            // Chỉ truy vấn tủ sách khi trang chủ thật sự có khối reading-shelf
            'featuredBooks' => in_array('reading-shelf', $blocks, true) ? Book::allVisible() : [],
        ]);
    }
}
