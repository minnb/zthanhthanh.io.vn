<?php
declare(strict_types=1);

namespace App\Core;

use GdImage;
use RuntimeException;

/**
 * Xử lý ảnh upload bằng GD (security.md + theme-ui.md):
 * - Kiểm MIME bằng finfo (chỉ jpeg/png/webp/gif), giới hạn dung lượng và số điểm ảnh.
 * - Xoay theo EXIF (ảnh chụp điện thoại), giữ nền trong suốt.
 * - Xuất WebP các cỡ 480/960/1440 (không phóng to), tên: <YYYY>/<MM>/<slug>-<hash>-<rộng>.webp
 *   (quy ước dùng chung với Models\Media::url/srcset).
 * Không giữ file gốc: chỉ lưu bản WebP đã sinh lại -> loại bỏ metadata/mã độc nhúng trong ảnh.
 */
final class Image
{
    public const WIDTHS = [480, 960, 1440];
    public const MAX_BYTES = 15 * 1024 * 1024;
    /**
     * ~16MP (vd 4900×3260). Bộ nhớ PHP đo thật (CLI, 12/10/2026, cách xử lý "ảnh gốc -> 1 bản lớn nhất rồi bỏ ảnh gốc"):
     * 12MP ngang 70MB, 12MP ảnh dọc điện thoại 72MB, 16MP xoay 2:3 94MB, 16MP dọc 9:16 100MB. Đây là phần tính vào
     * memory_limit; RAM thật của tiến trình cao hơn (~130MB đo ở cách xử lý cũ) vì libjpeg/libwebp cấp phát riêng.
     */
    public const MAX_PIXELS = 16_000_000;
    /** Byte cho mỗi điểm ảnh NGUỒN: GD truecolor 4 byte + bộ đệm giải mã JPEG. */
    private const SOURCE_BYTES_PER_PIXEL = 5;
    /**
     * Byte cho mỗi điểm ảnh của bản LỚN NHẤT, tồn tại CÙNG LÚC với ảnh gốc (canvas 4 byte + dư). Xoay, làm nét,
     * mã hóa WebP, dò điểm tâm chạy SAU khi đã bỏ ảnh gốc nên không cộng vào đỉnh. Hiệu chỉnh 12/10/2026: 10 -> 6
     * (ước tính vẫn ≥ đo thật ≥ 9MB ở mọi ca – tests/image-check.php kiểm "UNDER-ESTIMATE").
     */
    private const OUTPUT_BYTES_PER_PIXEL = 6;
    /**
     * Giai đoạn 2 (sau khi bỏ ảnh gốc): bản lớn nhất + bộ đệm ARGB của bộ mã hóa WebP (GD cấp bằng emalloc, có tính vào
     * memory_limit) + các cỡ nhỏ hơn. Ảnh ≤ 1440px ngang không cần thu nhỏ -> bản lớn nhất CHÍNH LÀ ảnh gốc, đỉnh rơi
     * vào giai đoạn này. Đo 12/10/2026: 1080×12000 198MB, 1290×8000 122MB, 1170×2532 48MB -> cần ≥ 15.1 byte/điểm ảnh.
     */
    private const OUTPUT_PHASE2_BYTES_PER_PIXEL = 16;
    /** Phần cố định dư an toàn. */
    private const OVERHEAD_BYTES = 10 * 1024 * 1024;
    private const MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const QUALITY = 80;

    /** Các khung hiển thị của site (rộng/cao): ảnh bìa bài, poster phim, ảnh chia sẻ Facebook. */
    public const FRAME_COVER = 16 / 9;
    public const FRAME_POSTER = 2.39;
    public const FRAME_SHARE = 1200 / 630;
    public const SHARE_WIDTH = 1200;
    private const SHARE_QUALITY = 82;
    /**
     * Làm nét nhẹ sau khi thu nhỏ mạnh (lọc diện tích của imagecopyresampled hơi mềm). Tổng kernel = 32 = divisor
     * -> cường độ 0.25. Đo 12/10/2026 (tests/image-check.php): cường độ 0.5 (24/16) làm WebP +14% -> giảm còn 0.25.
     */
    private const SHARPEN_KERNEL = [[-1, -1, -1], [-1, 40, -1], [-1, -1, -1]];
    private const SHARPEN_DIVISOR = 32;
    private const SHARPEN_MIN_RATIO = 2.0;
    /** Dò điểm tâm (theo smartcrop.js): phân tích trên ảnh có cạnh ngắn ≈ 256px, lấy mẫu chấm điểm mỗi 8px. */
    private const ANALYSIS_SIZE = 256;
    private const SCORE_STEP = 8;

    /**
     * Ghi các bản WebP + ảnh chia sẻ <path>-og.jpg; trả điểm tâm tự dò (0–100 %).
     *
     * @return array{path: string, width: int, height: int, size: int, mime: string, focus_x: int, focus_y: int}
     * @throws RuntimeException kèm thông báo tiếng Việt cho người dùng
     */
    public static function storeUpload(string $tmpFile, string $originalName, string $uploadsDir): array
    {
        if (!function_exists('imagewebp')) {
            throw new RuntimeException('Máy chủ chưa hỗ trợ ảnh WebP (cần bật GD).');
        }
        // Web: file phải đến từ upload thật (is_uploaded_file). CLI (test): chỉ cần tồn tại.
        if (!is_file($tmpFile) || (PHP_SAPI !== 'cli' && !is_uploaded_file($tmpFile))) {
            throw new RuntimeException('Không nhận được file upload.');
        }
        $size = (int) filesize($tmpFile);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('Ảnh phải nhỏ hơn ' . (self::MAX_BYTES / 1024 / 1024) . 'MB.');
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmpFile);
        if (!in_array($mime, self::MIMES, true)) {
            throw new RuntimeException('Chỉ nhận ảnh JPG, PNG, WebP hoặc GIF.');
        }
        $info = @getimagesize($tmpFile);
        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            throw new RuntimeException('File không phải ảnh hợp lệ.');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new RuntimeException('Ảnh quá lớn (tối đa khoảng 16 triệu điểm ảnh, vd 4900×3260). Hãy giảm kích thước rồi thử lại.');
        }
        // Xoay/lật theo EXIF SAU khi thu nhỏ và bỏ ảnh gốc: xoay ảnh gốc tạo thêm một bản sao đầy đủ (x2 bộ nhớ)
        ['angle' => $angle, 'flip' => $flip] = self::exifOrientation($tmpFile, $mime);
        // Kiểm trước khi giải mã: thiếu bộ nhớ trong GD là lỗi fatal (500), không bắt được bằng try/catch
        $limit = self::memoryLimitBytes();
        if ($limit > 0 && memory_get_usage(true) + self::estimateMemory($info[0], $info[1], $angle) > $limit) {
            throw new RuntimeException('Ảnh quá lớn so với bộ nhớ máy chủ. Hãy giảm kích thước ảnh (vd còn 3000px chiều ngang) rồi thử lại.');
        }

        $src = self::load($tmpFile, $mime);
        $swap = abs($angle) === 90;
        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);
        // Kích thước sau khi xoay (kích thước hiển thị đúng chiều)
        $width = $swap ? $srcHeight : $srcWidth;
        $height = $swap ? $srcWidth : $srcHeight;
        $maxWidth = min($width, max(self::WIDTHS));

        $base = Slug::make(pathinfo($originalName, PATHINFO_FILENAME), 50);
        $relative = date('Y/m') . '/' . ($base !== '' ? $base : 'anh') . '-' . bin2hex(random_bytes(3));
        $dir = rtrim($uploadsDir, '/\\') . '/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            imagedestroy($src);
            throw new RuntimeException('Không tạo được thư mục lưu ảnh.');
        }

        $written = [];
        $largestSize = 0;
        $maxHeight = (int) max(1, round($height * $maxWidth / $width));
        // Làm nét chỉ cho ảnh chụp (JPEG): ảnh PNG/GIF thường là đồ họa nét sẵn hoặc có nền trong suốt
        $sharpen = $mime === 'image/jpeg';
        try {
            // 1. Từ ảnh gốc chỉ tạo MỘT bản lớn nhất rồi bỏ ảnh gốc ngay. PHP 8: imagedestroy() không giải phóng bộ nhớ,
            //    ảnh GD chỉ được giải phóng khi hết biến tham chiếu -> unset. Đỉnh bộ nhớ = ảnh gốc + 1 bản lớn nhất
            //    (estimateMemory); mọi bước sau (xoay, làm nét, cỡ nhỏ, điểm tâm, ảnh Facebook) chỉ làm trên ảnh ≤ 1440px.
            $base = $maxWidth === $width
                ? $src
                : self::resize($src, $srcWidth, $srcHeight, $swap ? $maxHeight : $maxWidth, $swap ? $maxWidth : $maxHeight);
            unset($src);
            if ($angle !== 0) {
                $base = self::rotate($base, $angle);
            }
            if ($flip !== 0 && !imageflip($base, $flip)) {
                throw new RuntimeException('Không lật được ảnh.');
            }

            // 2. Từng cỡ từ bản lớn nhất (đã đúng chiều). $base giữ nguyên, chưa làm nét, cho bước 3.
            foreach (self::widths($maxWidth) as $w) {
                $h = (int) max(1, round($height * $w / $width));
                $canvas = $w === $maxWidth ? $base : self::resize($base, $maxWidth, $maxHeight, $w, $h);
                if ($sharpen && $width / $w >= self::SHARPEN_MIN_RATIO) {
                    if ($canvas === $base) {
                        $canvas = self::resize($base, $maxWidth, $maxHeight, $maxWidth, $maxHeight); // bản sao, imageconvolution sửa tại chỗ
                    }
                    imageconvolution($canvas, self::SHARPEN_KERNEL, self::SHARPEN_DIVISOR, 0);
                }
                $file = rtrim($uploadsDir, '/\\') . '/' . $relative . '-' . $w . '.webp';
                if (!imagewebp($canvas, $file, self::QUALITY)) {
                    throw new RuntimeException('Không lưu được ảnh WebP.');
                }
                $written[] = $file;
                if ($w === $maxWidth) {
                    $largestSize = (int) filesize($file);
                }
            }
            unset($canvas);

            // 3. Điểm tâm tự động + ảnh chia sẻ Facebook 1200×630 cắt quanh điểm tâm
            $focus = self::detectFocus($base);
            $shareFile = rtrim($uploadsDir, '/\\') . '/' . $relative . '-og.jpg';
            self::writeShareImage($base, $focus['x'], $focus['y'], $shareFile);
            $written[] = $shareFile;
        } catch (RuntimeException $e) {
            foreach ($written as $file) {
                @unlink($file);
            }
            throw $e;
        } finally {
            unset($src, $base, $canvas);
        }

        return [
            'path' => $relative, 'width' => $maxWidth, 'height' => $maxHeight, 'size' => $largestSize, 'mime' => 'image/webp',
            'focus_x' => (int) round($focus['x'] * 100), 'focus_y' => (int) round($focus['y'] * 100),
        ];
    }

    /**
     * Vùng cắt tỉ lệ $ratio LỚN NHẤT nằm trong ảnh W×H, đặt điểm tâm (fx, fy ∈ [0,1]) vào giữa vùng,
     * kẹp trong ảnh (chạm mép thì dừng, không bao giờ lộ khoảng trống). Hàm thuần.
     *
     * @return array{x: float, y: float, w: float, h: float}
     */
    public static function cropRect(int $width, int $height, float $fx, float $fy, float $ratio): array
    {
        if ($width < 1 || $height < 1 || $ratio <= 0) {
            return ['x' => 0.0, 'y' => 0.0, 'w' => (float) max(0, $width), 'h' => (float) max(0, $height)];
        }
        $fx = min(1.0, max(0.0, $fx));
        $fy = min(1.0, max(0.0, $fy));
        if ($width / $height > $ratio) { // ảnh rộng hơn khung -> cắt bớt chiều ngang
            $w = $height * $ratio;
            return ['x' => min(max($fx * $width - $w / 2, 0.0), $width - $w), 'y' => 0.0, 'w' => $w, 'h' => (float) $height];
        }
        $h = $width / $ratio; // ảnh cao hơn khung -> cắt bớt chiều dọc
        return ['x' => 0.0, 'y' => min(max($fy * $height - $h / 2, 0.0), $height - $h), 'w' => (float) $width, 'h' => $h];
    }

    /**
     * Giá trị CSS object-position (vd "50% 23.4%") để khung `object-fit: cover` tỉ lệ $ratio hiện ĐÚNG vùng cropRect().
     * CSS đặt lệch = p × (khung − ảnh) nên p = x0 / (W − w) trùng khớp tuyệt đối.
     */
    public static function framePosition(int $width, int $height, float $fx, float $fy, float $ratio): string
    {
        $r = self::cropRect($width, $height, $fx, $fy, $ratio);
        $px = $width - $r['w'] > 0.5 ? $r['x'] / ($width - $r['w']) * 100 : 50.0;
        $py = $height - $r['h'] > 0.5 ? $r['y'] / ($height - $r['h']) * 100 : 50.0;
        return self::percent($px) . ' ' . self::percent($py);
    }

    /** Kích thước ảnh chia sẻ Facebook sinh ra từ ảnh W×H: 1200×630, hoặc nhỏ hơn nếu ảnh hẹp (không phóng to). @return array{0: int, 1: int} */
    public static function shareSize(int $width, int $height): array
    {
        $r = self::cropRect($width, $height, 0.5, 0.5, self::FRAME_SHARE);
        $w = (int) max(1, min(self::SHARE_WIDTH, floor($r['w'])));
        return [$w, (int) max(1, round($w / self::FRAME_SHARE))];
    }

    /**
     * Tạo lại ảnh Facebook từ bản WebP lớn nhất (khi đổi điểm tâm) – không giữ file gốc nên đây là nguồn tốt nhất còn lại.
     * Ghi chú (chấp nhận, review 12/10/2026): bản này đã nén WebP và đã làm nét nếu ảnh gốc rộng ≥ 2×1440px, nên ảnh og
     * tạo lại hơi nét hơn bản tạo lúc upload (lấy từ ảnh chưa làm nét). Chênh lệch nhỏ, không đáng lưu thêm một bản 1440.
     *
     * @param array<string, mixed> $media
     */
    public static function rebuildShareImage(array $media, string $uploadsDir): void
    {
        $base = rtrim($uploadsDir, '/\\') . '/' . $media['path'];
        $widths = self::widths((int) $media['width']);
        $source = $base . '-' . end($widths) . '.webp';
        $image = is_file($source) ? @imagecreatefromwebp($source) : false;
        if (!$image instanceof GdImage) {
            throw new RuntimeException('Không đọc được ảnh gốc để tạo ảnh chia sẻ.');
        }
        self::writeShareImage($image, ((int) $media['focus_x']) / 100, ((int) $media['focus_y']) / 100, $base . '-og.jpg');
    }

    /** Cắt quanh điểm tâm theo tỉ lệ 1200:630 + thu nhỏ một lần, ghi JPEG (định dạng Facebook đọc tốt nhất). */
    private static function writeShareImage(GdImage $image, float $fx, float $fy, string $file): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $r = self::cropRect($width, $height, $fx, $fy, self::FRAME_SHARE);
        [$outW, $outH] = self::shareSize($width, $height);
        $dst = imagecreatetruecolor($outW, $outH);
        if ($dst === false) {
            throw new RuntimeException('Không đủ bộ nhớ để tạo ảnh chia sẻ.');
        }
        // JPEG không có nền trong suốt: lót trắng trước khi chép (ảnh PNG trong suốt không bị nền đen)
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagealphablending($dst, true);
        imagecopyresampled($dst, $image, 0, 0, (int) round($r['x']), (int) round($r['y']), $outW, $outH, (int) round($r['w']), (int) round($r['h']));
        // Ghi file tạm rồi rename (nguyên tử, cùng thư mục): người xem/Facebook không bao giờ nhận file đang ghi dở,
        // và nếu ghi lỗi thì file og cũ vẫn còn nguyên. rename() của PHP ghi đè file đích cả trên Windows.
        $tmp = $file . '.tmp';
        if (!imagejpeg($dst, $tmp, self::SHARE_QUALITY) || !@rename($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('Không lưu được ảnh chia sẻ.');
        }
    }

    /**
     * Tự tìm điểm tâm (vùng quan trọng) – port rút gọn smartcrop.js (Jonas Wagner, MIT), giữ nguyên trọng số gốc:
     * 1. Thu nhỏ để cạnh ngắn ≈ 256px; mỗi điểm ảnh tính 3 đặc trưng: chi tiết (Laplacian), màu da, độ bão hòa.
     * 2. Gộp khối 8×8 (0.5·trung bình + 0.5·lớn nhất, như downSample của smartcrop).
     * 3. Trượt khung (khung hẹp nhất của site so với ảnh) bước 8px, chấm điểm
     *    (0.2·chi tiết + 1.8·da + 0.1·bão hòa) × importance / diện tích; importance ưu tiên giữa + đường 1/3, phạt sát mép.
     * 4. Điểm tâm = tâm khung điểm cao nhất. Ảnh không có gì nổi bật -> giữa (0.5, 0.5).
     * Khác smartcrop: điểm sát viền ảnh có chi tiết = 0 (smartcrop dùng độ sáng thô ở viền); thêm ứng viên sát mép cuối.
     *
     * @return array{x: float, y: float} toạ độ 0..1
     */
    public static function detectFocus(GdImage $image): array
    {
        $center = ['x' => 0.5, 'y' => 0.5];
        $srcW = imagesx($image);
        $srcH = imagesy($image);
        if ($srcW < 16 || $srcH < 16) {
            return $center;
        }
        $scale = min(max(self::ANALYSIS_SIZE / $srcW, self::ANALYSIS_SIZE / $srcH), 1.0);
        $w = max(16, (int) round($srcW * $scale));
        $h = max(16, (int) round($srcH * $scale));
        $small = $scale < 1.0 ? self::resize($image, $srcW, $srcH, $w, $h) : $image;
        $blocks = self::featureBlocks($small, $w, $h);
        unset($small);
        if ($blocks === null) {
            return $center;
        }

        // Khung hẹp nhất so với ảnh (giữ ít diện tích nhất) -> các khung khác chứa trọn vùng này khi dùng chung điểm tâm
        $imageRatio = $w / $h;
        $ratio = self::FRAME_COVER;
        $minShare = 2.0;
        foreach ([self::FRAME_COVER, self::FRAME_POSTER, self::FRAME_SHARE] as $frame) {
            $share = $imageRatio > $frame ? $frame / $imageRatio : $imageRatio / $frame;
            if ($share < $minShare) {
                $minShare = $share;
                $ratio = $frame;
            }
        }
        $first = self::cropRect($w, $h, 0.0, 0.0, $ratio);
        $cw = $first['w'];
        $ch = $first['h'];
        $horizontal = $w - $cw > 0.5;
        $span = $horizontal ? $w - $cw : $h - $ch;
        $offsets = range(0, (int) floor($span), self::SCORE_STEP);
        if ($span - end($offsets) > 0.5) {
            $offsets[] = $span; // ứng viên sát mép cuối
        }

        $best = null;
        $bestScore = -INF;
        $worstScore = INF;
        foreach ($offsets as $offset) {
            $cx = $horizontal ? (float) $offset : 0.0;
            $cy = $horizontal ? 0.0 : (float) $offset;
            $score = self::scoreCrop($blocks, $cx, $cy, $cw, $ch);
            $worstScore = min($worstScore, $score);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [$cx, $cy];
            }
        }
        // Mọi vị trí gần như bằng điểm (ảnh trơn / đều) -> giữ giữa thay vì chọn đại vị trí đầu
        if ($best === null || $bestScore - $worstScore < 1e-9) {
            return $center;
        }
        return ['x' => ($best[0] + $cw / 2) / $w, 'y' => ($best[1] + $ch / 2) / $h];
    }

    /**
     * Bản đồ đặc trưng gộp khối 8×8. Trả null nếu ảnh không có đặc trưng nào (trơn).
     *
     * @return array{cols: int, rows: int, detail: list<float>, skin: list<float>, sat: list<float>}|null
     */
    private static function featureBlocks(GdImage $img, int $w, int $h): ?array
    {
        $light = [];
        $skin = [];
        $sat = [];
        $skinColor = [0.78, 0.57, 0.44];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($img, $x, $y);
                $r = ($c >> 16) & 255;
                $g = ($c >> 8) & 255;
                $b = $c & 255;
                $l = 0.5126 * $b + 0.7152 * $g + 0.0722 * $r; // công thức "cie" của smartcrop (0..255)
                $light[] = $l;
                $ln = $l / 255;
                // Màu da: khoảng cách màu chuẩn hóa tới skinColor, ngưỡng 0.8, độ sáng 0.2..1
                $mag = sqrt($r * $r + $g * $g + $b * $b);
                $s = 0.0;
                if ($mag > 0) {
                    $d = sqrt(($r / $mag - $skinColor[0]) ** 2 + ($g / $mag - $skinColor[1]) ** 2 + ($b / $mag - $skinColor[2]) ** 2);
                    $sk = 1 - $d;
                    if ($sk > 0.8 && $ln >= 0.2 && $ln <= 1.0) {
                        $s = ($sk - 0.8) / 0.2;
                    }
                }
                $skin[] = $s;
                // Độ bão hòa (HSL), ngưỡng 0.4, độ sáng 0.05..0.9
                $max = max($r, $g, $b) / 255;
                $min = min($r, $g, $b) / 255;
                $st = 0.0;
                if ($max !== $min) {
                    $sum = $max + $min;
                    $satv = $sum / 2 > 0.5 ? ($max - $min) / (2 - $sum) : ($max - $min) / $sum;
                    if ($satv > 0.4 && $ln >= 0.05 && $ln <= 0.9) {
                        $st = ($satv - 0.4) / 0.6;
                    }
                }
                $sat[] = $st;
            }
        }

        $f = self::SCORE_STEP;
        $cols = intdiv($w, $f);
        $rows = intdiv($h, $f);
        if ($cols < 1 || $rows < 1) {
            return null;
        }
        $bDetail = [];
        $bSkin = [];
        $bSat = [];
        $total = 0.0;
        for ($by = 0; $by < $rows; $by++) {
            for ($bx = 0; $bx < $cols; $bx++) {
                $sumD = $sumS = $sumT = 0.0;
                $maxD = $maxS = $maxT = 0.0;
                for ($y = $by * $f; $y < ($by + 1) * $f; $y++) {
                    for ($x = $bx * $f; $x < ($bx + 1) * $f; $x++) {
                        $i = $y * $w + $x;
                        // Chi tiết = Laplacian của độ sáng, kẹp 0..255; viền ảnh = 0
                        $dv = 0.0;
                        if ($x > 0 && $y > 0 && $x < $w - 1 && $y < $h - 1) {
                            $dv = 4 * $light[$i] - $light[$i - $w] - $light[$i + $w] - $light[$i - 1] - $light[$i + 1];
                            $dv = min(255.0, max(0.0, $dv)) / 255;
                        }
                        $sumD += $dv;
                        $sumS += $skin[$i];
                        $sumT += $sat[$i];
                        $maxD = max($maxD, $dv);
                        $maxS = max($maxS, $skin[$i]);
                        $maxT = max($maxT, $sat[$i]);
                    }
                }
                $n = $f * $f;
                $bDetail[] = $sumD / $n * 0.5 + $maxD * 0.5;
                $bSkin[] = $sumS / $n * 0.5 + $maxS * 0.5;
                $bSat[] = $sumT / $n * 0.5 + $maxT * 0.5;
                $total += $sumD + $sumS + $sumT;
            }
        }
        return $total <= 0 ? null : ['cols' => $cols, 'rows' => $rows, 'detail' => $bDetail, 'skin' => $bSkin, 'sat' => $bSat];
    }

    /** @param array{cols: int, rows: int, detail: list<float>, skin: list<float>, sat: list<float>} $blocks */
    private static function scoreCrop(array $blocks, float $cx, float $cy, float $cw, float $ch): float
    {
        $detail = $skin = $sat = 0.0;
        $f = self::SCORE_STEP;
        for ($by = 0, $i = 0; $by < $blocks['rows']; $by++) {
            for ($bx = 0; $bx < $blocks['cols']; $bx++, $i++) {
                $imp = self::importance($cx, $cy, $cw, $ch, (float) ($bx * $f), (float) ($by * $f));
                $d = $blocks['detail'][$i];
                $skin += $blocks['skin'][$i] * ($d + 0.01) * $imp;   // skinBias
                $detail += $d * $imp;
                $sat += $blocks['sat'][$i] * ($d + 0.2) * $imp;      // saturationBias
            }
        }
        return ($detail * 0.2 + $skin * 1.8 + $sat * 0.1) / ($cw * $ch); // detailWeight, skinWeight, saturationWeight
    }

    /** Trọng số vị trí của smartcrop: cao ở giữa, thưởng đường 1/3, phạt sát mép (edgeRadius 0.4, edgeWeight −20), ngoài khung −0.5. */
    private static function importance(float $cx, float $cy, float $cw, float $ch, float $x, float $y): float
    {
        if ($cx > $x || $x >= $cx + $cw || $cy > $y || $y >= $cy + $ch) {
            return -0.5;
        }
        $px = abs(0.5 - ($x - $cx) / $cw) * 2;
        $py = abs(0.5 - ($y - $cy) / $ch) * 2;
        $dx = max($px - 1.0 + 0.4, 0.0);
        $dy = max($py - 1.0 + 0.4, 0.0);
        $d = ($dx * $dx + $dy * $dy) * -20.0;
        $s = 1.41 - sqrt($px * $px + $py * $py);
        $s += max(0.0, $s + $d + 0.5) * 1.2 * (self::thirds($px) + self::thirds($py));
        return $s + $d;
    }

    private static function thirds(float $x): float
    {
        $x = (fmod($x - 1 / 3 + 1.0, 2.0) * 0.5 - 0.5) * 16;
        return max(1.0 - $x * $x, 0.0);
    }

    private static function percent(float $value): string
    {
        $text = rtrim(rtrim(number_format(min(100.0, max(0.0, $value)), 1, '.', ''), '0'), '.');
        return $text . '%';
    }

    /**
     * Các cỡ được sinh cho ảnh có bản lớn nhất rộng $maxWidth: các mốc nhỏ hơn + chính nó.
     * Hàm thuần, Media::srcset dựng lại đúng danh sách này từ cột width.
     *
     * @return list<int>
     */
    public static function widths(int $maxWidth): array
    {
        $list = array_values(array_filter(self::WIDTHS, static fn (int $w): bool => $w < $maxWidth));
        $list[] = $maxWidth;
        return $list;
    }

    /** memory_limit của PHP tính bằng byte; -1 (không giới hạn) -> 0. */
    public static function memoryLimitBytes(): int
    {
        $raw = trim((string) ini_get('memory_limit'));
        if ($raw === '' || $raw === '-1') {
            return 0;
        }
        $value = (int) $raw;
        return match (strtolower(substr($raw, -1))) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    private static function load(string $file, string $mime): GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file),
            'image/png' => @imagecreatefrompng($file),
            'image/webp' => @imagecreatefromwebp($file),
            'image/gif' => @imagecreatefromgif($file),
            default => false,
        };
        if (!$image instanceof GdImage) {
            throw new RuntimeException('Không đọc được ảnh (file hỏng hoặc định dạng không hỗ trợ).');
        }
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);
        return $image;
    }

    private static function resize(GdImage $src, int $sw, int $sh, int $w, int $h): GdImage
    {
        $dst = imagecreatetruecolor($w, $h);
        if ($dst === false) {
            throw new RuntimeException('Không đủ bộ nhớ để xử lý ảnh.');
        }
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);
        return $dst;
    }

    /**
     * Bộ nhớ ước tính để xử lý ảnh = phần cố định + đỉnh của 2 giai đoạn:
     *  1. giải mã: ảnh nguồn + bản lớn nhất (ảnh rộng > 1440px: đỉnh thường ở đây);
     *  2. sau khi bỏ ảnh gốc: bản lớn nhất + bộ đệm WebP + các cỡ nhỏ (ảnh ≤ 1440px ngang, vd ảnh chụp màn hình dài).
     * Luôn ≥ số đo thật (tests/image-check.php kiểm "UNDER-ESTIMATE"). Nơi gọi cộng thêm bộ nhớ đang dùng: CLI ~2MB,
     * request web local ~10MB (/_dev/health). Ở memory_limit 128MB qua web: mọi ảnh ≤ 16MP rộng > 1440px tới tỉ lệ 9:16
     * (kể cả cần xoay) được nhận; ảnh hẹp mà rất dài (> ~7MP, vd chụp cuộn màn hình 1290×8000) bị từ chối KÈM THÔNG BÁO.
     */
    public static function estimateMemory(int $width, int $height, int $angle = 0): int
    {
        if ($width < 1 || $height < 1) {
            return 0;
        }
        $swap = abs($angle) === 90;
        $outWidth = min($swap ? $height : $width, max(self::WIDTHS));
        $outHeight = (int) max(1, round(($swap ? $width : $height) * $outWidth / ($swap ? $height : $width)));
        $out = $outWidth * $outHeight;
        $decodePhase = $width * $height * self::SOURCE_BYTES_PER_PIXEL + $out * self::OUTPUT_BYTES_PER_PIXEL;
        $encodePhase = $out * self::OUTPUT_PHASE2_BYTES_PER_PIXEL;
        return max($decodePhase, $encodePhase) + self::OVERHEAD_BYTES;
    }

    /**
     * Phép biến đổi theo EXIF Orientation (ảnh chụp điện thoại). angle theo imagerotate (dương = ngược chiều kim đồng hồ),
     * flip là hằng IMG_FLIP_* áp SAU khi xoay (0 = không lật). 2/4/5/7 là ảnh lật gương (camera trước).
     *
     * @return array{angle: int, flip: int}
     */
    private static function exifOrientation(string $file, string $mime): array
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return ['angle' => 0, 'flip' => 0];
        }
        $exif = @exif_read_data($file);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        return match ($orientation) {
            2 => ['angle' => 0, 'flip' => IMG_FLIP_HORIZONTAL],
            3 => ['angle' => 180, 'flip' => 0],
            4 => ['angle' => 0, 'flip' => IMG_FLIP_VERTICAL],
            5 => ['angle' => -90, 'flip' => IMG_FLIP_HORIZONTAL],
            6 => ['angle' => -90, 'flip' => 0],
            7 => ['angle' => 90, 'flip' => IMG_FLIP_HORIZONTAL],
            8 => ['angle' => 90, 'flip' => 0],
            default => ['angle' => 0, 'flip' => 0],
        };
    }

    /** Xoay ảnh (bản đã thu nhỏ). Trả ảnh MỚI; ảnh vào do nơi gọi tự giải phóng. */
    private static function rotate(GdImage $image, int $angle): GdImage
    {
        $rotated = imagerotate($image, $angle, 0);
        if ($rotated === false) {
            throw new RuntimeException('Không đủ bộ nhớ để xoay ảnh.');
        }
        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);
        return $rotated;
    }
}
