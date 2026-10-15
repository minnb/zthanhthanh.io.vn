<?php
/**
 * Trình đọc kịch bản (Góc sáng tác): trang kịch bản chuẩn Hollywood (Scene Heading, Action, Character,
 * Parenthetical, Dialogue, Transition) + chỉnh cỡ chữ (app.js) + thông tin phim + mốc làm phim.
 *
 * Dùng ở Góc sáng tác (inStudio = true, tiêu đề ẩn vì trang đã có tab) và có thể bật làm khối trang chủ
 * (home_blocks, HomeController nạp thêm studio.css) – khi đó tự bọc container và hiện tiêu đề.
 *
 * @var array<string, mixed> $site
 * @var array<string, mixed>|null $featuredFilm
 * @var list<array<string, mixed>> $milestones
 * @var bool|null $inStudio
 */
$contactEmail = (string) ($site['contactEmail'] ?? '');
$inStudio = !empty($inStudio);
?>
<?php if (!$inStudio): ?><div class="container section"><?php endif; ?>
<section class="studio-panel" id="panel-script" aria-labelledby="script-heading">
  <?php if ($inStudio): ?>
    <h2 class="sr-only" id="script-heading">Trích đoạn kịch bản phim ngắn ĐỢI</h2>
  <?php else: ?>
    <div class="section-head">
      <div class="section-head__text">
        <p class="eyebrow">Góc sáng tác</p>
        <h2 class="h-2" id="script-heading">Trích đoạn kịch bản “ĐỢI”</h2>
      </div>
      <a class="text-link" href="<?= e(url('/goc-sang-tac')) ?>">Vào Góc sáng tác <?= icon('arrow-r') ?></a>
    </div>
  <?php endif; ?>
  <div class="studio-grid">
    <div>
      <div class="script-toolbar">
        <p class="btn-row"><span class="badge badge--neutral label" lang="en">Draft 3 · Competition cut</span><span class="badge label">Trích đoạn Cảnh 1</span></p>
        <div class="font-ctrl card" role="group" aria-label="Cỡ chữ kịch bản" data-font-ctrl="script-sheet" hidden>
          <button type="button" data-font="-1" aria-label="Giảm cỡ chữ">A−</button>
          <output aria-live="polite">15px</output>
          <button type="button" data-font="1" aria-label="Tăng cỡ chữ">A+</button>
        </div>
      </div>

      <div class="script-sheet" id="script-sheet" role="region" aria-label="Nội dung kịch bản">
        <span class="sc-page">1.</span>
        <p>MỞ CẢNH <span lang="en">(FADE IN:)</span></p>
        <p class="sc-heading"><span>1</span><span>NGOẠI. NGÃ TƯ ĐƯỜNG PHỐ SÀI GÒN – CHIỀU MUỘN</span><span>1</span></p>
        <p class="sc-action">Tiếng còi xe inh ỏi, khói bụi bốc lên từ dòng người tan tầm. Mặt đường nhựa loang loáng những vệt nước mưa chưa kịp khô.</p>
        <p class="sc-action">Ở góc vỉa hè cao hơn nửa gang tay so với lòng đường: AN (khoảng 30 tuổi, gương mặt thanh tú phảng phất nét mỏi mệt) ngồi bất động trên chiếc xe lăn điện. Ánh mắt cô dán chặt vào vạch sơn kẻ đường dành cho người đi bộ ở phía bên kia.</p>
        <p class="sc-action">Đèn tín hiệu chuyển sang XANH. Dòng người bộ hành ùa qua. An đưa tay nắm lấy cần điều khiển, đẩy nhẹ. Bánh xe lăn chỉ kịp khựng lại ngay mép vỉa hè gồ ghề. Không có ram dốc tiếp cận.</p>
        <div class="sc-dialogue">
          <p class="sc-char">An</p>
          <p class="sc-paren">(tự lẩm bẩm, giọng chùng xuống)</p>
          <p class="sc-line">Lại phải đợi sao...</p>
        </div>
        <p class="sc-action">Một vài người đi đường lướt qua, ánh mắt tò mò nhìn chiếc xe lăn rồi vội vã quay đi. An siết chặt ngón tay vào tay vịn kim loại lạnh toát. Cô ngước nhìn đồng hồ: 17 giờ 45 phút. Cuộc hẹn quan trọng chỉ còn vỏn vẹn 15 phút.</p>
        <div class="sc-dialogue">
          <p class="sc-char">An</p>
          <p class="sc-paren">(nhìn thẳng vào ống kính máy quay, mắt ánh lên sự kiên quyết)</p>
          <p class="sc-line">Nếu mình cứ tiếp tục chờ một bàn tay của ai đó đến cứu... thì mình sẽ mắc kẹt ở ngã tư này suốt đời.</p>
        </div>
        <p class="sc-action">An gạt nút khóa an toàn. Động cơ xe lăn điện rít lên một âm thanh dứt khoát. Cô bắt đầu xoay bánh tìm lối thoát bằng chính đôi tay của mình.</p>
        <p class="sc-trans">CẮT SANG <span lang="en">(CUT TO:)</span></p>
      </div>
      <p class="muted small mt-5">Quy ước: NỘI. = <span lang="en">INT.</span> (trong nhà) · NGOẠI. = <span lang="en">EXT.</span> (ngoài trời) · Tên nhân vật viết hoa, chỉ dẫn diễn xuất đặt trong ngoặc.</p>

      <div class="cta-box mt-8">
        <div>
          <h3 class="h-3">Thảo luận về kịch bản đầy đủ hoặc cơ hội hợp tác</h3>
          <p>Mình luôn sẵn lòng gửi trọn bộ Full Script, Treatment chi tiết và Pitch Deck dự án cho các đạo diễn, nhà sản xuất và liên hoan phim quan tâm.</p>
        </div>
        <div class="btn-row">
          <?php if ($contactEmail !== ''): ?>
            <a class="btn btn--primary btn--sm" href="mailto:<?= e($contactEmail) ?>?subject=<?= rawurlencode('[Screenplay Request] Yêu cầu kịch bản hoàn chỉnh: ĐỢI') ?>"><?= icon('mail') ?>Nhận Full Script</a>
          <?php endif; ?>
          <a class="btn btn--outline btn--sm" href="<?= e(url('/lien-he')) ?>">Trò chuyện &amp; kết nối</a>
        </div>
      </div>
    </div>

    <aside aria-label="Thông tin kịch bản">
      <?php if (!empty($featuredFilm)): ?>
        <div class="side-card card">
          <h3 class="h-3">Thông tin phim</h3>
          <dl class="facts">
            <div><dt>Tựa phim</dt><dd><?= e((string) $featuredFilm['title']) ?><?= !empty($featuredFilm['title_en']) ? ' (<span lang="en">' . e((string) $featuredFilm['title_en']) . '</span>)' : '' ?></dd></div>
            <?php if (!empty($featuredFilm['genre'])): ?><div><dt>Thể loại</dt><dd><?= e((string) $featuredFilm['genre']) ?></dd></div><?php endif; ?>
            <?php if (!empty($featuredFilm['duration_note']) || !empty($featuredFilm['duration_min'])): ?><div><dt>Thời lượng</dt><dd><?= e((string) ($featuredFilm['duration_note'] ?: $featuredFilm['duration_min'] . ' phút')) ?></dd></div><?php endif; ?>
            <div><dt>Biên kịch</dt><dd><?= e((string) ($site['authorName'] ?? '')) ?> (kiêm vai An)</dd></div>
            <?php if (!empty($featuredFilm['year'])): ?><div><dt>Năm</dt><dd><?= (int) $featuredFilm['year'] ?></dd></div><?php endif; ?>
          </dl>
          <a class="text-link mt-5" href="<?= e(url((string) $featuredFilm['url'])) ?>">Trang phim <?= e((string) $featuredFilm['title']) ?> <?= icon('arrow-r') ?></a>
        </div>
      <?php endif; ?>
      <?php if (!empty($milestones)): ?>
        <div class="side-card card">
          <h3 class="h-3">Từ trang giấy đến màn ảnh</h3>
          <?= partial('partials/timeline', ['milestones' => $milestones, 'compact' => true]) ?>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</section>
<?php if (!$inStudio): ?></div><?php endif; ?>
