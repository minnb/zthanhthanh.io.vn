<?php
/**
 * Thống kê lượt truy cập (số liệu tính sẵn trong Admin\StatsController::report).
 *
 * @var int $days
 * @var list<int> $ranges
 * @var bool|null $missingTables
 * @var string $fromLabel
 * @var string $toLabel
 * @var list<array{label: string, value: int, change: ?int, trend: ?string}> $cards  change = |%|
 * @var array{width: int, max: int, bars: list<array<string, mixed>>, ticks: list<string>} $chart
 * @var list<array{day: string, label: string, views: int, visitors: int}> $daily
 * @var list<array<string, mixed>> $pages
 * @var list<array<string, mixed>> $sources
 */
?>
<header class="admin-head">
  <div>
    <p class="eyebrow">Lượt truy cập website</p>
    <h1 class="h-2">Thống kê</h1>
  </div>
  <nav class="range-links" aria-label="Khoảng thời gian">
    <?php foreach ($ranges as $r): ?>
      <a href="<?= e(url('/admin/stats?days=' . $r)) ?>"<?= $r === $days ? ' aria-current="page"' : '' ?>><?= (int) $r ?> ngày</a>
    <?php endforeach; ?>
  </nav>
</header>

<?php if (!empty($missingTables)): ?>
  <div class="notice notice--error" role="alert">
    Chưa có bảng thống kê trong cơ sở dữ liệu. Chạy file <code>database/migrations/20261015_page_stats.sql</code> rồi tải lại trang.
  </div>
<?php else: ?>
  <p class="small muted">Từ <?= e($fromLabel) ?> đến <?= e($toLabel) ?> (hôm nay). So sánh với <?= (int) $days ?> ngày liền trước.</p>

  <ul class="stat-grid stat-grid--3" aria-label="Tổng quan <?= (int) $days ?> ngày">
    <?php foreach ($cards as $card): ?>
      <li class="stat stat--static">
        <span class="stat__label"><?= e($card['label']) ?></span>
        <span class="stat__value"><?= e(number_format($card['value'], 0, ',', '.')) ?></span>
        <?php if ($card['trend'] === null): ?>
          <span class="xs muted">Kỳ trước chưa có số liệu</span>
        <?php elseif ($card['trend'] === 'up'): ?>
          <span class="xs trend trend--up">▲ <?= (int) $card['change'] ?>% so với kỳ trước</span>
        <?php elseif ($card['trend'] === 'down'): ?>
          <span class="xs trend trend--down">▼ <?= (int) $card['change'] ?>% so với kỳ trước</span>
        <?php else: ?>
          <span class="xs trend trend--flat">Không đổi so với kỳ trước</span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <section class="panel" aria-labelledby="chart-h">
    <div class="panel__title" id="chart-h"><span>Lượt xem theo ngày</span><span class="xs muted">Cao nhất: <?= (int) $chart['max'] ?></span></div>
    <?php if ($chart['max'] === 0): ?>
      <p class="muted small">Chưa có lượt xem nào trong khoảng này. Số liệu bắt đầu được ghi từ khi tính năng thống kê được đưa lên website.</p>
    <?php else: ?>
      <svg class="chart" viewBox="0 0 <?= (int) $chart['width'] ?> 100" preserveAspectRatio="none" role="img" aria-labelledby="chart-h" aria-describedby="chart-table">
        <?php foreach ($chart['bars'] as $bar): ?>
          <rect x="<?= (int) $bar['x'] ?>" y="<?= e((string) $bar['y']) ?>" width="8" height="<?= e((string) $bar['h']) ?>"><title><?= e($bar['label']) ?>: <?= (int) $bar['views'] ?> lượt xem</title></rect>
        <?php endforeach; ?>
      </svg>
      <div class="chart__ticks xs muted" aria-hidden="true">
        <?php foreach ($chart['ticks'] as $tick): ?><span><?= e($tick) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <details class="mt-2">
      <summary class="small">Bảng số liệu từng ngày</summary>
      <table class="data-table" id="chart-table">
        <caption class="sr-only">Lượt xem và người xem từng ngày, mới nhất trước</caption>
        <thead><tr><th scope="col">Ngày</th><th scope="col">Lượt xem</th><th scope="col">Người xem</th></tr></thead>
        <tbody>
          <?php foreach ($daily as $d): ?>
            <tr><th scope="row"><?= e($d['label']) ?></th><td><?= (int) $d['views'] ?></td><td><?= (int) $d['visitors'] ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </details>
  </section>

  <div class="admin-grid">
    <section class="panel" aria-labelledby="pages-h">
      <div class="panel__title" id="pages-h"><span>Trang được xem nhiều</span><span class="xs muted">Lượt xem · người xem</span></div>
      <?php if ($pages === []): ?>
        <p class="muted small">Chưa có số liệu.</p>
      <?php else: ?>
        <ol class="bar-list">
          <?php foreach ($pages as $p): ?>
            <li>
              <div class="bar-list__row">
                <a href="<?= e(url($p['path'])) ?>" target="_blank" rel="noopener"><?= e($p['title']) ?></a>
                <span class="bar-list__num"><strong><?= (int) $p['views'] ?></strong> · <?= (int) $p['visitors'] ?></span>
              </div>
              <span class="xs muted bar-list__path"><?= e($p['path']) ?></span>
              <span class="bar-list__bar" style="--w: <?= (int) $p['share'] ?>%" aria-hidden="true"></span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>

    <section class="panel" aria-labelledby="sources-h">
      <div class="panel__title" id="sources-h"><span>Nguồn truy cập</span><span class="xs muted">Lượt xem · tỉ lệ</span></div>
      <?php if ($sources === []): ?>
        <p class="muted small">Chưa có số liệu.</p>
      <?php else: ?>
        <ol class="bar-list">
          <?php foreach ($sources as $s): ?>
            <li>
              <div class="bar-list__row">
                <span><?= e($s['label']) ?></span>
                <span class="bar-list__num"><strong><?= (int) $s['views'] ?></strong> · <?= (int) $s['percent'] ?>%</span>
              </div>
              <span class="bar-list__bar" style="--w: <?= (int) $s['share'] ?>%" aria-hidden="true"></span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>
  </div>

  <details class="panel">
    <summary class="small"><strong>Cách đếm và giới hạn</strong></summary>
    <ul class="small muted">
      <li>Mỗi lần trang hiển thị trên trình duyệt được tính 1 lượt xem (kể cả trang lấy từ bộ nhớ đệm). Bot và công cụ quét phần lớn không được tính.</li>
      <li>"Người xem" ước lượng theo từng ngày từ mã băm ẩn danh, không lưu IP, không dùng cookie. Cùng một người đổi mạng hoặc trình duyệt sẽ bị tính là người khác; cộng nhiều ngày thì một người có thể được tính nhiều lần.</li>
      <li>Người tắt JavaScript hoặc dùng trình chặn theo dõi không được đếm (thường vài phần trăm).</li>
      <li>"Trực tiếp / không rõ": gõ địa chỉ, bấm từ ứng dụng không gửi nguồn, hoặc dấu trang.</li>
    </ul>
  </details>
<?php endif; ?>
