<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Models\PageStats;

/** Thống kê lượt truy cập (đọc bảng tổng hợp do beacon POST /t ghi). Editor xem được. */
final class StatsController extends AdminController
{
    public const RANGES = [7, 30, 90];
    private const DEFAULT_RANGE = 30;

    public function index(): string
    {
        $this->guard('editor');
        $days = (int) Request::query('days', (string) self::DEFAULT_RANGE);
        if (!in_array($days, self::RANGES, true)) {
            $days = self::DEFAULT_RANGE;
        }
        try {
            $report = self::report($days);
        } catch (\PDOException $e) {
            // Thường do chưa chạy database/migrations/20261015_page_stats.sql trên DB này
            ErrorHandler::log($e);
            $report = ['missingTables' => true, 'days' => $days, 'ranges' => self::RANGES];
        }
        return $this->render('stats', ['title' => 'Thống kê'] + $report);
    }

    /**
     * Dữ liệu đã tính sẵn cho template (template chỉ if/foreach). Dùng chung với /_dev/admin/stats.
     *
     * @return array<string, mixed>
     */
    public static function report(int $days): array
    {
        $today = date('Y-m-d');
        $from = self::shift($today, -($days - 1));
        $prevTo = self::shift($from, -1);
        $prevFrom = self::shift($prevTo, -($days - 1));

        $totals = PageStats::totals($from, $today);
        $prev = PageStats::totals($prevFrom, $prevTo);
        $daily = PageStats::daily($from, $today);
        $pages = PageStats::topPages($from, $today, 30);
        $sources = PageStats::topSources($from, $today, 12);

        return [
            'days' => $days,
            'ranges' => self::RANGES,
            'fromLabel' => date('d/m/Y', (int) strtotime($from)),
            'toLabel' => date('d/m/Y', (int) strtotime($today)),
            'cards' => [
                self::card('Lượt xem trang', $totals['views'], $prev['views']),
                self::card('Người xem (cộng theo ngày)', $totals['visitors'], $prev['visitors']),
                self::card('Lượt xem trung bình/ngày', (int) round($totals['views'] / max(1, $days)), (int) round($prev['views'] / max(1, $days))),
            ],
            'chart' => self::chart($daily),
            'daily' => array_reverse(array_map(static fn (array $d): array => $d + [
                'label' => date('d/m', (int) strtotime($d['day'])),
            ], $daily)),
            'pages' => self::withShare($pages, 'views'),
            'sources' => self::withShare($sources, 'views'),
        ];
    }

    /** Số liệu tóm tắt: lượt xem hôm nay và 7 ngày (ô nhỏ trên Tổng quan). @return array{today: int, week: int} */
    public static function summary(): array
    {
        $today = date('Y-m-d');
        return [
            'today' => PageStats::totals($today, $today)['views'],
            'week' => PageStats::totals(self::shift($today, -6), $today)['views'],
        ];
    }

    /**
     * change = % so với kỳ trước (null nếu kỳ trước = 0), trend = up | down | flat (null nếu không so được).
     *
     * @return array{label: string, value: int, change: ?int, trend: ?string}
     */
    private static function card(string $label, int $value, int $previous): array
    {
        $change = $previous > 0 ? (int) round(($value - $previous) * 100 / $previous) : null;
        return [
            'label' => $label,
            'value' => $value,
            'change' => $change === null ? null : abs($change),
            'trend' => $change === null ? null : ($change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat')),
        ];
    }

    /**
     * Biểu đồ cột SVG: mỗi ngày một cột trong hệ tọa độ viewBox (rộng 10 đơn vị/ngày, cao 100).
     *
     * @param list<array{day: string, views: int, visitors: int}> $daily
     * @return array{width: int, max: int, bars: list<array{x: int, y: float, h: float, label: string, views: int}>, ticks: list<string>}
     */
    private static function chart(array $daily): array
    {
        $max = 0;
        foreach ($daily as $d) {
            $max = max($max, $d['views']);
        }
        $bars = [];
        foreach ($daily as $i => $d) {
            $h = $max > 0 ? round($d['views'] * 100 / $max, 2) : 0.0;
            $bars[] = [
                'x' => $i * 10 + 1,
                'y' => round(100 - $h, 2),
                'h' => $h,
                'label' => date('d/m', (int) strtotime($d['day'])),
                'views' => $d['views'],
            ];
        }
        $n = count($bars);
        $ticks = $n > 0 ? array_values(array_unique([$bars[0]['label'], $bars[intdiv($n - 1, 2)]['label'], $bars[$n - 1]['label']])) : [];
        return ['width' => max(10, $n * 10), 'max' => $max, 'bars' => $bars, 'ticks' => $ticks];
    }

    /**
     * Thêm "share" (% so với dòng lớn nhất, cho thanh ngang) và "percent" (% trên tổng).
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private static function withShare(array $rows, string $key): array
    {
        $max = 0;
        $sum = 0;
        foreach ($rows as $r) {
            $max = max($max, (int) $r[$key]);
            $sum += (int) $r[$key];
        }
        return array_map(static fn (array $r): array => $r + [
            'share' => $max > 0 ? (int) round((int) $r[$key] * 100 / $max) : 0,
            'percent' => $sum > 0 ? (int) round((int) $r[$key] * 100 / $sum) : 0,
        ], $rows);
    }

    private static function shift(string $day, int $offset): string
    {
        return date('Y-m-d', (int) strtotime($day . ' ' . ($offset >= 0 ? '+' : '') . $offset . ' day'));
    }
}
