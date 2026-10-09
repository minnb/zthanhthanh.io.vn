<?php
declare(strict_types=1);

/*
 * Mẫu cấu hình. Copy thành app/config.php rồi sửa giá trị (config.php không commit).
 *   Copy-Item src\thanhthanh\app\config.sample.php src\thanhthanh\app\config.php
 * Giá trị mặc định dưới đây dùng cho XAMPP local.
 */
return [
    // local | production
    'env' => 'local',

    // URL gốc của site, KHÔNG có dấu / ở cuối. Production: https://ten-mien.vn
    'base_url' => 'http://localhost/projects/thanhthanh/src/thanhthanh/public',

    'timezone' => 'Asia/Ho_Chi_Minh',
    'locale' => 'vi',
    'theme' => 'default',

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'thanhthanh',
        'user' => 'root',
        'pass' => '',
    ],

    // Mã xác minh Google Search Console (thẻ meta google-site-verification)
    'search_console_verification' => '',

    // Production: 301 về đúng scheme + host của base_url (HTTPS, có/không www).
    // Đặt false nếu hosting báo HTTPS theo cách lạ gây vòng lặp chuyển hướng.
    'force_canonical_host' => true,

    // Cache HTML trang công khai ra storage/cache/pages. Bật ở production (true), local để false.
    'page_cache' => false,
    // Thời gian sống của cache (giây). Admin lưu bài sẽ xóa cache ngay, TTL chỉ là lưới an toàn.
    'page_cache_ttl' => 600,
];
