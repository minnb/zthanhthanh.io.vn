<?php
declare(strict_types=1);

/**
 * Script đồng bộ nội dung (bài viết, chuyên mục, media, tag, phim, trang tĩnh)
 * Chạy trên Hosting qua SSH:  php database/sync_content.php
 */

if (PHP_SAPI !== 'cli') {
    exit("Chỉ chạy qua dòng lệnh CLI.\n");
}

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

use App\Core\Db;

$sqlFile = __DIR__ . '/posts_and_media.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Lỗi: Không tìm thấy file {$sqlFile}\n");
    exit(1);
}

echo "==> Đang đồng bộ nội dung bài viết và media vào Database...\n";

$sql = file_get_contents($sqlFile);
try {
    Db::pdo()->exec($sql);
    echo " OK  Đã nạp thành công toàn bộ 12 bài viết, chuyên mục, tag và media vào cơ sở dữ liệu!\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "LỖI khi chạy SQL: " . $e->getMessage() . "\n");
    exit(1);
}

// Kiểm tra thư mục uploads
$uploadsDir = $root . '/public/uploads/2026/10';
if (!is_dir($uploadsDir)) {
    echo " !!  CẢNH BÁO: Thư mục ảnh {$uploadsDir} chưa có file ảnh.\n";
    echo "     Hãy tải file uploads_media.zip lên và giải nén vào public/uploads/\n";
    echo "     để ảnh bài viết hiển thị đầy đủ.\n";
} else {
    echo " OK  Thư mục ảnh public/uploads/2026/10 đã sẵn sàng.\n";
}

// Xóa cache HTML để site nhận ngay bài mới
$cacheDir = $root . '/storage/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    foreach ($files as $f) {
        if (basename($f) !== '.gitkeep' && is_file($f)) {
            @unlink($f);
        }
    }
    echo " OK  Đã dọn dẹp cache HTML trong storage/cache/.\n";
}

echo "\n==> ĐỒNG BỘ HOÀN TẤT! Bạn có thể mở website để kiểm tra các bài viết mới.\n";
