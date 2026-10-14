<?php
/**
 * Hộp kêu gọi hợp tác chuyên nghiệp dành cho Biên kịch (Collaboration & Pitching Box).
 *
 * @var array<string, mixed> $site
 */
$contactEmail = (string) ($site['contactEmail'] ?? 'doicho147@gmail.com');
$contactAddress = (string) ($site['contactAddress'] ?? 'Thôn Vân Sa, xã Cổ Đô, Hà Nội');
?>
<aside class="collab-box" aria-labelledby="collab-heading">
  <div class="collab-box__badge">MỜI HỢP TÁC · OPEN FOR COLLABORATION</div>
  <h2 class="collab-box__title" id="collab-heading">Đồng hành cùng Biên kịch Thanh Thanh</h2>
  <p class="collab-box__intro">
    Mình luôn trân trọng mọi cơ hội kết nối với các đạo diễn, nhà sản xuất, hãng phim và các tổ chức văn hóa nghệ thuật. Dưới đây là các định hướng hợp tác trọng tâm:
  </p>

  <div class="collab-box__grid">
    <div class="collab-box__item">
      <div class="collab-box__icon">🎬</div>
      <div>
        <h3 class="collab-box__item-title">Kịch bản Phim ngắn & Điện ảnh</h3>
        <p class="collab-box__item-desc">Sáng tác kịch bản gốc (Original Screenplay), phát triển kịch bản chuyển thể, hoặc đồng biên kịch các dự án phim độc lập, web-series.</p>
      </div>
    </div>

    <div class="collab-box__item">
      <div class="collab-box__icon">✍️</div>
      <div>
        <h3 class="collab-box__item-title">Tư vấn Kịch bản (Script Doctoring)</h3>
        <p class="collab-box__item-desc">Cố vấn góc nhìn chân thực về đời sống người khuyết tật, thẩm định tâm lý nhân vật và tối ưu nhịp điệu cảm xúc theo cấu trúc kịch bản 3 hồi.</p>
      </div>
    </div>

    <div class="collab-box__item">
      <div class="collab-box__icon">🎤</div>
      <div>
        <h3 class="collab-box__item-title">Diễn thuyết & Chiếu phim Giao lưu</h3>
        <p class="collab-box__item-desc">Tham gia các buổi tọa đàm truyền cảm hứng sống, chia sẻ hành trình vượt qua giới hạn của chiếc xe lăn và kinh nghiệm tự học biên kịch từ số 0.</p>
      </div>
    </div>
  </div>

  <div class="collab-box__contact-footer">
    <div class="collab-box__info">
      <p class="collab-box__meta">
        <strong>Địa chỉ làm việc:</strong> <?= e($contactAddress) ?>
      </p>
      <p class="collab-box__meta">
        <strong>Email chính thức:</strong> <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
      </p>
    </div>
    <div class="collab-box__cta-row">
      <a class="btn btn--primary" href="mailto:<?= e($contactEmail) ?>?subject=<?= rawurlencode('[Pitching] Đề xuất hợp tác dự án điện ảnh') ?>">
        <span>Gửi email trao đổi dự án</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
      </a>
      <a class="btn btn--outline" href="<?= e(url('/press-kit')) ?>">Tải Press Kit</a>
    </div>
  </div>
</aside>
