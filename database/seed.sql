-- =====================================================================
-- Dữ liệu mặc định. Chạy SAU schema.sql. Không chứa tài khoản/mật khẩu.
-- Các nội dung đánh dấu (nháp) cần tác giả duyệt lại câu chữ.
-- =====================================================================
SET NAMES utf8mb4;

-- Danh mục bài viết
INSERT INTO categories (slug, name, description, sort_order) VALUES
('hau-truong-bien-kich', 'Hậu trường biên kịch', 'Chuyện thật từ bàn viết ra trường quay: làm phim ĐỢI, pitching, ekip và những lần đầu tiên.', 1),
('goc-nghe', 'Góc nghề', 'Kiến thức dùng được cho người viết kịch bản và làm phim độc lập.', 2),
('dien-thuyet', 'Diễn thuyết', 'Các buổi chia sẻ, hoạt động cộng đồng và câu chuyện bước ra khỏi vỏ ốc.', 3);

-- Cài đặt site
INSERT INTO settings (`key`, `value`) VALUES
('site_name', 'Thanh Thanh'),
('site_tagline', 'Biên kịch'),
('mission', 'Đưa lát cắt đời sống người ngồi xe lăn vào điện ảnh.'),
('mission_intro', 'Mình là Thanh Thanh, biên kịch và vai chính của phim ngắn ĐỢI. Ở đây mình kể chuyện hậu trường, những “lần đầu tiên”, và những giới hạn mà không ai có quyền đặt ra cho mình.'),
('author_name', 'Thanh Thanh'),
('author_bio', 'Biên kịch ngồi xe lăn, tác giả phim ngắn ĐỢI (Chung kết Inspiring Asia 2026). Đang phát triển phim điện ảnh “Đứa con đến từ cõi âm”.'),
('social_facebook', 'https://www.facebook.com/thanhthanh.KOII'),
('social_threads', ''),
('contact_email', ''),
('default_og_image', ''),
('menu_main', '[{"label":"Bài viết","url":"/bai-viet"},{"label":"Phim","url":"/phim"},{"label":"Góc sáng tác","url":"/goc-sang-tac"},{"label":"Giới thiệu","url":"/gioi-thieu"},{"label":"Liên hệ","url":"/lien-he"},{"label":"EN · Press kit","url":"/en/press-kit","style":"button"}]'),
('home_blocks', '["hero-mission","achievements","featured-film","latest-posts","milestones","collab-invite"]'),
('achievements', '[{"value":"Chung kết","label":"Inspiring Asia 2026 · Manila"},{"value":"Chung kết","label":"Inspiring Vietnam 2026 · SIHUB"},{"value":"Top 7","label":"From Script to Screen mùa 1"}]'),
('about_page', '{"chapters":[{"mark":"15","unit":"tuổi","title":"Cánh cửa khép lại","text":"Căn bệnh viêm khớp dạng thấp ập đến. Các khớp xương sưng tấy, co rút; từ một cô bé nhanh nhẹn, mình mất dần khả năng đi lại rồi nằm liệt hoàn toàn. Gần 10 năm, cuộc sống gói gọn trong bốn bức tường."},{"mark":"25","unit":"tuổi","title":"Chiếc chìa khóa mang tên “Máy tính”","text":"Gia đình dành dụm mua cho mình một chiếc máy tính kết nối Internet. Mình đọc ngấu nghiến, viết những dòng tản văn đầu tiên, rồi tự học biên kịch qua các khóa học trực tuyến."},{"mark":"2026","unit":"","title":"Đưa ĐỢI tới Manila","text":"Tự viết, tự đóng vai An với kinh phí tự túc chưa tới 50 triệu đồng. ĐỢI trở thành 1 trong 2 đại diện Việt Nam ở Top 7 Grand Finalists Inspiring Asia 2026."}],"quote":{"text":"Chúa đã khiến mình trở nên tàn tật. Đó là vì Người tin rằng mình có khả năng chịu đựng.","cite":"Kito Aya · Một lít nước mắt"},"career":[{"year":"2024","items":[{"when":"Nhạc kịch thiếu nhi","title":"“Ước mơ của em” đạt giải cao nhất","body":"Cuộc thi sáng tác kịch bản cho trẻ em do Nhà hát Tuổi Trẻ phối hợp cùng Nhà hát Sangsang Maru (Hàn Quốc) tổ chức. Nhân vật trung tâm – cô bé Nhất Linh ngồi xe lăn – là hình bóng của chính mình năm 15 tuổi.","highlight":false},{"when":"Lần chào dự án đầu tiên","title":"Mang “Đứa con đến từ cõi âm” đi gặp anh Đức Thịnh, chị Thanh Thúy","body":"Một đêm đông Hà Nội rét buốt. Kịch bản khi ấy còn non, cấu trúc nhiều lỗ hổng nên anh chị từ chối – nhưng mình mang về vô vàn trải nghiệm và một khát khao: phải hoàn thành bằng được câu chuyện này.","highlight":false}]},{"year":"2026","items":[{"when":"Phim ngắn ĐỢI ra đời","title":"Gần 3 tháng cho 8 phút phim","body":"Tìm ý tưởng, viết kịch bản, pitching ở số thứ tự 4 trước đạo diễn Trịnh Đình Lê Minh và chị Phạm Thị Hảo, rồi 2 ngày quay dưới nắng Sài Gòn với vai trò biên kịch kiêm vai chính An.","highlight":false},{"when":"Phim điện ảnh","title":"Top 7 From Script to Screen mùa 1","body":"Dự án “Đứa con đến từ cõi âm” vào Top 7. Ban tổ chức tạo điều kiện để mình pitching online vì việc di chuyển xe lăn vào TP.HCM quá phức tạp.","highlight":false},{"when":"Inspiring Vietnam","title":"Top 6 Phim ngắn xuất sắc tại SIHUB","body":"Buổi chiếu đầu tiên của ĐỢI tại Trung tâm Khởi nghiệp Sáng tạo SIHUB, TP.HCM. Đạo diễn Trịnh Đình Lê Minh trao giải cho Thanh Thanh.","highlight":false},{"when":"Diễn thuyết","title":"Lần đầu chia sẻ tại trụ sở Liên Hợp Quốc tại Việt Nam","body":"Bài chia sẻ 20 phút về câu hỏi “Làm thế nào để xây dựng một thế giới đáng sống hơn?” – cột mốc bước ra khỏi chiếc vỏ ốc an toàn 10 năm.","highlight":false},{"when":"10/2026 · Manila, Philippines","title":"Top 7 Grand Finalists Inspiring Asia 2026","body":"Sau Bán kết (1 trong 4 đại diện Việt Nam), ĐỢI là 1 trong 7 tác phẩm tranh tài tại Chung kết Inspiring Asia Micro Film Festival 2026 – và là 1 trong 2 đại diện của Việt Nam.","highlight":true}]}]}');

-- Phim
INSERT INTO films (slug, title, title_en, logline, genre, duration_min, duration_note, year, credits_md, awards_md, badge, related_tag, actors, status, is_featured, sort_order) VALUES
('doi', 'ĐỢI', 'WAITING',
 'An giam mình trong chiếc lồng tâm lý mang tên chờ đợi người khác, và trong chính lựa chọn của mình. Phải qua hết những điều ấy, cô mới tìm được lối ra.',
 'Phim ngắn · Chính kịch', 8, '6 phút (bản dự thi)', 2026,
 '- Biên kịch: Thanh Thanh
- Vai chính (An): Thanh Thanh
- Pitching trước: ĐD Trịnh Đình Lê Minh, chị Phạm Thị Hảo',
 '- Chung kết Inspiring Asia 2026, Manila, Philippines
- Bán kết Inspiring Asia 2026: 1 trong 4 đại diện Việt Nam
- Chung kết Inspiring Vietnam 2026, SIHUB, TP.HCM',
 'Chung kết Inspiring Asia 2026 · Manila', 'phimngandoi', 'Thanh Thanh',
 'released', 1, 1),
('dua-con-den-tu-coi-am', 'Đứa con đến từ cõi âm', NULL,
 'Dự án phim điện ảnh đầu tay, lọt Top 7 From Script to Screen mùa 1.',
 'Phim điện ảnh', NULL, NULL, NULL, NULL,
 '- Top 7 From Script to Screen mùa 1',
 'Top 7 From Script to Screen', NULL, NULL,
 'in_development', 0, 2);

-- Dòng thời gian phim ĐỢI
INSERT INTO film_milestones (film_id, label, title, description, is_highlight, sort_order)
SELECT f.id, m.label, m.title, m.description, m.is_highlight, m.sort_order
FROM films f
JOIN (
  SELECT '2 tuần' AS label, 'Tìm ý tưởng' AS title, 'Ngước theo Stutterer, This is John' AS description, 0 AS is_highlight, 1 AS sort_order
  UNION ALL SELECT '1 tháng+', 'Viết & sửa kịch bản', 'Kèm hồ sơ dự thi', 0, 2
  UNION ALL SELECT '7 ngày', 'Làm hồ sơ pitching', 'Xem ~30 phim ngắn mỗi sáng', 0, 3
  UNION ALL SELECT '1 ngày', 'Pitching thành công', 'STT 4, đúng tinh thần ĐỢI', 0, 4
  UNION ALL SELECT '2 tuần', 'Chỉnh kịch bản', 'Cùng đội sản xuất', 0, 5
  UNION ALL SELECT '2 ngày', 'Lăn lội trường quay', 'Tay cháy nắng đỏ rực', 0, 6
  UNION ALL SELECT 'Top 30', 'Giấy phép phân loại', 'Nộp online, ~10 ngày', 0, 7
  UNION ALL SELECT '10/2026', 'Chung kết Manila', 'Inspiring Asia 2026', 1, 8
) m
WHERE f.slug = 'doi';

-- Trang tĩnh (nội dung nháp, tác giả cập nhật trong admin)
INSERT INTO pages (slug, lang, title, body_md, status) VALUES
('gioi-thieu', 'vi', 'Giới thiệu', 'Mình là Thanh Thanh, biên kịch ngồi xe lăn.

_(Nội dung đang cập nhật.)_', 'published'),
('lien-he', 'vi', 'Liên hệ', 'Liên hệ hợp tác, báo chí và chiếu phim.

_(Nội dung đang cập nhật.)_', 'published'),
('press-kit', 'en', 'Press kit', 'Thanh Thanh is a Vietnamese screenwriter and wheelchair user. Her debut short film WAITING (ĐỢI) is a finalist at Inspiring Asia 2026.

_(Content coming soon.)_', 'published');

-- Press kit (en) là bản tiếng Anh của trang Giới thiệu -> sinh hreflang vi/en
UPDATE pages en JOIN pages vi ON vi.slug = 'gioi-thieu' AND vi.lang = 'vi'
SET en.translation_of = vi.id
WHERE en.slug = 'press-kit' AND en.lang = 'en';
