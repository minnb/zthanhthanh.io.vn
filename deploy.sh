#!/usr/bin/env bash
# Deploy website lên hosting (cPanel + SSH): kéo code mới từ GitHub, kiểm tra, dọn cache.
# Chạy TRÊN HOSTING, trong thư mục repo:   bash deploy.sh [tuỳ chọn]
#
#   (không tuỳ chọn)   Cập nhật lên commit mới nhất của nhánh $BRANCH
#   --check            Chỉ xem có gì mới, không thay đổi gì
#   --rollback         Quay về commit trước lần deploy gần nhất
#   --url=https://...  Sau khi deploy, gọi thử URL này, báo nếu không trả 200
#   -h, --help         Hiện trợ giúp
#
# Biến môi trường (tuỳ chọn): BRANCH (mặc định main), PHP_BIN (đường dẫn php 8.2+), SITE_URL.
# Script KHÔNG đọc app/config.php, chỉ kiểm tra file có tồn tại.

set -euo pipefail

BRANCH="${BRANCH:-}"
SITE_URL="${SITE_URL:-}"
MODE="deploy"

for arg in "$@"; do
  case "$arg" in
    --check) MODE="check" ;;
    --rollback) MODE="rollback" ;;
    --url=*) SITE_URL="${arg#--url=}" ;;
    -h|--help) sed -n '2,13p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) echo "Tuỳ chọn không hợp lệ: $arg (xem --help)" >&2; exit 2 ;;
  esac
done

cd "$(dirname "$0")"
LOG_FILE="storage/logs/deploy.log"

info() { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m OK\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m !!\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[1;31mLỖI\033[0m %s\n' "$*" >&2; exit 1; }

# Ghi một dòng nhật ký: thời gian|commit cũ|commit mới|trạng thái
log_deploy() {
  mkdir -p "$(dirname "$LOG_FILE")"
  printf '%s|%s|%s|%s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1" "$2" "$3" >> "$LOG_FILE"
}

# Tìm php >= 8.2: ưu tiên PHP_BIN, rồi các bản cPanel (EA-PHP), cuối cùng là php trong PATH
find_php() {
  local c
  for c in "${PHP_BIN:-}" /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php \
           /opt/cpanel/ea-php82/root/usr/bin/php php; do
    [ -n "$c" ] || continue
    command -v "$c" >/dev/null 2>&1 || continue
    if "$c" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' >/dev/null 2>&1; then
      echo "$c"
      return 0
    fi
  done
  return 1
}

# Lint toàn bộ file PHP của site. Trả về 0 nếu sạch, 1 nếu có lỗi (in lỗi ra stderr).
lint_php() {
  local bad
  bad="$(find app public themes -name '*.php' -exec "$PHP" -l {} \; 2>&1 | grep -v '^No syntax errors' || true)"
  if [ -n "$bad" ]; then
    printf '%s\n' "$bad" >&2
    return 1
  fi
  return 0
}

clear_cache() {
  [ -d storage/cache ] || return 0
  find storage/cache -type f ! -name .gitkeep -delete
}

# ---------- Kiểm tra môi trường ----------
command -v git >/dev/null 2>&1 || die "Không tìm thấy git."
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "Thư mục này không phải git repo."
[ -f app/config.php ] || die "Thiếu app/config.php. Tạo từ app/config.sample.php rồi điền cấu hình production."
[ -f public/index.php ] || die "Thiếu public/index.php: chạy script từ thư mục gốc của website."
PHP="$(find_php)" || die "Không tìm thấy PHP 8.2+. Đặt PHP_BIN=/đường/dẫn/php rồi chạy lại."
info "PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
BRANCH="${BRANCH:-$CURRENT_BRANCH}"
[ "$CURRENT_BRANCH" = "$BRANCH" ] || die "Đang ở nhánh '$CURRENT_BRANCH', khác nhánh deploy '$BRANCH'."

# Không deploy khi có sửa tay chưa commit trên hosting (tránh mất việc / xung đột)
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  git status --short --untracked-files=no >&2
  die "Có file đã bị sửa trực tiếp trên hosting. Hãy xử lý (git diff / git stash) trước khi deploy."
fi

OLD="$(git rev-parse HEAD)"

# ---------- Rollback ----------
if [ "$MODE" = "rollback" ]; then
  [ -f "$LOG_FILE" ] || die "Chưa có nhật ký deploy ($LOG_FILE) nên không biết quay về đâu."
  LAST="$(grep '|OK$' "$LOG_FILE" | tail -n 1 || true)"
  [ -n "$LAST" ] || die "Không có lần deploy thành công nào trong nhật ký."
  PREV="$(printf '%s' "$LAST" | cut -d'|' -f2)"
  NEW="$(printf '%s' "$LAST" | cut -d'|' -f3)"
  [ "$OLD" = "$NEW" ] || die "HEAD hiện tại (${OLD:0:7}) khác commit của lần deploy gần nhất (${NEW:0:7}). Rollback tự động không an toàn."
  info "Quay về ${PREV:0:7} (đang ở ${NEW:0:7})"
  git reset --hard "$PREV" >/dev/null
  clear_cache
  log_deploy "$NEW" "$PREV" "ROLLBACK"
  ok "Đã rollback về ${PREV:0:7}. Lần deploy sau sẽ kéo lại commit mới nếu chưa sửa trên GitHub."
  exit 0
fi

# ---------- Lấy commit mới ----------
info "Lấy dữ liệu từ origin/$BRANCH"
git fetch --quiet origin "$BRANCH"
REMOTE="$(git rev-parse "origin/$BRANCH")"

if [ "$OLD" = "$REMOTE" ]; then
  ok "Đã là bản mới nhất (${OLD:0:7}), không có gì để deploy."
  [ "$MODE" = "check" ] || clear_cache
  exit 0
fi

git merge-base --is-ancestor "$OLD" "$REMOTE" || die "origin/$BRANCH không phải bản tiến tiếp của hosting (lịch sử bị viết lại?). Dừng, hãy kiểm tra thủ công."

info "Các commit sắp được deploy:"
git log --oneline "$OLD..$REMOTE"
info "File thay đổi:"
git diff --stat "$OLD" "$REMOTE" | tail -n 20

if [ "$MODE" = "check" ]; then
  ok "Chế độ --check: không thay đổi gì."
  exit 0
fi

# ---------- Deploy ----------
git merge --ff-only --quiet "origin/$BRANCH"
NEW="$(git rev-parse HEAD)"

info "Kiểm tra cú pháp PHP"
if ! lint_php; then
  warn "Có lỗi cú pháp, tự động quay về ${OLD:0:7}."
  git reset --hard "$OLD" >/dev/null
  clear_cache
  log_deploy "$OLD" "$NEW" "LINT_FAIL"
  die "Deploy bị huỷ. Sửa lỗi trên máy, push lại rồi deploy."
fi
ok "Không có lỗi cú pháp"

for d in storage/cache storage/logs public/uploads; do
  [ -w "$d" ] || warn "Thư mục '$d' không ghi được. Cần chmod (755, hoặc 775 nếu vẫn lỗi)."
done

clear_cache
ok "Đã xoá cache HTML"

# Cảnh báo việc phải làm tay: thay đổi cấu trúc database hoặc khoá cấu hình
if [ -n "$(git diff --name-only "$OLD" "$NEW" -- database/schema.sql database/migrations)" ]; then
  warn "Database có thay đổi (schema.sql / migrations). Hãy chạy các file migration mới bằng tay. KHÔNG import lại schema.sql vì sẽ xoá dữ liệu."
fi
if [ -n "$(git diff --name-only "$OLD" "$NEW" -- app/config.sample.php)" ]; then
  warn "config.sample.php có thay đổi. Đối chiếu và bổ sung khoá mới vào app/config.php."
fi

log_deploy "$OLD" "$NEW" "OK"
ok "Deploy xong: ${OLD:0:7} -> ${NEW:0:7}"

# ---------- Kiểm tra sức khoẻ (tuỳ chọn) ----------
if [ -n "$SITE_URL" ]; then
  if command -v curl >/dev/null 2>&1; then
    CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$SITE_URL" || true)"
    if [ "$CODE" = "200" ]; then
      ok "$SITE_URL trả 200"
    else
      warn "$SITE_URL trả '$CODE' (kỳ vọng 200). Kiểm tra ngay, nếu hỏng thì: bash deploy.sh --rollback"
    fi
  else
    warn "Không có curl nên bỏ qua kiểm tra URL."
  fi
fi
