<?php
declare(strict_types=1);

/*
 * Kiểm thử hồi quy xử lý ảnh (Core\Image), không cần DB hay web server:
 *   php src/thanhthanh/tests/image-check.php
 *
 * 1. Chiều xoay/lật theo EXIF Orientation 1–8: ảnh gốc có ô ĐỎ ở góc trên-trái và ô XANH ở góc trên-phải;
 *    mọi cỡ WebP sinh ra phải có 2 ô ở đúng góc. Thử cả ảnh lớn (có thu nhỏ) và ảnh nhỏ (không thu nhỏ).
 * 2. Bộ nhớ ở memory_limit của hosting yếu: mỗi ảnh chạy trong tiến trình con với các mức 96M..128M.
 *    Kết quả chỉ được là "OK" hoặc "từ chối có thông báo" – KHÔNG BAO GIỜ là lỗi fatal (500).
 *    Khi nhận ảnh, bộ nhớ đỉnh thật phải ≤ ước tính của Image::estimateMemory.
 *    Ở 128M, mọi ảnh ≤ 16MP tỉ lệ thường (tới 9:16) phải được nhận.
 * File tạm ghi vào thư mục temp của hệ thống và bị xóa khi xong.
 * Exit code: 0 = tất cả đạt, 1 = có lỗi.
 */

if (PHP_SAPI !== 'cli') {
    exit("Chỉ chạy bằng CLI.\n");
}

require __DIR__ . '/../app/Core/Slug.php';
require __DIR__ . '/../app/Core/Image.php';

use App\Core\Image;

// Tiến trình con: xử lý một ảnh, in kết quả một dòng
if (($argv[1] ?? '') === '--worker') {
    // Cùng công thức Image::storeUpload dùng để kiểm trước khi giải mã (bộ nhớ đang dùng + ước tính)
    $size = @getimagesize((string) $argv[2]);
    $estimate = is_array($size) ? memory_get_usage(true) + Image::estimateMemory($size[0], $size[1], (int) ($argv[4] ?? 0)) : 0;
    try {
        $r = Image::storeUpload((string) $argv[2], 'check.jpg', (string) $argv[3]);
        $peak = memory_get_peak_usage(true);
        printf("OK peak=%.1fMB est=%.1fMB %dx%d%s\n", $peak / 1048576, $estimate / 1048576, $r['width'], $r['height'], $peak > $estimate ? ' UNDER-ESTIMATE' : '');
    } catch (RuntimeException $e) {
        echo 'REJECT ' . $e->getMessage() . "\n";
    }
    exit(0);
}

// Tiến trình con: đo detectFocus trong tiến trình SẠCH (giống một request upload). Đo trong tiến trình chính sau
// nhiều lần storeUpload chậm ~2 lần (đã loại trừ rò rỉ bộ nhớ, GC, hạ xung CPU – nguyên nhân sâu chưa rõ) -> sai lệch.
if (($argv[1] ?? '') === '--focus-timing') {
    [$w, $h, $kind, $bx, $by] = [(int) $argv[2], (int) $argv[3], (string) $argv[4], (float) $argv[5], (float) $argv[6]];
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 70, 90, 110));
    $rad = (int) (min($w, $h) * 0.12);
    $cx = (int) ($bx * $w);
    $cy = (int) ($by * $h);
    if ($kind === 'da') {
        imagefilledellipse($im, $cx, $cy, $rad * 2, (int) ($rad * 2.6), imagecolorallocate($im, 224, 172, 140));
    } elseif ($kind === 'chitiet') {
        for ($i = -$rad; $i < $rad; $i += 4) {
            imageline($im, $cx + $i, $cy - $rad, $cx + $i, $cy + $rad, imagecolorallocate($im, 250, 250, 250));
        }
    }
    $t = microtime(true);
    $f = Image::detectFocus($im);
    echo json_encode(['x' => $f['x'], 'y' => $f['y'], 'ms' => (microtime(true) - $t) * 1000, 'cx' => $cx, 'cy' => $cy]);
    exit(0);
}

if (!function_exists('imagewebp') || !function_exists('exif_read_data')) {
    fwrite(STDERR, "Cần bật extension gd và exif.\n");
    exit(1);
}
ini_set('memory_limit', '-1'); // chỉ tiến trình chính (tạo ảnh thử); tiến trình con đặt giới hạn riêng

$tmp = sys_get_temp_dir() . '/tt-image-check-' . bin2hex(random_bytes(4));
if (!mkdir($tmp, 0775, true)) {
    fwrite(STDERR, "Không tạo được thư mục tạm $tmp\n");
    exit(1);
}
$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok, string $detail = '') use (&$pass, &$fail): void {
    $ok ? $pass++ : $fail++;
    echo ($ok ? '  PASS  ' : '  FAIL  ') . $name . ($ok || $detail === '' ? '' : "  ($detail)") . "\n";
};

/** JPEG từ ảnh GD, chèn segment EXIF (APP1) chỉ chứa thẻ Orientation khi $orientation > 1. */
$writeJpeg = static function (GdImage $im, int $orientation, string $file): void {
    ob_start();
    imagejpeg($im, null, 88);
    $jpg = (string) ob_get_clean();
    if ($orientation > 1) {
        // TIFF big-endian, 1 IFD, 1 thẻ 0x0112 (Orientation) kiểu SHORT
        $tiff = "MM\x00\x2A\x00\x00\x00\x08" . "\x00\x01" . "\x01\x12\x00\x03\x00\x00\x00\x01" . pack('n', $orientation) . "\x00\x00" . "\x00\x00\x00\x00";
        $app1 = "Exif\x00\x00" . $tiff;
        $jpg = "\xFF\xD8\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpg, 2);
    }
    file_put_contents($file, $jpg);
};

$rmTree = static function (string $dir) use (&$rmTree): void {
    foreach (glob($dir . '/*') ?: [] as $path) {
        is_dir($path) ? $rmTree($path) : @unlink($path);
    }
    @rmdir($dir);
};

// ---------- 1. Chiều xoay/lật ----------
echo "== Chiều xoay/lật theo EXIF ==\n";
// Góc chứa ô đỏ / ô xanh sau khi hiển thị đúng chiều (ảnh lưu: đỏ trên-trái, xanh trên-phải)
$expect = [
    1 => ['TL', 'TR'], 2 => ['TR', 'TL'], 3 => ['BR', 'BL'], 4 => ['BL', 'BR'],
    5 => ['TL', 'BL'], 6 => ['TR', 'BR'], 7 => ['BR', 'TR'], 8 => ['BL', 'TL'],
];
foreach ([[3000, 2000, 400], [1200, 800, 100]] as [$w, $h, $box]) {
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
    imagefilledrectangle($im, 0, 0, $box - 1, $box - 1, imagecolorallocate($im, 255, 0, 0));
    imagefilledrectangle($im, $w - $box, 0, $w - 1, $box - 1, imagecolorallocate($im, 0, 0, 255));
    foreach ($expect as $o => [$wantRed, $wantBlue]) {
        $file = "$tmp/o$o-{$w}.jpg";
        $writeJpeg($im, $o, $file);
        try {
            $r = Image::storeUpload($file, "o$o.jpg", $tmp);
        } catch (RuntimeException $e) {
            $check("{$w}x{$h} o=$o", false, $e->getMessage());
            continue;
        }
        $problems = [];
        foreach (Image::widths($r['width']) as $size) {
            $out = imagecreatefromwebp("$tmp/{$r['path']}-$size.webp");
            if (!$out instanceof GdImage) {
                $problems[] = "cỡ $size không đọc được";
                continue;
            }
            $W = imagesx($out);
            $H = imagesy($out);
            $portrait = in_array($o, [5, 6, 7, 8], true);
            if (($W < $H) !== $portrait) {
                $problems[] = "cỡ $size sai chiều {$W}x{$H}";
            }
            $m = 3;
            $found = ['red' => [], 'blue' => []];
            foreach (['TL' => [$m, $m], 'TR' => [$W - 1 - $m, $m], 'BL' => [$m, $H - 1 - $m], 'BR' => [$W - 1 - $m, $H - 1 - $m]] as $corner => [$x, $y]) {
                $c = imagecolorat($out, $x, $y);
                [$rr, $gg, $bb] = [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
                if ($rr > 200 && $gg < 80 && $bb < 80) {
                    $found['red'][] = $corner;
                } elseif ($bb > 200 && $rr < 80 && $gg < 80) {
                    $found['blue'][] = $corner;
                }
            }
            if ($found['red'] !== [$wantRed] || $found['blue'] !== [$wantBlue]) {
                $problems[] = "cỡ $size: đỏ [" . implode(',', $found['red']) . '] xanh [' . implode(',', $found['blue']) . "], cần đỏ $wantRed xanh $wantBlue";
            }
        }
        $check("{$w}x{$h} o=$o (" . implode('/', Image::widths($r['width'])) . ')', $problems === [], implode('; ', $problems));
    }
}

// ---------- 1b. Cắt theo khung quanh điểm tâm (công thức thuần) ----------
echo "== Cắt theo khung (cropRect / framePosition) ==\n";
$near = static fn (float $a, float $b): bool => abs($a - $b) < 0.01;
foreach ([
    // [W, H, fx, fy, ratio, x, y, w, h, position]
    [1600, 900, 0.5, 0.5, 16 / 9, 0, 0, 1600, 900, '50% 50%'],          // đúng tỉ lệ khung: không cắt
    [1440, 1080, 0.5, 0.5, 2.39, 0, 238.75, 1440, 602.51, '50% 50%'],  // giữa
    [1440, 1080, 0.5, 0.0, 2.39, 0, 0, 1440, 602.51, '50% 0%'],        // điểm tâm sát trên -> kẹp mép trên
    [1440, 1080, 0.5, 1.0, 2.39, 0, 477.49, 1440, 602.51, '50% 100%'], // sát dưới -> kẹp mép dưới
    [1440, 1080, 0.5, 0.3, 2.39, 0, 22.75, 1440, 602.51, '50% 4.8%'],  // 0.3·1080 − 301.26 = 22.75
    [1440, 1920, 0.5, 0.25, 16 / 9, 0, 75, 1440, 810, '50% 6.8%'],     // ảnh dọc
    [1440, 400, 0.9, 0.5, 2.39, 484, 0, 956, 400, '100% 50%'],         // ảnh toàn cảnh, điểm tâm bên phải -> kẹp mép phải
    [1440, 400, 0.4, 0.5, 2.39, 98, 0, 956, 400, '20.2% 50%'],         // 0.4·1440 − 478 = 98
] as [$W, $H, $fx, $fy, $R, $ex, $ey, $ew, $eh, $epos]) {
    $r = Image::cropRect($W, $H, $fx, $fy, $R);
    $pos = Image::framePosition($W, $H, $fx, $fy, $R);
    $inside = $r['x'] >= 0 && $r['y'] >= 0 && $r['x'] + $r['w'] <= $W + 0.01 && $r['y'] + $r['h'] <= $H + 0.01;
    $ok = $near($r['x'], $ex) && $near($r['y'], $ey) && $near($r['w'], $ew) && $near($r['h'], $eh) && $pos === $epos && $inside;
    $check(sprintf('%dx%d tâm(%.1f,%.1f) khung %.2f -> %s', $W, $H, $fx, $fy, $R, $pos), $ok,
        sprintf('được x=%.2f y=%.2f w=%.2f h=%.2f pos=%s', $r['x'], $r['y'], $r['w'], $r['h'], $pos));
}
$check('shareSize: ảnh 1440 rộng -> 1200×630', Image::shareSize(1440, 1080) === [1200, 630]);
$check('shareSize: ảnh hẹp 800×600 -> 800×420 (không phóng to)', Image::shareSize(800, 600) === [800, 420], implode('x', Image::shareSize(800, 600)));

// ---------- 1c. Tự dò điểm tâm (smartcrop-lite) ----------
echo "== Tự dò điểm tâm (mỗi ca một tiến trình con sạch, giống một request upload) ==\n";
foreach ([
    // [rộng, cao, loại khối, tâm khối x, tâm khối y] – ảnh cỡ bản lớn nhất (≤ 1440) như lúc gọi thật
    [1440, 1080, 'da', 0.5, 0.2], [1440, 1080, 'da', 0.5, 0.8], [1080, 1440, 'da', 0.5, 0.25],
    [1080, 1440, 'chitiet', 0.5, 0.7], [1440, 480, 'da', 0.15, 0.5], [1440, 480, 'chitiet', 0.85, 0.5],
    [1440, 1080, 'tron', 0.5, 0.5],
] as [$w, $h, $kind, $bx, $by]) {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . " --focus-timing $w $h $kind $bx $by";
    $res = json_decode((string) shell_exec($cmd), true);
    if (!is_array($res)) {
        $check("{$w}x{$h} khối $kind: tiến trình con không trả kết quả", false);
        continue;
    }
    $f = ['x' => (float) $res['x'], 'y' => (float) $res['y']];
    $ms = (float) $res['ms'];
    $cx = (int) $res['cx'];
    $cy = (int) $res['cy'];
    if ($kind === 'tron') {
        $ok = $f === ['x' => 0.5, 'y' => 0.5];
    } else {
        // Khung hẹp nhất (poster 2.39) đặt theo điểm tâm phải chứa tâm khối
        $r = Image::cropRect($w, $h, $f['x'], $f['y'], Image::FRAME_POSTER);
        $ok = $cx >= $r['x'] && $cx <= $r['x'] + $r['w'] && $cy >= $r['y'] && $cy <= $r['y'] + $r['h'];
    }
    $ok = $ok && $ms < 300;
    $check(sprintf('%dx%d khối %s (%.2f,%.2f) -> tâm (%.2f,%.2f) %dms', $w, $h, $kind, $bx, $by, $f['x'], $f['y'], (int) $ms), $ok);
}

// ---------- 1d. Ảnh chia sẻ Facebook + làm nét ----------
echo "== Ảnh chia sẻ Facebook (-og.jpg) + làm nét ==\n";
// Ảnh 4:3 có "mặt" màu da ở 1/5 trên: điểm tâm tự dò phải ở nửa trên, ảnh og 1200×630 phải chứa vùng da
$im = imagecreatetruecolor(4000, 3000);
imagefill($im, 0, 0, imagecolorallocate($im, 70, 90, 110));
imagefilledellipse($im, 2000, 600, 700, 900, imagecolorallocate($im, 224, 172, 140));
for ($i = 0; $i < 3000; $i += 6) { // chi tiết rải đều nửa dưới (không được kéo điểm tâm xuống)
    imageline($im, 0, 1800 + intdiv($i, 3), 4000, 1800 + intdiv($i, 3), imagecolorallocate($im, 80 + $i % 40, 95, 115));
}
$writeJpeg($im, 1, "$tmp/og-src.jpg");
$r = Image::storeUpload("$tmp/og-src.jpg", 'og-src.jpg', $tmp);
$og = "$tmp/{$r['path']}-og.jpg";
$size = is_file($og) ? getimagesize($og) : false;
$check('storeUpload tạo -og.jpg đúng 1200×630', $size !== false && $size[0] === 1200 && $size[1] === 630, $size ? $size[0] . 'x' . $size[1] : 'không có file');
$check("Điểm tâm tự dò ở nửa trên (mặt ở 20%): focus_y={$r['focus_y']}", $r['focus_y'] < 50);
$skinPixels = 0;
if ($size !== false) {
    $o = imagecreatefromjpeg($og);
    for ($y = 0; $y < 630; $y += 10) {
        for ($x = 0; $x < 1200; $x += 10) {
            $c = imagecolorat($o, $x, $y);
            if (abs((($c >> 16) & 255) - 224) < 20 && abs((($c >> 8) & 255) - 172) < 20) {
                $skinPixels++;
            }
        }
    }
}
$check("Ảnh og chứa vùng mặt ($skinPixels điểm mẫu màu da)", $skinPixels > 50);
// Ảnh hẹp: không phóng to
$small = imagecreatetruecolor(800, 600);
imagefill($small, 0, 0, imagecolorallocate($small, 200, 200, 200));
$writeJpeg($small, 1, "$tmp/og-small.jpg");
$r2 = Image::storeUpload("$tmp/og-small.jpg", 'og-small.jpg', $tmp);
$s2 = getimagesize("$tmp/{$r2['path']}-og.jpg");
$check('Ảnh nguồn 800px -> og 800×420 (không phóng to)', $s2 !== false && $s2[0] === 800 && $s2[1] === 420, $s2 ? $s2[0] . 'x' . $s2[1] : '');
// Tạo lại ảnh og (đổi điểm tâm): ghi file tạm rồi rename -> nội dung đổi, không sót file .tmp
$before = md5_file($og);
Image::rebuildShareImage(['path' => $r['path'], 'width' => $r['width'], 'height' => $r['height'], 'focus_x' => 50, 'focus_y' => 100], $tmp);
$check('rebuildShareImage: ảnh og đổi theo điểm tâm mới, không sót file .tmp',
    is_file($og) && md5_file($og) !== $before && !is_file($og . '.tmp') && getimagesize($og)[0] === 1200);

// Làm nét: cùng điểm ảnh, nguồn JPEG (có làm nét) so với PNG (không làm nét)
$photo = imagecreatetruecolor(3000, 2000);
for ($k = 0; $k < 400; $k++) {
    imagefilledellipse($photo, random_int(0, 3000), random_int(0, 2000), random_int(40, 500), random_int(40, 500),
        imagecolorallocate($photo, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
}
$writeJpeg($photo, 1, "$tmp/sharp.jpg");
$decoded = imagecreatefromjpeg("$tmp/sharp.jpg");
imagepng($decoded, "$tmp/sharp.png");
$rj = Image::storeUpload("$tmp/sharp.jpg", 'sharp.jpg', $tmp);
$rp = Image::storeUpload("$tmp/sharp.png", 'sharp.png', $tmp);
$edge = static function (string $file): float { // độ nét = trung bình |Laplacian| của độ sáng
    $g = imagecreatefromwebp($file);
    $l = static function (int $x, int $y) use ($g): float { $c = imagecolorat($g, $x, $y); return 0.299 * (($c >> 16) & 255) + 0.587 * (($c >> 8) & 255) + 0.114 * ($c & 255); };
    $sum = 0.0; $n = 0;
    for ($y = 1; $y < imagesy($g) - 1; $y += 2) {
        for ($x = 1; $x < imagesx($g) - 1; $x += 2) {
            $sum += abs(4 * $l($x, $y) - $l($x - 1, $y) - $l($x + 1, $y) - $l($x, $y - 1) - $l($x, $y + 1));
            $n++;
        }
    }
    return $sum / max(1, $n);
};
$fj = "$tmp/{$rj['path']}-480.webp";
$fp = "$tmp/{$rp['path']}-480.webp";
$sharpJ = $edge($fj);
$sharpP = $edge($fp);
$sizeRatio = filesize($fj) / max(1, filesize($fp));
$check(sprintf('Làm nét bản 480: độ nét %.2f -> %.2f (+%d%%)', $sharpP, $sharpJ, (int) round(($sharpJ / max(0.01, $sharpP) - 1) * 100)), $sharpJ > $sharpP);
$check(sprintf('Làm nét không tăng dung lượng WebP quá 10%% (%+.1f%%)', ($sizeRatio - 1) * 100), $sizeRatio <= 1.10);
if (getenv('IMAGE_CHECK_KEEP')) { // so sánh bằng mắt: ghép 2 bản 480 cạnh nhau
    $a = imagecreatefromwebp($fp);
    $b = imagecreatefromwebp($fj);
    $pair = imagecreatetruecolor(imagesx($a) * 2 + 10, imagesy($a));
    imagecopy($pair, $a, 0, 0, 0, 0, imagesx($a), imagesy($a));
    imagecopy($pair, $b, imagesx($a) + 10, 0, 0, 0, imagesx($b), imagesy($b));
    imagepng($pair, (string) getenv('IMAGE_CHECK_KEEP'));
    echo '  (ảnh so sánh trái=không làm nét, phải=làm nét: ' . getenv('IMAGE_CHECK_KEEP') . ")\n";
}

// ---------- 2. Bộ nhớ ----------
echo "== Bộ nhớ ở memory_limit hosting yếu (tiến trình con) ==\n";
// orientation EXIF 1–8 -> góc xoay (khớp Image::exifOrientation; phần lật không ảnh hưởng ước tính bộ nhớ)
$angles = [1 => 0, 2 => 0, 3 => 180, 4 => 0, 5 => -90, 6 => -90, 7 => 90, 8 => 90];
$cases = [ // [rộng, cao, orientation, tên, kết quả phải có ở 128M]
    [4032, 3024, 1, '12MP ngang', 'OK'],
    [4032, 3024, 6, '12MP dọc điện thoại (xoay)', 'OK'],
    [4896, 3264, 1, '16MP ngang', 'OK'],
    [4896, 3264, 6, '16MP xoay 2:3', 'OK'],
    [4600, 3456, 5, '15,9MP xoay + lật', 'OK'],
    [3000, 5333, 1, '16MP dọc 9:16 không EXIF', 'OK'],
    [4624, 3468, 6, '>16MP (phải từ chối)', 'REJECT'],
    // Ảnh ≤ 1440px ngang: không thu nhỏ, đỉnh bộ nhớ rơi vào giai đoạn mã hóa (review 12/10/2026)
    [1170, 2532, 1, 'Chụp màn hình iPhone', 'OK'],
    [1290, 8000, 1, 'Chụp cuộn màn hình 1290x8000', 'REJECT'],
    [1080, 12000, 1, 'Ảnh rất dài 1080x12000', 'REJECT'],
];
foreach ($cases as [$w, $h, $o, $name, $expect128]) {
    $im = imagecreatetruecolor($w, $h);
    for ($i = 0; $i < 300; $i++) { // nội dung có chi tiết để thời gian mã hóa gần ảnh thật
        $col = imagecolorallocate($im, random_int(0, 255), random_int(0, 255), random_int(0, 255));
        imagefilledellipse($im, random_int(0, $w), random_int(0, $h), random_int(50, 900), random_int(50, 900), $col);
    }
    $file = "$tmp/mem-{$w}x{$h}-o$o.jpg";
    $writeJpeg($im, $o, $file);
    imagedestroy($im);
    // 256M: không phải giới hạn hosting, chỉ để ảnh bị từ chối ở ≤ 128M cũng được kiểm "ước tính ≥ đo thật"
    foreach (['96M', '104M', '112M', '120M', '128M', '256M'] as $limit) {
        $cmd = escapeshellarg(PHP_BINARY) . ' -d memory_limit=' . $limit . ' -d display_errors=1 -d log_errors=0 '
            . escapeshellarg(__FILE__) . ' --worker ' . escapeshellarg($file) . ' ' . escapeshellarg($tmp) . ' ' . $angles[$o];
        $out = trim((string) shell_exec($cmd));
        // Nhận ảnh thì bộ nhớ thật phải ≤ ước tính (nếu không, ở memory_limit thấp hơn sẽ lọt kiểm rồi lỗi 500)
        $ok = (str_starts_with($out, 'OK ') && !str_contains($out, 'UNDER-ESTIMATE')) || str_starts_with($out, 'REJECT ');
        if ($limit === '128M') {
            $ok = $ok && str_starts_with($out, $expect128 . ' ');
        }
        $check(sprintf('%-28s %-5s %s', $name, $limit, strtok($out, "\n") ?: '(không có output)'), $ok, $ok ? '' : $out);
    }
}

$rmTree($tmp);
echo "\nKẾT QUẢ: $pass đạt, $fail lỗi\n";
exit($fail === 0 ? 0 : 1);
