<?php
declare(strict_types=1);

namespace App\Models;

/** Thông tin chung của site (tên, menu, mạng xã hội, tác giả) lấy từ bảng settings. */
final class Site
{
    /** @return array<string, mixed> */
    public static function info(): array
    {
        $menu = Setting::json('menu_main', []);
        return [
            'name' => Setting::get('site_name', 'Thanh Thanh'),
            'tagline' => Setting::get('site_tagline', ''),
            'mission' => Setting::get('mission', ''),
            'missionIntro' => Setting::get('mission_intro', ''),
            'authorName' => Setting::get('author_name', ''),
            'authorBio' => Setting::get('author_bio', ''),
            'contactEmail' => Setting::get('contact_email', 'doicho147@gmail.com'),
            'contactAddress' => Setting::get('contact_address', 'Thôn Vân Sa, xã Cổ Đô, Hà Nội'),
            'ogImage' => Setting::get('default_og_image', ''),
            'authorAvatar' => Setting::get('author_avatar', ''),
            'social' => array_filter([
                'Facebook' => Setting::get('social_facebook', 'https://www.facebook.com/thanhthanh.KOII'),
                'Threads' => Setting::get('social_threads', ''),
                'TikTok' => Setting::get('social_tiktok', ''),
            ]),
            'menu' => is_array($menu) ? $menu : [],
        ];
    }

    /**
     * Fallback khi DB lỗi (để trang lỗi vẫn hiển thị được).
     *
     * @return array<string, mixed>
     */
    public static function fallback(): array
    {
        return [
            'name' => 'Thanh Thanh', 'tagline' => 'Biên kịch', 'mission' => '', 'missionIntro' => '',
            'authorName' => 'Thanh Thanh', 'authorBio' => '', 'contactEmail' => 'doicho147@gmail.com',
            'contactAddress' => 'Thôn Vân Sa, xã Cổ Đô, Hà Nội',
            'ogImage' => '', 'authorAvatar' => '', 'social' => [], 'menu' => [],
        ];

    }
}
