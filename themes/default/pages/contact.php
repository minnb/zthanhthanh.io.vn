<?php
/**
 * Trang Liên hệ & Pitching (/lien-he): lời ngỏ = nội dung soạn trong admin (pages), bên cạnh là mẫu
 * kết nối pitching và các kênh liên hệ lấy từ Cài đặt.
 * Mẫu pitching KHÔNG gửi lên máy chủ: app.js kiểm tra rồi mở ứng dụng email với nội dung soạn sẵn
 * (không có JS: trình duyệt dùng action mailto:).
 *
 * @var array<string, mixed> $page  Có thêm: html (đã render an toàn)
 * @var list<array{label: string, url?: string}> $breadcrumb
 * @var array<string, mixed> $site
 */
$email = (string) ($site['contactEmail'] ?? '');
$address = (string) ($site['contactAddress'] ?? '');
$social = is_array($site['social'] ?? null) ? $site['social'] : [];
$socialIcons = ['Facebook' => 'fb', 'Threads' => 'threads', 'TikTok' => 'tiktok'];
$topics = ['pitching' => 'Pitching kịch bản', 'speaking' => 'Mời diễn thuyết', 'screening' => 'Chiếu phim & tọa đàm', 'personal' => 'Kết nối cá nhân'];
?>
<header class="container page-head">
  <?= partial('partials/breadcrumb', ['items' => $breadcrumb]) ?>
  <p class="eyebrow">Liên hệ &amp; Pitching</p>
  <h1 class="h-1"><?= e((string) $page['title']) ?></h1>
  <p class="lead">Lời ngỏ gửi các đạo diễn, nhà sản xuất, đồng nghiệp biên kịch và độc giả: mình luôn hào hứng với những dự án điện ảnh – văn hóa giàu giá trị nhân văn.</p>
</header>

<div class="container section section--flush-top contact-grid">
  <article class="letter card" aria-label="Lời ngỏ">
    <div class="prose"><?= $page['html'] /* đã render qua Markdown safeMode */ ?></div>
    <p class="letter__sign">— <?= e((string) ($site['authorName'] ?? '')) ?></p>
  </article>

  <aside class="contact-side" aria-label="Kết nối với Thanh Thanh">
    <?php if ($email !== ''): ?>
      <section class="form-card card" aria-labelledby="form-title">
        <h2 class="h-3" id="form-title">Mẫu kết nối pitching</h2>
        <p class="muted small mt-2">Điền nhanh, bấm gửi: ứng dụng email của bạn sẽ mở sẵn nội dung để gửi tới <?= e($email) ?>. Mình phản hồi trong 24–48 giờ.</p>
        <form class="form" action="mailto:<?= e($email) ?>" method="post" enctype="text/plain" data-mailto="<?= e($email) ?>" novalidate>
          <div class="field">
            <label for="f-name">Họ tên <span class="req" aria-hidden="true">*</span></label>
            <input class="input" id="f-name" name="name" autocomplete="name" required aria-describedby="e-name">
            <p class="error" id="e-name"></p>
          </div>
          <div class="field">
            <label for="f-email">Email của bạn <span class="req" aria-hidden="true">*</span></label>
            <input class="input" id="f-email" name="email" type="email" autocomplete="email" inputmode="email" required aria-describedby="e-email">
            <p class="error" id="e-email"></p>
          </div>
          <fieldset class="field" aria-describedby="e-topic">
            <legend>Chủ đề <span class="req" aria-hidden="true">*</span></legend>
            <div class="topics">
              <?php foreach ($topics as $value => $label): ?>
                <label class="topic"><input type="radio" name="topic" value="<?= e($value) ?>" data-label="<?= e($label) ?>" required><span><?= e($label) ?></span></label>
              <?php endforeach; ?>
            </div>
            <p class="error" id="e-topic"></p>
          </fieldset>
          <div class="field">
            <label for="f-msg">Lời nhắn <span class="req" aria-hidden="true">*</span> <span class="muted">(tối đa 1.500 ký tự)</span></label>
            <?php /* Nội dung đi vào URL mailto: – giới hạn để không vượt độ dài URL của ứng dụng email */ ?>
            <textarea class="input" id="f-msg" name="message" required maxlength="1500" aria-describedby="e-message" placeholder="Logline, thời gian, địa điểm…"></textarea>
            <p class="error" id="e-message"></p>
          </div>
          <button class="btn btn--primary" type="submit">Soạn email gửi lời mời <?= icon('arrow-r', 'i--sm i--move') ?></button>
          <p class="form__note" data-form-status role="status" tabindex="-1" hidden>Ứng dụng email đã được mở với nội dung soạn sẵn. Nếu không thấy, bạn gửi trực tiếp tới <a class="text-accent" href="mailto:<?= e($email) ?>"><?= e($email) ?></a> nhé.</p>
        </form>
      </section>

      <div class="channel-main dark-surface">
        <p class="eyebrow">Email chính thức</p>
        <a class="mail" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        <p class="muted">Phản hồi trong vòng 24–48 giờ</p>
      </div>
    <?php endif; ?>

    <ul class="channel-list card">
      <?php if ($address !== ''): ?>
        <li><div class="channel-static"><span class="ico"><?= icon('pin', '') ?></span><span><strong>Địa chỉ làm việc</strong><small><?= e($address) ?></small></span></div></li>
      <?php endif; ?>
      <?php foreach ($social as $label => $href): ?>
        <li>
          <a href="<?= e(link_url((string) $href)) ?>" target="_blank" rel="noopener">
            <span class="ico"><?= icon($socialIcons[$label] ?? 'link', '') ?></span>
            <span><strong><?= e((string) $label) ?></strong><small><?= e((string) preg_replace('#^https?://(www\.)?#', '', (string) $href)) ?></small></span>
            <?= icon('arrow-ur') ?><span class="sr-only">(mở tab mới)</span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </aside>
</div>
