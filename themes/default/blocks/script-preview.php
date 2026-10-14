<?php
/**
 * Khối Trích đoạn kịch bản điện ảnh chuẩn mực (Screenplay Reader).
 * Thiết kế theo chuẩn Hollywood Screenplay Format: Scene Heading, Action, Character, Dialogue.
 *
 * @var array<string, mixed> $site
 * @var array<string, mixed>|null $featuredFilm
 */
$contactEmail = (string) ($site['contactEmail'] ?? 'doicho147@gmail.com');
?>
<section class="container section section--line" id="script-excerpt" aria-labelledby="script-heading">
  <div class="section__head">
    <div>
      <p class="eyebrow">TRÍCH ĐOẠN KỊCH BẢN · SCREENPLAY EXCERPT</p>
      <h2 class="h-2 mt-2" id="script-heading">Không gian đọc kịch bản: "ĐỢI"</h2>
    </div>
    <span class="badge badge--script">Định dạng Kịch bản Phim</span>
  </div>

  <p class="lead section__intro">
    Trích đoạn Cảnh 1 phim ngắn <em>ĐỢI</em> – tác phẩm lọt vào Chung kết <strong>Inspiring Asia 2026</strong> tại Manila.
  </p>

  <div class="script-reader">
    <div class="script-reader__header">
      <div class="script-reader__meta">
        <span class="script-tag">DRAFT 3 · COMPETITION CUT</span>
        <span class="script-title">KỊCH BẢN PHIM NGẮN: ĐỢI (WAITING)</span>
      </div>
      <div class="script-reader__author">Tác giả: <strong>Thanh Thanh</strong></div>
    </div>

    <div class="script-sheet" role="region" aria-label="Nội dung kịch bản">
      <div class="script-page-marker">Trang 01 / 08</div>

      <div class="script-scene-heading">
        CẢNH 1: NGOẠI. NGÃ TƯ ĐƯỜNG PHỐ SÀI GÒN - CHIỀU MUỘN
      </div>

      <p class="script-action">
        Tiếng còi xe inh ỏi, khói bụi bốc lên từ dòng người tan tầm. Mặt đường nhựa loang loáng những vệt nước mưa chưa kịp khô.
      </p>

      <p class="script-action">
        Ở góc vỉa hè cao hơn nửa gang tay so với lòng đường: AN (khoảng 30 tuổi, gương mặt thanh tú phảng phất nét mỏi mệt) ngồi bất động trên chiếc xe lăn điện. Ánh mắt cô dán chặt vào vạch sơn kẻ đường dành cho người đi bộ ở phía bên kia.
      </p>

      <p class="script-action">
        Đèn tín hiệu chuyển sang XANH. Dòng người bộ hành ùa qua. An đưa tay nắm lấy cần điều khiển, đẩy nhẹ. Bánh xe lăn chỉ kịp khựng lại ngay mép vỉa hè gồ ghề. Không có ram dốc tiếp cận.
      </p>

      <div class="script-dialogue-block">
        <div class="script-character">AN</div>
        <div class="script-parenthetical">(tự lẩm bẩm, giọng chùng xuống)</div>
        <p class="script-dialogue">
          Lại phải đợi sao...
        </p>
      </div>

      <p class="script-action">
        Một vài người đi đường lướt qua, ánh mắt tò mò nhìn chiếc xe lăn rồi vội vã quay đi. An siết chặt ngón tay vào tay vịn kim loại lạnh toát. Cô ngước nhìn đồng hồ: 17 giờ 45 phút. Cuộc hẹn quan trọng chỉ còn vỏn vẹn 15 phút.
      </p>

      <div class="script-dialogue-block">
        <div class="script-character">AN</div>
        <div class="script-parenthetical">(nhìn thẳng vào ống kính máy quay, mắt ánh lên sự kiên quyết)</div>
        <p class="script-dialogue">
          Nếu mình cứ tiếp tục chờ một bàn tay của ai đó đến cứu... thì mình sẽ mắc kẹt ở ngã tư này suốt đời.
        </p>
      </div>

      <p class="script-action">
        An gạt nút khóa an toàn. Động cơ xe lăn điện rít lên một âm thanh dứt khoát. Cô bắt đầu xoay bánh tìm lối thoát bằng chính đôi tay của mình.
      </p>

      <div class="script-transition">
        CẮT SANG (CUT TO:)
      </div>
    </div>

    <!-- Hộp kêu gọi hợp tác dành cho Đạo diễn & Nhà sản xuất -->
    <div class="script-collab-cta">
      <div class="script-collab-cta__text">
        <h3 class="script-collab-cta__title">Thảo luận về kịch bản đầy đủ hoặc cơ hội hợp tác</h3>
        <p class="script-collab-cta__desc">
          Mình luôn sẵn lòng gửi trọn bộ Full Script, Treatment chi tiết và Pitch Deck dự án cho các đạo diễn, nhà sản xuất và liên hoan phim quan tâm.
        </p>
      </div>
      <div class="script-collab-cta__actions">
        <a class="btn btn--primary" href="mailto:<?= e($contactEmail) ?>?subject=<?= rawurlencode('[Screenplay Request] Yêu cầu kịch bản hoàn chỉnh: ĐỢI') ?>">
          <span>Nhận Full Script qua Email</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        </a>
        <a class="btn btn--outline" href="<?= e(url('/lien-he')) ?>">
          <span>Trò chuyện & Kết nối →</span>
        </a>
      </div>
    </div>
  </div>
</section>
