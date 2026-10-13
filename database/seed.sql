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
('menu_main', '[{"label":"Hậu trường biên kịch","url":"/hau-truong-bien-kich"},{"label":"Góc nghề","url":"/goc-nghe"},{"label":"Phim","url":"/phim"},{"label":"Giới thiệu","url":"/gioi-thieu"},{"label":"EN · Press kit","url":"/en/press-kit","style":"button"}]'),
('home_blocks', '["hero-mission","achievements","featured-film","latest-posts","milestones"]'),
('achievements', '[{"value":"Chung kết","label":"Inspiring Asia 2026 · Manila"},{"value":"Chung kết","label":"Inspiring Vietnam 2026 · SIHUB"},{"value":"Top 7","label":"From Script to Screen mùa 1"}]');

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
