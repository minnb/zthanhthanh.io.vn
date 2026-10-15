-- =====================================================================
-- Schema website thanhthanh. Nguồn sự thật: chạy lại được từ đầu.
-- Import: Get-Content schema.sql -Raw | mysql -u root thanhthanh
-- Yêu cầu: MySQL 5.7+/MariaDB 10.3+, InnoDB, utf8mb4.
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS site_daily;
DROP TABLE IF EXISTS referrers_daily;
DROP TABLE IF EXISTS page_visitors_daily;
DROP TABLE IF EXISTS page_views_daily;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS redirects;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS film_milestones;
DROP TABLE IF EXISTS films;
DROP TABLE IF EXISTS post_revisions;
DROP TABLE IF EXISTS post_tag;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS media;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS remember_tokens;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Người dùng & đăng nhập
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL,
  name          VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Ghi nhớ đăng nhập": selector công khai + hash của validator
CREATE TABLE remember_tokens (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  selector   CHAR(24) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_remember_selector (selector),
  KEY idx_remember_expires (expires_at),
  CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip         VARCHAR(45) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  success    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_login_ip_time (ip, created_at),
  KEY idx_login_email_time (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Nội dung
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(120) NOT NULL,
  name        VARCHAR(120) NOT NULL,
  description TEXT NULL,
  seo_title   VARCHAR(120) NULL,
  seo_desc    VARCHAR(255) NULL,
  sort_order  SMALLINT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ảnh đã upload (file gốc + các bản WebP 480/960/1440 cùng tên gốc)
CREATE TABLE media (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path          VARCHAR(255) NOT NULL,          -- tương đối với public/uploads, vd 2026/10/poster-doi-a1b2c3
  original_name VARCHAR(255) NULL,
  mime          VARCHAR(60) NOT NULL,
  width         SMALLINT UNSIGNED NULL,
  height        SMALLINT UNSIGNED NULL,
  size_bytes    INT UNSIGNED NULL,
  alt           VARCHAR(255) NOT NULL DEFAULT '',
  caption       VARCHAR(255) NULL,
  focus_x       TINYINT UNSIGNED NOT NULL DEFAULT 50,  -- điểm tâm (%) giữ trong khung khi cắt ảnh; tự dò lúc upload
  focus_y       TINYINT UNSIGNED NOT NULL DEFAULT 50,
  uploaded_by   INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_media_path (path),
  CONSTRAINT fk_media_user FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug           VARCHAR(160) NOT NULL,
  title          VARCHAR(255) NOT NULL,
  excerpt        TEXT NULL,
  body_md        MEDIUMTEXT NOT NULL,
  cover_media_id INT UNSIGNED NULL,
  category_id    INT UNSIGNED NULL,
  author_id      INT UNSIGNED NULL,
  status         ENUM('draft','pending','scheduled','published') NOT NULL DEFAULT 'draft',
  published_at   DATETIME NULL,
  seo_title      VARCHAR(120) NULL,
  seo_desc       VARCHAR(255) NULL,
  lang           CHAR(2) NOT NULL DEFAULT 'vi',
  source         ENUM('manual','facebook','ai-draft') NOT NULL DEFAULT 'manual',
  source_ref     VARCHAR(255) NULL,
  noindex        TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at     DATETIME NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_posts_slug (slug),
  KEY idx_posts_public (status, deleted_at, published_at),
  KEY idx_posts_category (category_id),
  UNIQUE KEY uq_posts_source_ref (source_ref),   -- tool đăng bài: 1 bài gốc = 1 bài web (NULL không bị ràng buộc)
  CONSTRAINT fk_posts_cover FOREIGN KEY (cover_media_id) REFERENCES media (id) ON DELETE SET NULL,
  CONSTRAINT fk_posts_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tags (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug       VARCHAR(120) NOT NULL,
  name       VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tags_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng nối: khóa chính ghép, không cần id/timestamps
CREATE TABLE post_tag (
  post_id INT UNSIGNED NOT NULL,
  tag_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, tag_id),
  KEY idx_post_tag_tag (tag_id),
  CONSTRAINT fk_post_tag_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_post_tag_tag FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lịch sử phiên bản bài viết (giữ 10 bản gần nhất, dọn trong code)
CREATE TABLE post_revisions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NULL,
  title      VARCHAR(255) NOT NULL,
  body_md    MEDIUMTEXT NOT NULL,
  -- 1 = bản nháp tự lưu của bài ĐÃ xuất bản (chưa ra ngoài web). Mỗi bài tối đa 1 bản, xóa khi bấm Cập nhật.
  is_autosave TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_revisions_post (post_id, created_at),
  KEY idx_revisions_autosave (post_id, is_autosave),
  CONSTRAINT fk_revisions_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_revisions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Phim
-- ---------------------------------------------------------------------
CREATE TABLE films (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug            VARCHAR(120) NOT NULL,
  title           VARCHAR(160) NOT NULL,
  title_en        VARCHAR(160) NULL,
  logline         TEXT NULL,
  synopsis_md     MEDIUMTEXT NULL,
  genre           VARCHAR(120) NULL,
  duration_min    SMALLINT UNSIGNED NULL,          -- thời lượng bản gốc (dùng cho JSON-LD Movie)
  duration_note   VARCHAR(160) NULL,               -- ghi chú phiên bản khác, vd "6 phút (bản dự thi)"
  year            SMALLINT UNSIGNED NULL,
  poster_media_id INT UNSIGNED NULL,
  trailer_url     VARCHAR(255) NULL,
  credits_md      TEXT NULL,
  actors          VARCHAR(255) NULL,               -- tên diễn viên, phân cách dấu phẩy (JSON-LD Movie.actor)
  awards_md       TEXT NULL,
  badge           VARCHAR(120) NULL,               -- nhãn ngắn trên poster, vd "Chung kết Inspiring Asia 2026 · Manila"
  related_tag     VARCHAR(120) NULL,               -- slug tag của các bài hậu trường về phim, vd "phimngandoi"
  status          ENUM('released','in_production','in_development') NOT NULL DEFAULT 'released',
  is_featured     TINYINT(1) NOT NULL DEFAULT 0,
  sort_order      SMALLINT NOT NULL DEFAULT 0,
  seo_title       VARCHAR(120) NULL,
  seo_desc        VARCHAR(255) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_films_slug (slug),
  CONSTRAINT fk_films_poster FOREIGN KEY (poster_media_id) REFERENCES media (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE film_milestones (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  film_id      INT UNSIGNED NOT NULL,
  label        VARCHAR(60) NOT NULL,           -- nhãn lớn: "2 tuần", "Top 30", "10/2026"
  title        VARCHAR(160) NOT NULL,
  description  VARCHAR(255) NULL,
  happened_on  DATE NULL,
  is_highlight TINYINT(1) NOT NULL DEFAULT 0,
  sort_order   SMALLINT NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_milestones_film (film_id, sort_order),
  CONSTRAINT fk_milestones_film FOREIGN KEY (film_id) REFERENCES films (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Trang tĩnh, tủ sách, cài đặt, redirect, nhật ký
-- ---------------------------------------------------------------------
CREATE TABLE pages (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug           VARCHAR(120) NOT NULL,
  lang           CHAR(2) NOT NULL DEFAULT 'vi',
  title          VARCHAR(255) NOT NULL,
  body_md        MEDIUMTEXT NOT NULL,
  status         ENUM('draft','published') NOT NULL DEFAULT 'draft',
  translation_of INT UNSIGNED NULL,
  seo_title      VARCHAR(120) NULL,
  seo_desc       VARCHAR(255) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pages_slug_lang (slug, lang),
  CONSTRAINT fk_pages_translation FOREIGN KEY (translation_of) REFERENCES pages (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE books (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title          VARCHAR(255) NOT NULL,
  author         VARCHAR(160) NOT NULL,
  cover_media_id INT UNSIGNED NULL,
  note           TEXT NULL,
  affiliate_url  VARCHAR(500) NULL,
  post_id        INT UNSIGNED NULL,
  is_visible     TINYINT(1) NOT NULL DEFAULT 1,
  sort_order     SMALLINT NOT NULL DEFAULT 0,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_books_cover FOREIGN KEY (cover_media_id) REFERENCES media (id) ON DELETE SET NULL,
  CONSTRAINT fk_books_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key`      VARCHAR(100) NOT NULL,
  `value`    MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_settings_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE redirects (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  old_path    VARCHAR(255) NOT NULL,
  new_path    VARCHAR(255) NOT NULL,
  status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  hits        INT UNSIGNED NOT NULL DEFAULT 0,
  last_hit_at DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_redirects_old (old_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,
  action      VARCHAR(60) NOT NULL,            -- create | update | publish | delete | restore | login ...
  entity_type VARCHAR(60) NULL,
  entity_id   INT UNSIGNED NULL,
  details     TEXT NULL,                       -- JSON
  ip          VARCHAR(45) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_time (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Thống kê lượt truy cập (beacon POST /t từ app.js, xem Models/PageStats.php)
-- Chỉ lưu số tổng hợp theo ngày, không lưu IP. Mã người xem = băm(salt đổi hằng ngày + IP + UA),
-- chỉ giữ 2 ngày để đếm "người xem" và chống bơm số.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_views_daily (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  day        DATE NOT NULL,
  path       VARCHAR(255) NOT NULL,
  views      INT UNSIGNED NOT NULL DEFAULT 0,
  visitors   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_page_views_day_path (day, path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_visitors_daily (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  day        DATE NOT NULL,
  visitor    CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  path       VARCHAR(255) NOT NULL,
  hits       INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_page_visitors (day, visitor, path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referrers_daily (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  day        DATE NOT NULL,
  source     VARCHAR(100) NOT NULL,
  views      INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_referrers_day_source (day, source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_daily (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  day        DATE NOT NULL,
  views      INT UNSIGNED NOT NULL DEFAULT 0,
  visitors   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_site_daily_day (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;