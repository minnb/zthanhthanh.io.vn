-- =====================================================================
-- DỮ LIỆU MẪU CHỈ DÙNG Ở LOCAL để thử giao diện (Bước 3).
-- KHÔNG import lên hosting. Bước 6 nhập bài thật bằng tools/contents/import_facebook.php.
-- Chạy sau schema.sql + seed.sql.
-- =====================================================================
SET NAMES utf8mb4;

INSERT INTO tags (slug, name) VALUES
('hautruongbienkich', 'hautruongbienkich'),
('phimngandoi', 'PhimngắnĐỢI'),
('khan-gia-muc-tieu', 'khángiảmụctiêu');

INSERT INTO posts (slug, title, excerpt, body_md, category_id, status, published_at, source, source_ref) VALUES
('khong-de-nguoi-khac-quyet-dinh-gioi-han-cua-ban-than',
 'Không để người khác quyết định giới hạn của bản thân',
 'Một câu chê thẳng mặt, hai ngày ra trường quay với cánh tay cháy nắng, và vai diễn từng bị “dự báo” làm bể phim.',
 'Hôm qua ngồi xem series *Agency*, nghe câu thoại của nhân vật chính, mình phải note lại liền: **“Không để người khác quyết định giới hạn của bản thân.”**

Nhớ lại hồi mình chuẩn bị làm phim ngắn ĐỢI, hí hửng khoe với ông bạn sẽ tự đóng vai chính. Ông bạn bốp chát luôn vào mặt mình:

> Bà mà đóng vai chính, phim của bà bể là cái chắc.

Cũng phải thừa nhận khả năng này có thể xảy ra vì mình chưa từng làm diễn viên, cũng chưa học qua lớp diễn xuất nào. Viết kịch bản chưa ổn thì còn ngồi sửa được chứ ra trường quay mà diễn đơ, cả đoàn đứng chờ thì áp lực lắm.

## Nhận vai, rồi tập như thật

Nhưng vai An mình vẫn nhận, thứ nhất là vì kinh phí làm phim ít, thứ hai là mình muốn trải nghiệm một lần ở vị trí mới. Có điều từ hôm ấy mình đọc kịch bản kỹ hơn, tự luyện tập diễn xuất nhiều hơn. Cảnh nào có cảm xúc khó, mình tập diễn trước, còn nhờ chị sang Việt Trì quay để xem mình diễn thế nào rồi sửa. Chưa biết lên phim thế nào chứ ở nhà đã có một cameraman kiêm khán giả bất đắc dĩ để mình luyện rồi hẹ hẹ.

## Người lạ ở Sài Gòn và một lời khen

Hôm trước, một bạn trong Sài Gòn nhắn rằng bạn đã xem phim ĐỢI tại đêm chung kết Inspiring Vietnam 2026 và khen nét diễn của mình. Hóa ra cái vai từng được dự báo là nguyên nhân khiến phim thất bại lại có người thích.

Đúng là giới hạn của bản thân đâu thể nằm ở mồm người khác, giới hạn của mình nằm ở việc mình có dám dấn thân.',
 1, 'published', '2026-09-20 09:00:00', 'facebook', 'bai_viet/facebook_thanhthanh/bai_viet_thang_9.txt#1'),
('dung-giua-cho-rau-rao-ban-sach-account',
 'Đứng giữa chợ rau rao bán sách Account: bài học về khán giả mục tiêu',
 'Trước khi viết, câu đầu tiên phải trả lời: mình kể câu chuyện này cho ai xem?',
 'Khum ngờ bên Threads cũng bán được đồ mọi người ạ.

Mình có khóa học đem pass, dành cho những bạn sinh viên mới ra trường muốn tìm hiểu ngành Account. Vậy mà ban đầu mình lại hí hửng mang khóa học vào pass các nhóm mẹ bỉm sữa. Và kết quả KHÔNG một ai hỏi han, nhòm ngó.

## Kể cho ai xem?

Chuyện này khiến mình liên tưởng đến một nguyên tắc cốt lõi trong viết kịch bản: tính phù hợp với khán giả mục tiêu. Trước khi viết, điều đầu tiên cần xác định là sẽ kể câu chuyện này cho ai xem?

Cùng một chủ đề về tình yêu nhưng viết cho khán giả tuổi teen sẽ khác với người đã lập gia đình.

> Không xác định đúng người cần thì sản phẩm có tốt đến đâu, mình cũng chỉ đang đứng giữa chợ bán rau mà rêu rao bán sách học Account.',
 2, 'published', '2026-09-15 09:00:00', 'facebook', 'bai_viet/facebook_thanhthanh/bai_viet_thang_9.txt#2'),
('giay-phep-phan-loai-phim',
 'Giấy phép phân loại phim: chiếc vé thông hành ai làm phim cũng cần',
 'Nộp online, khoảng 10 ngày, lệ phí 1,6 triệu cho phim dưới 60 phút. Kinh nghiệm thật từ ĐỢI.',
 'Hành trình ĐỢI được công chiếu tại Chung kết Inspiring Vietnam 2026 không thể thiếu chiếc **giấy phép phân loại phim**.

Muốn có chiếc giấy phép này, nhà làm phim phải nộp hồ sơ trên Cổng Dịch vụ công Quốc gia. Cơ quan giải quyết là Bộ Văn hóa, Thể thao và Du lịch (Cục Điện ảnh).

## Mất bao lâu, tốn bao nhiêu?

- Khoảng 7 ngày sau khi nộp hồ sơ có kết quả cấp phép.
- Khoảng 3 ngày sau nữa nhận giấy phép bản cứng.
- Phim dưới 60 phút, phi thương mại: lệ phí 1 triệu 6.

Với một người khó đi lại như mình, việc gần như mọi thứ đều làm được online thực sự là phần dễ thở nhất trong quá trình làm phim.',
 2, 'published', '2026-09-10 09:00:00', 'facebook', 'bai_viet/facebook_thanhthanh/bai_viet_thang_9.txt#5'),
('bai-nhap-khong-duoc-hien',
 'Bài nháp – không được hiện ra ngoài',
 NULL, 'Nội dung nháp.', 1, 'draft', NULL, 'manual', NULL);

-- Thử chuyển hướng 301 từ bảng redirects (tests/smoke.php)
INSERT INTO redirects (old_path, new_path) VALUES ('/bai-cu-thu-nghiem', '/goc-nghe/giay-phep-phan-loai-phim');

-- Bài nhập về chưa từng được sửa: ngày cập nhật = ngày đăng (tránh hiện "Cập nhật" sai)
UPDATE posts SET updated_at = published_at WHERE published_at IS NOT NULL;

INSERT INTO post_tag (post_id, tag_id)
SELECT p.id, t.id FROM posts p JOIN tags t
WHERE (p.slug = 'khong-de-nguoi-khac-quyet-dinh-gioi-han-cua-ban-than' AND t.slug IN ('hautruongbienkich', 'phimngandoi'))
   OR (p.slug = 'dung-giua-cho-rau-rao-ban-sach-account' AND t.slug IN ('hautruongbienkich', 'khan-gia-muc-tieu'))
   OR (p.slug = 'giay-phep-phan-loai-phim' AND t.slug IN ('hautruongbienkich', 'phimngandoi'));
