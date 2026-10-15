-- Migration 2026-10-15: bảng thống kê lượt truy cập (Phương án A).
-- Chạy MỘT lần trên DB đã có dữ liệu (hosting): chỉ CREATE TABLE IF NOT EXISTS, không xóa gì.
-- mysql -u USER -p --default-character-set=utf8mb4 TEN_DB -e "source 20261015_page_stats.sql"
SET NAMES utf8mb4;
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