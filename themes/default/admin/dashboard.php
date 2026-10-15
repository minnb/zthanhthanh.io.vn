<?php
/**
 * Trang Tổng quan (Dashboard) – Chuẩn Vibe Coding:
 * - Stat cards có icon trực quan, phân tầng số liệu rõ ràng
 * - Danh sách bài sửa gần đây thanh lịch, responsive
 * - Cảnh báo SEO dạng badge gọn gàng, hữu ích
 *
 * @var array<string, int> $counts
 * @var list<array<string, mixed>> $recent
 * @var list<array{id: int, title: string, issues: list<string>}> $warnings
 * @var array{today: int, week: int}|null $traffic  null khi chưa có bảng thống kê
 * @var array<string, mixed> $user
 */
$stats = [
    [
        'label' => 'Đã xuất bản',
        'value' => $counts['published'] ?? 0,
        'href'  => '/admin/posts?status=published',
        'type'  => 'published',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>',
    ],
    [
        'label' => 'Hẹn giờ',
        'value' => $counts['scheduled'] ?? 0,
        'href'  => '/admin/posts?status=scheduled',
        'type'  => 'scheduled',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    ],
    [
        'label' => 'Nháp',
        'value' => $counts['draft'] ?? 0,
        'href'  => '/admin/posts?status=draft',
        'type'  => 'draft',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
    ],
    [
        'label' => 'Chờ duyệt',
        'value' => $counts['pending'] ?? 0,
        'href'  => '/admin/posts?status=pending',
        'type'  => 'pending',
        'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    ],
];
?>
<header class="admin-head">
  <div>
    <p class="eyebrow">Không gian làm việc</p>
    <h1 class="h-2">Xin chào, <?= e($user['name']) ?></h1>
  </div>
  <div class="admin-head__meta">
    <form method="post" action="<?= e(url('/admin/posts/new')) ?>" class="inline">
      <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
      <button type="submit" class="btn btn--primary btn--sm">＋ Viết bài mới</button>
    </form>
  </div>
</header>

<?php if (($counts['imported'] ?? 0) > 0): ?>
  <div class="notice" role="alert">
    <span>Có <strong><?= (int) $counts['imported'] ?></strong> bài nhập từ Facebook/AI đang chờ biên tập.</span>
    <a href="<?= e(url('/admin/posts?source=facebook')) ?>" class="text-accent" style="margin-left: 8px; font-weight: 600;">Xem bài từ Facebook →</a>
  </div>
<?php endif; ?>

<ul class="stat-grid" aria-label="Số bài theo trạng thái">
  <?php foreach ($stats as $item): ?>
    <li>
      <a class="stat" href="<?= e(url($item['href'])) ?>">
        <div class="field__row" style="margin-bottom: 0;">
          <span class="stat__label"><?= e($item['label']) ?></span>
          <span class="status status--<?= e($item['type']) ?>" style="padding: 4px; border-radius: 6px;"><?= $item['icon'] ?></span>
        </div>
        <span class="stat__value"><?= (int) $item['value'] ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<?php if (!empty($traffic)): ?>
  <a class="traffic-strip" href="<?= e(url('/admin/stats')) ?>">
    <span>Lượt xem hôm nay <strong><?= (int) $traffic['today'] ?></strong></span>
    <span>7 ngày qua <strong><?= (int) $traffic['week'] ?></strong></span>
    <span class="text-accent">Xem thống kê →</span>
  </a>
<?php endif; ?>

<div class="admin-grid">
  <section class="panel" aria-labelledby="recent-h">
    <div class="panel__title" id="recent-h">
      <span>Sửa gần đây</span>
      <a href="<?= e(url('/admin/posts')) ?>" class="small text-accent" style="font-weight: 500;">Tất cả bài viết →</a>
    </div>
    <?php if ($recent === []): ?>
      <p class="muted small" style="padding: 24px 0; text-align: center;">Chưa có bài nào. Bấm “＋ Viết bài mới” để bắt đầu.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($recent as $p): ?>
          <li class="list__item">
            <a href="<?= e(url('/admin/posts/' . $p['id'])) ?>"><?= e($p['title'] !== '' ? $p['title'] : '(chưa có tiêu đề)') ?></a>
            <span class="status status--<?= e($p['status_key']) ?>"><?= e($p['status_label']) ?></span>
            <span class="xs muted"><?= e(date('d/m H:i', strtotime((string) $p['updated_at']) ?: time())) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="panel" aria-labelledby="seo-h">
    <div class="panel__title" id="seo-h">
      <span>Cảnh báo SEO</span>
      <span class="xs muted"><?= count($warnings) ?> bài cần tối ưu</span>
    </div>
    <?php if ($warnings === []): ?>
      <div style="padding: 20px 0; text-align: center; color: var(--success); font-size: 0.875rem;">
        <span style="display: inline-block; font-size: 1.25rem; margin-bottom: 4px;">✓</span>
        <p style="margin: 0; font-weight: 500;">Các bài đã xuất bản đều chuẩn SEO.</p>
      </div>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($warnings as $w): ?>
          <li class="list__item list__item--stack">
            <a href="<?= e(url('/admin/posts/' . $w['id'])) ?>"><?= e($w['title']) ?></a>
            <ul class="issues">
              <?php foreach ($w['issues'] as $issue): ?>
                <li><?= e($issue) ?></li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
