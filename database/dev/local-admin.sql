-- CHỈ LOCAL (database/dev/ không deploy): tài khoản admin có sẵn mỗi lần khởi tạo/reset DB.
--   Đăng nhập: admin@thanhthanh.test / aA@2026   (mật khẩu yếu, cố ý cho tiện thử ở máy local)
-- Hosting KHÔNG dùng file này: tạo tài khoản bằng tools/create_user.php với mật khẩu mạnh (≥ 10 ký tự).
-- Hash sinh bằng password_hash('aA@2026', PASSWORD_DEFAULT). Chạy lại file thì đặt lại mật khẩu + quyền admin.
INSERT INTO users (email, name, password_hash, role)
VALUES ('admin@thanhthanh.test', 'Admin', '$2y$10$j/OvP00vLtxKcIVx6dPuDeDEVz284fNokge38jmweFmGFCTrUJaaa', 'admin')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin';
