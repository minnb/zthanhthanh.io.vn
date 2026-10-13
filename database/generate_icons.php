<?php
declare(strict_types=1);

/**
 * Script vẽ bộ favicon chuẩn với chất lượng siêu cao (2x supersampling)
 * và xuất ra SVG, PNG các kích cỡ, và favicon.ico đa tầng.
 */

$rootDir = dirname(__DIR__);
$publicDir = $rootDir . '/public';

// Kích thước canvas vẽ gốc (supersampled 1024x1024)
$W = 1024;
$H = 1024;
$img = imagecreatetruecolor($W, $H);
imagealphablending($img, true);
imagesavealpha($img, true);

// Màu trong suốt ban đầu
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

// Bảng màu nhận diện thương hiệu
$cDarkBg    = imagecolorallocate($img, 27, 18, 16);     // #1B1210 Nền rạp chiếu phim
$cBorder    = imagecolorallocate($img, 186, 146, 92);   // #BA925C Viền ánh đồng thau
$cGold      = imagecolorallocate($img, 218, 175, 78);   // #DAAF4E Vàng đồng kim loại
$cGoldDark  = imagecolorallocate($img, 160, 120, 50);   // Vàng đồng bóng đổ
$cPaper     = imagecolorallocate($img, 250, 245, 238);  // #FAF5EE Giấy kịch bản
$cPenSlit   = imagecolorallocate($img, 142, 31, 47);    // #8E1F2F Đỏ ngòi bút
$cRuby      = imagecolorallocate($img, 230, 25, 75);    // #E6194B Đỏ REC rực rỡ
$cRubyGlow  = imagecolorallocatealpha($img, 230, 25, 75, 75); // Hào quang đỏ REC
$cWhite     = imagecolorallocate($img, 255, 255, 255);

// 1. Vẽ Squircle (Bo góc tròn 240px trên canvas 1024)
function drawRoundedRect($im, $x1, $y1, $x2, $y2, $radius, $color): void {
    // 4 góc tròn
    imagefilledellipse($im, (int)($x1 + $radius), (int)($y1 + $radius), (int)($radius * 2), (int)($radius * 2), $color);
    imagefilledellipse($im, (int)($x2 - $radius), (int)($y1 + $radius), (int)($radius * 2), (int)($radius * 2), $color);
    imagefilledellipse($im, (int)($x1 + $radius), (int)($y2 - $radius), (int)($radius * 2), (int)($radius * 2), $color);
    imagefilledellipse($im, (int)($x2 - $radius), (int)($y2 - $radius), (int)($radius * 2), (int)($radius * 2), $color);
    // 2 hình chữ nhật lấp đầy thân
    imagefilledrectangle($im, (int)($x1 + $radius), (int)$y1, (int)($x2 - $radius), (int)$y2, $color);
    imagefilledrectangle($im, (int)$x1, (int)($y1 + $radius), (int)$x2, (int)($y2 - $radius), $color);
}

// Viền ngoài ánh kim
drawRoundedRect($img, 24, 24, 1000, 1000, 240, $cBorder);
// Nền trong điện ảnh
drawRoundedRect($img, 38, 38, 986, 986, 226, $cDarkBg);

// 2. Vành Bánh Xe Lăn & Cuộn Phim (The Resilient Wheel & Film Spool)
$cx = 512;
$cy = 532;
$radius = 296;

// Vành mỏng ngoài
imagesetthickness($img, 8);
imagearc($img, $cx, $cy, $radius * 2, $radius * 2, 0, 360, $cGoldDark);

// Cung tròn chuyển động dày (Dynamic Forward Arc từ 70 độ đến 310 độ)
imagesetthickness($img, 26);
imagearc($img, $cx, $cy, $radius * 2 - 8, $radius * 2 - 8, 45, 305, $cGold);

// Các răng cưa phim nhựa (Film perforations trên vành bánh xe)
for ($angle = 0; $angle < 360; $angle += 22.5) {
    $rad = deg2rad($angle);
    $px = (int)($cx + cos($rad) * ($radius - 4));
    $py = (int)($cy + sin($rad) * ($radius - 4));
    imagefilledellipse($img, $px, $py, 12, 12, $cGold);
}

// 3 Nan hoa bánh xe
imagesetthickness($img, 10);
imageline($img, $cx, $cy, (int)($cx - cos(deg2rad(45)) * $radius * 0.85), (int)($cy + sin(deg2rad(45)) * $radius * 0.85), $cGoldDark);
imageline($img, $cx, $cy, (int)($cx + cos(deg2rad(45)) * $radius * 0.85), (int)($cy + sin(deg2rad(45)) * $radius * 0.85), $cGoldDark);
imageline($img, $cx, $cy, (int)($cx - cos(deg2rad(15)) * $radius * 0.85), (int)($cy - sin(deg2rad(15)) * $radius * 0.85), $cGoldDark);

// Trục tâm bánh xe (Center Hub)
imagefilledellipse($img, $cx, $cy, 60, 60, $cDarkBg);
imagesetthickness($img, 12);
imageellipse($img, $cx, $cy, 60, 60, $cGold);
imagefilledellipse($img, $cx, $cy, 22, 22, $cGold);

// 3. Chữ lồng "TT" & Ngòi bút biên kịch
// Chữ T thứ hai (T2 - Vàng kim, xếp sau)
// Thanh ngang T2
imagefilledrectangle($img, 360, 310, 690, 375, $cGold);
// Thân T2
imagefilledrectangle($img, 485, 375, 565, 640, $cGold);

// Chữ T thứ nhất (T1 - Trắng giấy kịch bản, xếp trước với ngòi bút sắc nhọn)
// Thanh ngang T1
imagefilledrectangle($img, 280, 270, 640, 345, $cPaper);
// Thân T1
imagefilledrectangle($img, 420, 345, 500, 560, $cPaper);

// Mũi ngòi bút máy (Tam giác nhọn chúc xuống)
$penPoints = [
    420, 560,
    500, 560,
    460, 650,
];
imagefilledpolygon($img, $penPoints, $cPaper);

// Đường rãnh mực biên kịch (Slit) màu đỏ Ruby
imagesetthickness($img, 7);
imageline($img, 460, 410, 460, 620, $cPenSlit);
imagefilledellipse($img, 460, 400, 12, 12, $cPenSlit);

// 4. Đèn "REC" / Ngọn lửa đam mê ở góc trên bên phải
$recX = 744;
$recY = 232;
imagefilledellipse($img, $recX, $recY, 96, 96, $cRubyGlow);
imagefilledellipse($img, $recX, $recY, 56, 56, $cRuby);
imagefilledellipse($img, $recX - 8, $recY - 8, 18, 18, $cWhite);

// Hàm xuất kích thước với chất lượng resample tối đa
function saveResampled($src, int $size, string $targetPath): void {
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, 1024, 1024);
    imagepng($dst, $targetPath, 9);
    imagedestroy($dst);
    echo "  [OK] Đã tạo {$targetPath} ({$size}x{$size})\n";
}

echo "Bắt đầu xuất bộ icon website:\n";
// Xuất PNG các kích thước tiêu chuẩn
saveResampled($img, 180, $publicDir . '/apple-touch-icon.png');
saveResampled($img, 192, $publicDir . '/android-chrome-192x192.png');
saveResampled($img, 512, $publicDir . '/android-chrome-512x512.png');
saveResampled($img, 32,  $publicDir . '/favicon-32x32.png');
saveResampled($img, 16,  $publicDir . '/favicon-16x16.png');

// 5. Đóng gói file favicon.ico đa tầng (16x16 + 32x32)
$png16 = file_get_contents($publicDir . '/favicon-16x16.png');
$png32 = file_get_contents($publicDir . '/favicon-32x32.png');

$icoHeader = pack('vvv', 0, 1, 2); // 2 ảnh (16x16 và 32x32)
$offset = 6 + (16 * 2); // 38 bytes

// Entry 16x16
$entry16 = pack('CCCCvvVV', 16, 16, 0, 0, 1, 32, strlen($png16), $offset);
$offset += strlen($png16);

// Entry 32x32
$entry32 = pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($png32), $offset);

$icoData = $icoHeader . $entry16 . $entry32 . $png16 . $png32;
file_put_contents($publicDir . '/favicon.ico', $icoData);
echo "  [OK] Đã tạo {$publicDir}/favicon.ico (Multi-size 16x16 + 32x32)\n";

imagedestroy($img);
echo "Hoàn tất sinh bộ icon thành công!\n";
