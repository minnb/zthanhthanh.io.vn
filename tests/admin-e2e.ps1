# Kiểm thử đầu-cuối trang Admin bằng curl (cookie jar) – CHỈ LOCAL, RESET DB.
# Chạy từ thư mục gốc repo: powershell -NoProfile -ExecutionPolicy Bypass -File src\thanhthanh\tests\admin-e2e.ps1
# Tài khoản thử *@local.test tạo bằng mật khẩu ngẫu nhiên và bị xóa ở cuối; file tạm ghi vào %TEMP%\tt-admin-e2e.
$ErrorActionPreference = 'Continue'
$php = 'd:\ROOT\xampp\php\php.exe'
$mysql = 'd:\ROOT\xampp\mysql\bin\mysql.exe'
$B = 'http://localhost/projects/thanhthanh/src/thanhthanh/public'
$S = Join-Path $env:TEMP 'tt-admin-e2e'; New-Item -ItemType Directory -Force $S | Out-Null
$jarA = "$S\jar-admin.txt"; $jarE = "$S\jar-editor.txt"; $jarR = "$S\jar-remember.txt"
# Xóa MỌI cookie jar cũ: DB bị reset nên id user lặp lại -> session cũ có thể trùng uid tài khoản mới
Get-ChildItem $S -Filter 'jar-*.txt' -ErrorAction SilentlyContinue | ForEach-Object { [IO.File]::Delete($_.FullName) }
$pass = 0; $fail = 0
function Check([string]$name, [bool]$ok, [string]$detail = '') {
  if ($ok) { $script:pass++; Write-Output "  PASS  $name" } else { $script:fail++; Write-Output "  FAIL  $name  $detail" }
}
function Sql([string]$q) { (& $mysql -u root --default-character-set=utf8mb4 -N thanhthanh -e $q) -join "`n" }
function Get-Page([string]$path, [string]$jar) { (curl.exe -s -b $jar -c $jar "$B$path") -join "`n" }
function Csrf([string]$html) { [regex]::Match($html, 'name="_csrf" value="([a-f0-9]{64})"').Groups[1].Value }
# POST form: trả "mã|location"
function Post-Form([string]$path, [string]$jar, [string[]]$fields) {
  $args = @('-s', '-X', 'POST', '-o', 'NUL', '-w', '%{http_code}|%{redirect_url}', '-b', $jar, '-c', $jar)
  foreach ($f in $fields) { $args += @('--data-urlencode', $f) }
  curl.exe @args "$B$path"
}
function Login([string]$jar, [string]$email, [string]$pw, [bool]$remember = $false) {
  $t = Csrf (Get-Page '/admin/login' $jar)
  $f = @("_csrf=$t", "email=$email", "password=$pw", 'next=/admin')
  if ($remember) { $f += 'remember=1' }
  Post-Form '/admin/login' $jar $f
}

Write-Output '== Reset DB local (schema + seed + dữ liệu mẫu) =='
& $mysql -u root --default-character-set=utf8mb4 thanhthanh -e "source src/thanhthanh/database/schema.sql; source src/thanhthanh/database/seed.sql; source src/thanhthanh/database/dev/sample.sql; source src/thanhthanh/database/dev/local-admin.sql;"
Write-Output '== Chuẩn bị tài khoản thử (mật khẩu ngẫu nhiên) =='
$pwA = 'A' + [guid]::NewGuid().ToString('N'); $pwE = 'E' + [guid]::NewGuid().ToString('N')
Sql "DELETE FROM users WHERE email LIKE '%@local.test'; DELETE FROM login_attempts;" | Out-Null
$pwA | & $php tools\create_user.php --site=thanhthanh --email=qa-admin@local.test --name="QA Admin" --role=admin 2>$null | Out-Null
$pwE | & $php tools\create_user.php --site=thanhthanh --email=qa-editor@local.test --name="QA Editor" --role=editor 2>$null | Out-Null
Check 'create_user tạo 2 tài khoản' ((Sql "SELECT COUNT(*) FROM users WHERE email LIKE 'qa-%@local.test'") -eq '2')
$short = 'ngan' | & $php tools\create_user.php --site=thanhthanh --email=x@local.test --name=X --role=editor 2>&1
Check 'create_user từ chối mật khẩu < 10 ký tự' ($LASTEXITCODE -eq 2)

Write-Output '== Đăng nhập & phân quyền =='
$r = Login $jarA 'qa-admin@local.test' 'sai-mat-khau-123'
Check 'Sai mật khẩu -> 422, không chuyển hướng' ($r -like '422|*') $r
$r = Login $jarA 'qa-admin@local.test' $pwA
Check 'Đúng mật khẩu -> 303 về /admin' ($r -eq "303|$B/admin") $r
$dash = Get-Page '/admin' $jarA
Check 'Tổng quan hiển thị sau đăng nhập' ($dash -match 'Tổng quan' -and $dash -match 'QA Admin')
$r = Post-Form '/admin/posts/new' $jarA @()
Check 'POST thiếu CSRF -> 403' ($r -like '403|*') $r
$r = Post-Form '/admin/posts/new' $jarA @('_csrf=sai')
Check 'POST CSRF sai -> 403' ($r -like '403|*') $r
$r = Login $jarE 'qa-editor@local.test' $pwE
Check 'Editor đăng nhập' ($r -eq "303|$B/admin") $r
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jarE "$B/admin/settings"
Check 'Editor vào Cài đặt -> 403' ($code -eq '403') $code
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jarA "$B/admin/settings"
Check 'Admin vào Cài đặt -> 200' ($code -eq '200') $code
$r = Login $jarR 'qa-admin@local.test' $pwA $true
$remember = Get-Content $jarR | Where-Object { $_ -match 'tt_remember' }
Set-Content -Path $jarR -Value (@('# Netscape HTTP Cookie File') + $remember)
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jarR -c $jarR "$B/admin"
Check '"Ghi nhớ đăng nhập": chỉ cookie remember vẫn vào được /admin' ($code -eq '200') $code
$oldToken = ($remember -split "`t" | Select-Object -Last 1)
$oldSelector = ($oldToken -split '%3A|:')[0]   # setcookie() mã hóa ":" thành %3A trong cookie jar
$code = curl.exe -s -o NUL -w '%{http_code}' -b "tt_remember=$oldToken" "$B/admin"
Check 'Token cũ dùng lại trong 30 giây ân hạn (request song song) -> vẫn vào được' ($code -eq '200') $code
Sql "UPDATE remember_tokens SET expires_at = NOW() - INTERVAL 1 SECOND WHERE selector = '$oldSelector'" | Out-Null
$code = curl.exe -s -o NUL -w '%{http_code}' -b "tt_remember=$oldToken" "$B/admin"
Check 'Token cũ hết ân hạn -> bị từ chối' ($code -eq '303') $code
$t0 = Csrf (Get-Page '/admin/login' "$S\jar-z.txt")
$r = Post-Form '/admin/login' "$S\jar-z.txt" @("_csrf=$t0", 'email=qa-admin@local.test', "password=$pwA", "next=/admin`n")
Check 'next có xuống dòng -> về /admin, không lỗi 500' ($r -eq "303|$B/admin") $r

Write-Output '== Soạn bài =='
[IO.File]::WriteAllText("$S\autosave.json", '{"title":"Tu luu thu","body_md":"Noi dung tu luu"}')
[IO.File]::WriteAllText("$S\preview.json", '{"body":"## Muc\n\n<script>alert(1)</script> [link](/phim/doi)"}')
[IO.File]::WriteAllText("$S\broken.json", '{title:Tu luu thu')
$t = Csrf $dash
$r = Post-Form '/admin/posts/new' $jarA @("_csrf=$t")
$postId = [regex]::Match($r, '/admin/posts/(\d+)$').Groups[1].Value
Check '＋ Viết tạo nháp và mở trình soạn' ($postId -ne '') $r
$edit = Get-Page "/admin/posts/$postId" $jarA
$t = Csrf $edit
$api = curl.exe -s -b $jarA -H 'Content-Type: application/json' -H "X-CSRF-Token: $t" -H 'Accept: application/json' --data-binary "@$S\autosave.json" "$B/admin/api/posts/$postId/autosave"
Check 'Tự lưu qua API -> ok' ($api -match '"ok":true') $api
Check 'Tự lưu ghi vào DB' ((Sql "SELECT title FROM posts WHERE id=$postId") -eq 'Tu luu thu')
$api = curl.exe -s -w '|%{http_code}' -b $jarA -H 'Content-Type: application/json' -H "X-CSRF-Token: $t" --data-binary "@$S\broken.json" "$B/admin/api/posts/$postId/autosave"
Check 'Tự lưu JSON hỏng -> 422, KHÔNG xóa nội dung bài' ($api -match '\|422$' -and (Sql "SELECT title FROM posts WHERE id=$postId") -eq 'Tu luu thu') $api
$api = curl.exe -s -w '|%{http_code}' -b $jarA -H 'Content-Type: application/json' --data-binary "@$S\autosave.json" "$B/admin/api/posts/$postId/autosave"
Check 'API thiếu CSRF -> JSON lỗi 403' ($api -match '"ok":false' -and $api -match '\|403$') $api
$api = curl.exe -s -w '|%{http_code}' -H 'Content-Type: application/json' --data-binary "@$S\preview.json" "$B/admin/api/preview"
Check 'API chưa đăng nhập -> JSON 401' ($api -match '"ok":false' -and $api -match '\|401$') $api
$api = curl.exe -s -b $jarA -H 'Content-Type: application/json' -H "X-CSRF-Token: $t" --data-binary "@$S\preview.json" "$B/admin/api/preview"
Check 'Xem trước: render H2, escape <script>, link nội bộ đầy đủ' ($api -match '<h2>Muc' -and $api -match '&lt;script&gt;' -and $api -match '/public/phim/doi') $api

$cat = Sql "SELECT id FROM categories WHERE slug='goc-nghe'"
$body = "Doan mo dau.`n`n## Muc hai`n`nXem [phim DOI](/phim/doi)."
$r = Post-Form "/admin/posts/$postId" $jarA @("_csrf=$t", 'title=Bai thu nghiem admin', 'slug=', "body_md=$body", "category_id=$cat", 'status=draft', 'intent=publish', 'tags=thu nghiem, PhimngắnĐỢI', 'excerpt=Tom tat bai thu nghiem cua trang quan tri.')
Check 'Xuất bản -> 303 về trình soạn' ($r -eq "303|$B/admin/posts/$postId") $r
Check 'DB: status=published, slug sinh từ tiêu đề' ((Sql "SELECT CONCAT(status,'|',slug) FROM posts WHERE id=$postId") -eq 'published|bai-thu-nghiem-admin')
Check 'Đã ghi post_revisions' ([int](Sql "SELECT COUNT(*) FROM post_revisions WHERE post_id=$postId") -ge 1)
Check 'Tag gộp theo slug (phimngandoi đã có sẵn)' ((Sql "SELECT COUNT(*) FROM post_tag pt JOIN tags t ON t.id=pt.tag_id WHERE pt.post_id=$postId AND t.slug IN ('phimngandoi','thu-nghiem')") -eq '2')
$code = curl.exe -s -o NUL -w '%{http_code}' "$B/goc-nghe/bai-thu-nghiem-admin"
Check 'Bài hiện ra ngoài (200)' ($code -eq '200') $code
[IO.File]::WriteAllText("$S\autosave-live.json", '{"title":"Bai thu nghiem admin","body_md":"NOI DUNG DANG SUA DO"}')
$api = curl.exe -s -b $jarA -H 'Content-Type: application/json' -H "X-CSRF-Token: $t" --data-binary "@$S\autosave-live.json" "$B/admin/api/posts/$postId/autosave"
Check 'Tự lưu bài ĐÃ xuất bản -> mode=draft' ($api -match '"mode":"draft"') $api
Check 'Tự lưu bài đã xuất bản KHÔNG đổi nội dung đang đăng' (-not ((Sql "SELECT body_md FROM posts WHERE id=$postId") -match 'DANG SUA DO') -and -not (((curl.exe -s "$B/goc-nghe/bai-thu-nghiem-admin") -join '') -match 'DANG SUA DO')) ''
Check 'Có đúng 1 bản nháp tự lưu' ((Sql "SELECT COUNT(*) FROM post_revisions WHERE post_id=$postId AND is_autosave=1") -eq '1')
Check 'Trình soạn đề nghị mở bản nháp tự lưu' ((Get-Page "/admin/posts/$postId" $jarA) -match 'data-restore-autosave') ''
$r = Post-Form "/admin/posts/$postId" $jarA @("_csrf=$t", 'title=Bai thu nghiem admin', 'slug=bai-thu-nghiem-doi-slug', "body_md=$body", "category_id=$cat", 'status=published')
Check 'Đổi slug -> lưu' ($r -like '303|*') $r
Check 'Bấm Cập nhật -> xóa bản nháp tự lưu' ((Sql "SELECT COUNT(*) FROM post_revisions WHERE post_id=$postId AND is_autosave=1") -eq '0')
$r = curl.exe -s -o NUL -w '%{http_code}|%{redirect_url}' "$B/goc-nghe/bai-thu-nghiem-admin"
Check 'URL cũ -> 301 tới slug mới' ($r -eq "301|$B/goc-nghe/bai-thu-nghiem-doi-slug") $r
$r = Post-Form "/admin/posts/$postId" $jarA @("_csrf=$t", 'title=', "body_md=$body", 'category_id=', 'status=published')
$errHtml = Get-Page "/admin/posts/$postId" $jarA
Check 'Xuất bản thiếu tiêu đề/danh mục -> 422 (không lưu)' ($r -like '422|*' -and (Sql "SELECT title FROM posts WHERE id=$postId") -eq 'Bai thu nghiem admin') $r
$dup = Sql "SELECT slug FROM posts WHERE id<>$postId AND deleted_at IS NULL LIMIT 1"
$r = Post-Form "/admin/posts/$postId" $jarA @("_csrf=$t", 'title=Bai thu nghiem admin', "slug=$dup", "body_md=$body", "category_id=$cat", 'status=published')
Check 'Slug trùng bài khác -> 422' ($r -like '422|*') $r

Write-Output '== Thư viện ảnh =='
& $php -r '$i=imagecreatetruecolor(2000,1200);imagefill($i,0,0,imagecolorallocate($i,142,31,47));imagejpeg($i,$argv[1],85);' "$S\anh-thu.jpg"
'day khong phai anh' | Set-Content "$S\gia-anh.jpg"
$t = Csrf (Get-Page '/admin/media' $jarA)
$r = curl.exe -s -o NUL -w '%{http_code}|%{redirect_url}' -b $jarA -c $jarA -F "_csrf=$t" -F 'alt=Anh thu nghiem' -F "files[]=@$S\anh-thu.jpg;type=image/jpeg" "$B/admin/media/upload"
Check 'Upload ảnh -> 303' ($r -like '303|*') $r
$m = Sql "SELECT CONCAT(id,'|',path,'|',width,'|',height,'|',alt) FROM media ORDER BY id DESC LIMIT 1"
$mid, $mpath, $mw, $mh, $malt = $m -split '\|'
Check 'DB media: rộng 1440, cao 864 (giữ tỉ lệ), có alt' ($mw -eq '1440' -and $mh -eq '864' -and $malt -eq 'Anh thu nghiem') $m
$files = foreach ($w in 480, 960, 1440) { Test-Path "src\thanhthanh\public\uploads\$($mpath -replace '/', '\')-$w.webp" }
Check 'Sinh đủ 3 file WebP 480/960/1440' (-not ($files -contains $false)) ($files -join ',')
$ogFile = "src\thanhthanh\public\uploads\$($mpath -replace '/', '\')-og.jpg"
$ogSize = if (Test-Path $ogFile) { & $php -r '$s=getimagesize($argv[1]);echo $s[0],''x'',$s[1];' $ogFile } else { 'không có' }
Check 'Sinh ảnh chia sẻ Facebook -og.jpg 1200x630' ($ogSize -eq '1200x630') $ogSize
$code = curl.exe -s -o NUL -w '%{http_code}' "$B/uploads/$mpath-960.webp"
Check 'Ảnh WebP tải được qua web' ($code -eq '200') $code
$r = curl.exe -s -o NUL -w '%{http_code}|%{redirect_url}' -b $jarA -c $jarA -F "_csrf=$t" -F "files[]=@$S\gia-anh.jpg;type=image/jpeg" "$B/admin/media/upload"
$flash = Get-Page '/admin/media' $jarA
Check 'File giả ảnh bị từ chối (finfo)' ($flash -match 'Chỉ nhận ảnh JPG' -and (Sql 'SELECT COUNT(*) FROM media') -eq '1') ($r)
$api = curl.exe -s -b $jarA -H 'Accept: application/json' "$B/admin/api/media"
Check 'API danh sách ảnh trả JSON' ($api -match '"ok":true' -and $api -match $mpath) $api
Check 'API ảnh có điểm tâm (focus_x/focus_y)' ($api -match '"focus_x":\d+' -and $api -match '"focus_y":\d+') ''
# Điểm tâm: thiếu CSRF bị chặn; giá trị ngoài 0–100 bị kẹp; đổi điểm tâm thì tạo lại -og.jpg
$focusBefore = Sql "SELECT CONCAT(focus_x,'|',focus_y) FROM media WHERE id=$mid"
$r = Post-Form "/admin/media/$mid" $jarA @('_csrf=sai', 'alt=Anh thu nghiem', 'focus_x=10', 'focus_y=10')
Check 'Lưu điểm tâm thiếu CSRF -> 403, không đổi' ($r -like '403|*' -and (Sql "SELECT CONCAT(focus_x,'|',focus_y) FROM media WHERE id=$mid") -eq $focusBefore) $r
$ogTime = (Get-Item $ogFile).LastWriteTimeUtc
Start-Sleep -Milliseconds 1100
$r = Post-Form "/admin/media/$mid" $jarA @("_csrf=$t", 'alt=Anh thu nghiem', 'focus_x=150', 'focus_y=-5')
Check 'Lưu điểm tâm ngoài khoảng -> kẹp 100/0' ($r -like '303|*' -and (Sql "SELECT CONCAT(focus_x,'|',focus_y) FROM media WHERE id=$mid") -eq '100|0') $r
Check 'Đổi điểm tâm -> tạo lại -og.jpg' ((Get-Item $ogFile).LastWriteTimeUtc -gt $ogTime) ''
$lib = Get-Page '/admin/media' $jarA
Check 'Thư viện ảnh có bộ chọn điểm tâm + 3 khung xem trước' ($lib -match 'data-focus-picker' -and ([regex]::Matches($lib, 'data-frame-ratio=')).Count -ge 3 -and $lib -match 'name="focus_x" value="100"') ''
# Ảnh dọc chụp điện thoại qua đường upload web: JPEG 1600x1200 + EXIF Orientation=6, ô đỏ ở góc trên-trái dữ liệu lưu
# -> phải dựng đứng 1200x1600, ô đỏ sang góc trên-phải. (hex2bin: tránh dấu " bị PowerShell bỏ khi truyền cho exe)
& $php -r '$i=imagecreatetruecolor(1600,1200);imagefill($i,0,0,imagecolorallocate($i,255,255,255));imagefilledrectangle($i,0,0,199,199,imagecolorallocate($i,255,0,0));ob_start();imagejpeg($i,null,90);$j=ob_get_clean();file_put_contents($argv[1],hex2bin(''ffd8ffe10022457869660000''.''4d4d002a000000080001011200030000000100060000''.''00000000'').substr($j,2));' "$S\anh-doc.jpg"
$r = curl.exe -s -o NUL -w '%{http_code}|%{redirect_url}' -b $jarA -c $jarA -F "_csrf=$t" -F 'alt=Anh doc' -F "files[]=@$S\anh-doc.jpg;type=image/jpeg" "$B/admin/media/upload"
$rm = Sql "SELECT CONCAT(id,'|',path,'|',width,'|',height) FROM media ORDER BY id DESC LIMIT 1"
$rid, $rpath, $rw, $rh = $rm -split '\|'
Check 'Upload web ảnh EXIF xoay -> DB dựng đứng 1200x1600' ($r -like '303|*' -and $rw -eq '1200' -and $rh -eq '1600') "$r $rm"
$px = & $php -r '$o=imagecreatefromwebp($argv[1]);$f=function($x,$y)use($o){$c=imagecolorat($o,$x,$y);return (($c>>16)&255)>200&&(($c>>8)&255)<80?''red'':''white'';};echo imagesx($o),''x'',imagesy($o),'' TL='',$f(5,5),'' TR='',$f(imagesx($o)-6,5);' "src\thanhthanh\public\uploads\$($rpath -replace '/', '\')-1200.webp"
Check 'File WebP xoay đúng chiều (ô đỏ ở góc trên-phải)' ($px -eq '1200x1600 TL=white TR=red') $px
Post-Form "/admin/media/$rid/delete" $jarA @("_csrf=$t") | Out-Null
Check 'Dọn ảnh dọc thử' ((Sql "SELECT COUNT(*) FROM media WHERE id=$rid") -eq '0')
$r = Post-Form "/admin/posts/$postId" $jarA @("_csrf=$t", 'title=Bai thu nghiem admin', 'slug=bai-thu-nghiem-doi-slug', "body_md=$body", "category_id=$cat", 'status=published', "cover_media_id=$mid", 'cover_alt=')
Check 'Xuất bản có ảnh bìa nhưng thiếu alt -> 422' ($r -like '422|*') $r
$r = Post-Form "/admin/posts/$postId" $jarA @("_csrf=$t", 'title=Bai thu nghiem admin', 'slug=bai-thu-nghiem-doi-slug', "body_md=$body", "category_id=$cat", 'status=published', "cover_media_id=$mid", 'cover_alt=Mau do do thu nghiem')
Check 'Gắn ảnh bìa + alt -> lưu' ($r -like '303|*') $r
$pub = (curl.exe -s "$B/goc-nghe/bai-thu-nghiem-doi-slug") -join "`n"
Check 'Trang bài: ảnh bìa srcset + fetchpriority' ($pub -match 'srcset="[^"]*-480\.webp 480w' -and $pub -match 'fetchpriority="high"') ''
Check 'Trang bài: og:image là -og.jpg?v= 1200x630' ($pub -match 'og:image" content="[^"]*-og\.jpg\?v=\d+"' -and $pub -match 'og:image:width" content="1200"' -and $pub -match 'og:image:height" content="630"') ''
# File -og.jpg bị ghi đè khi đổi điểm tâm mà .htaccess cache ảnh 1 năm immutable -> URL og phải đổi theo (?v=)
$ogUrl1 = [regex]::Match($pub, 'og:image" content="([^"]+)"').Groups[1].Value
Start-Sleep -Milliseconds 1100
Post-Form "/admin/media/$mid" $jarA @("_csrf=$t", 'alt=Mau do do thu nghiem', 'focus_x=100', 'focus_y=60') | Out-Null
$ogUrl2 = [regex]::Match(((curl.exe -s "$B/goc-nghe/bai-thu-nghiem-doi-slug") -join "`n"), 'og:image" content="([^"]+)"').Groups[1].Value
Check 'Đổi điểm tâm -> URL og:image đổi (?v= mới, tránh cache cũ)' ($ogUrl1 -ne '' -and $ogUrl2 -ne '' -and $ogUrl1 -ne $ogUrl2) "$ogUrl1 -> $ogUrl2"
Post-Form "/admin/media/$mid" $jarA @("_csrf=$t", 'alt=Mau do do thu nghiem', 'focus_x=100', 'focus_y=0') | Out-Null
# Ảnh 1440x864 (5:3), khung bìa 16:9: cắt dọc; điểm tâm y=0 -> giữ mép trên = object-position "50% 0%"
Check 'Trang bài: ảnh bìa có object-position theo điểm tâm' ($pub -match 'fetchpriority="high"[^>]*style="object-position:50% 0%"') ''
# Ảnh thư viện chèn trong nội dung: thêm width/height + srcset (chống nhảy bố cục)
$bodyImg = "$body`n`n![Anh trong bai](/uploads/$mpath-960.webp)"
$r = Post-Form "/admin/posts/$postId" $jarA @("_csrf=$t", 'title=Bai thu nghiem admin', 'slug=bai-thu-nghiem-doi-slug', "body_md=$bodyImg", "category_id=$cat", 'status=published', "cover_media_id=$mid", 'cover_alt=Mau do do thu nghiem')
$pub2 = (curl.exe -s "$B/goc-nghe/bai-thu-nghiem-doi-slug") -join "`n"
Check 'Ảnh trong nội dung có width/height/srcset/lazy' ($r -like '303|*' -and $pub2 -match 'srcset="[^"]*-480\.webp 480w[^"]*" sizes="\(min-width: 760px\) 720px, 100vw" width="1440" height="864" alt="Anh trong bai"' -and $pub2 -match '<img loading="lazy" decoding="async" src="[^"]*-960\.webp"') ''
$sm = (curl.exe -s "$B/sitemap.xml") -join ''
Check 'Sitemap có image:image của ảnh bìa' ($sm -match "image:loc>[^<]*$([regex]::Escape($mpath))-1440\.webp") ''
$r = Post-Form "/admin/media/$mid/delete" $jarA @("_csrf=$t")
Check 'Không xóa được ảnh đang làm bìa' ((Sql "SELECT COUNT(*) FROM media WHERE id=$mid") -eq '1') $r
curl.exe -s -o NUL -b $jarA -c $jarA -F "_csrf=$t" -F 'alt=Anh thu 2' -F "files[]=@$S\anh-thu.jpg;type=image/jpeg" "$B/admin/media/upload" | Out-Null
$m2 = Sql "SELECT CONCAT(id,'|',path) FROM media ORDER BY id DESC LIMIT 1"
$mid2, $mpath2 = $m2 -split '\|'
Sql "UPDATE settings SET value='/uploads/$mpath2-960.webp' WHERE ``key``='default_og_image'" | Out-Null
Post-Form "/admin/media/$mid2/delete" $jarA @("_csrf=$t") | Out-Null
Check 'Không xóa được ảnh đang làm ảnh chia sẻ mặc định (link cỡ 960)' ((Sql "SELECT COUNT(*) FROM media WHERE id=$mid2") -eq '1')
Sql "UPDATE settings SET value='' WHERE ``key``='default_og_image'" | Out-Null
Post-Form "/admin/media/$mid2/delete" $jarA @("_csrf=$t") | Out-Null
Check 'Ảnh không còn dùng -> xóa được, xóa cả file WebP + -og.jpg' ((Sql "SELECT COUNT(*) FROM media WHERE id=$mid2") -eq '0' -and -not (Test-Path "src\thanhthanh\public\uploads\$($mpath2 -replace '/', '\')-960.webp") -and -not (Test-Path "src\thanhthanh\public\uploads\$($mpath2 -replace '/', '\')-og.jpg"))

Write-Output '== API cho tool đăng bài (tools/contents/fbpost) =='
# JSON ghi ra file rồi gửi --data-binary @file (PowerShell 5.1 làm mất dấu " khi truyền tham số cho exe)
function Api-Post([string]$path, [string]$jar, [string]$csrf, $obj) {
  [IO.File]::WriteAllText("$S\api-body.json", ($obj | ConvertTo-Json -Compress -Depth 5), (New-Object Text.UTF8Encoding $false))
  $code = curl.exe -s -o "$S\api-resp.json" -w '%{http_code}' -b $jar -c $jar -H 'Content-Type: application/json' -H 'Accept: application/json' -H "X-CSRF-Token: $csrf" --data-binary "@$S\api-body.json" "$B$path"
  $body = if (Test-Path "$S\api-resp.json") { [IO.File]::ReadAllText("$S\api-resp.json", [Text.Encoding]::UTF8) } else { '' }
  return @{ code = $code; body = $body; json = ($body | ConvertFrom-Json -ErrorAction SilentlyContinue) }
}
$code = curl.exe -s -o NUL -w '%{http_code}' -H 'Accept: application/json' "$B/admin/api/session"
Check 'API session chưa đăng nhập -> 401' ($code -eq '401') $code
$sess = (curl.exe -s -b $jarA -c $jarA -H 'Accept: application/json' "$B/admin/api/session") -join '' | ConvertFrom-Json
$apiCsrf = [string]$sess.data.csrf
$catId = ($sess.data.categories | Where-Object { $_.slug -eq 'hau-truong-bien-kich' }).id
Check 'API session: CSRF 64 ký tự + danh mục' ($sess.ok -and $apiCsrf -match '^[a-f0-9]{64}$' -and $catId -gt 0) ''
$ref = 'fbpost:e2e-' + [guid]::NewGuid().ToString('N')
$apiPost = @{ title = 'Bai tu tool dang bai'; body_md = "Noi dung bai tu tool.`n`n![Anh trong bai](/uploads/$mpath-960.webp)"; category_id = $catId;
  tags = 'tool, facebook'; excerpt = 'Tom tat tu tool'; status = 'published'; source = 'facebook'; source_ref = $ref;
  cover_media_id = [int]$mid; cover_alt = 'Anh bia tu tool' }
$r = Api-Post '/admin/api/posts' $jarA 'sai-csrf' $apiPost
Check 'API tạo bài thiếu/sai CSRF -> 403' ($r.code -eq '403') $r.code
$r = Api-Post '/admin/api/posts' $jarA $apiCsrf $apiPost
Check 'API: bài Facebook xuất bản khi CHƯA duyệt -> 422 (admin.md)' ($r.code -eq '422' -and $r.json.errors.status) "$($r.code) $($r.body)"
Check 'API: 422 không tạo bài rác' ((Sql "SELECT COUNT(*) FROM posts WHERE source_ref='$ref'") -eq '0') ''
$noAlt = $apiPost.Clone(); $noAlt.cover_alt = ''; $noAlt.approved_by_human = $true
$r = Api-Post '/admin/api/posts' $jarA $apiCsrf $noAlt
Check 'API: xuất bản có ảnh bìa thiếu alt -> 422 errors.cover_alt' ($r.code -eq '422' -and $r.json.errors.cover_alt) "$($r.code) $($r.body)"
$apiPost.approved_by_human = $true
$r = Api-Post '/admin/api/posts' $jarA $apiCsrf $apiPost
$newId = $r.json.data.id
Check 'API: người đã duyệt -> 201, đã xuất bản, có url' ($r.code -eq '201' -and $r.json.data.status -eq 'published' -and $r.json.data.url -match '/hau-truong-bien-kich/bai-tu-tool-dang-bai$') "$($r.code) $($r.body)"
$pubApi = (curl.exe -s "$B/hau-truong-bien-kich/bai-tu-tool-dang-bai") -join "`n"
Check 'API: bài hiện trên web, ảnh bìa có object-position, ảnh trong bài có width/height' ($pubApi -match '<h1[^>]*>Bai tu tool dang bai' -and $pubApi -match 'fetchpriority="high"[^>]*object-position' -and $pubApi -match 'width="1440" height="864" alt="Anh trong bai"') ''
Check 'API: lưu source=facebook + source_ref' ((Sql "SELECT CONCAT(source,'|',source_ref) FROM posts WHERE id=$newId") -eq "facebook|$ref") ''
$r = Api-Post '/admin/api/posts' $jarA $apiCsrf $apiPost
Check 'API: đăng lại cùng bài (source_ref) -> 409 kèm id bài cũ' ($r.code -eq '409' -and $r.json.id -eq $newId) "$($r.code) $($r.body)"
$upd = $apiPost.Clone(); $upd.title = 'Bai tu tool da sua'; $upd.slug = 'bai-tu-tool-dang-bai'
$r = Api-Post "/admin/api/posts/$newId" $jarA $apiCsrf $upd
Check 'API sửa bài -> 200, web đổi tiêu đề' ($r.code -eq '200' -and ((curl.exe -s "$B/hau-truong-bien-kich/bai-tu-tool-dang-bai") -join '') -match 'Bai tu tool da sua') "$($r.code) $($r.body)"
$sched = @{ title = 'Bai hen gio tu tool'; body_md = 'Noi dung hen gio'; category_id = $catId; status = 'published'; published_at = '2030-01-01 08:00';
  source = 'ai-draft'; source_ref = "$ref-hen-gio"; approved_by_human = $true }
$r = Api-Post '/admin/api/posts' $jarA $apiCsrf $sched
$code = curl.exe -s -o NUL -w '%{http_code}' "$B/hau-truong-bien-kich/bai-hen-gio-tu-tool"
Check 'API hẹn giờ -> 201 status_key=scheduled, web chưa hiện (404)' ($r.code -eq '201' -and $r.json.data.status_key -eq 'scheduled' -and $code -eq '404') "$($r.code) $($r.json.data.status_key) web=$code"
$r = Api-Post '/admin/api/posts' $jarA $apiCsrf @{ title = ''; body_md = 'x'; status = 'draft' }
Check 'API thiếu tiêu đề -> 422' ($r.code -eq '422' -and $r.json.errors.title) "$($r.code) $($r.body)"
Check 'audit_log ghi via api-tool' ([int](Sql "SELECT COUNT(*) FROM audit_log WHERE details LIKE '%api-tool%'") -ge 3) ''
# Bấm "Duyệt" 2 lần / 2 máy: 2 yêu cầu tạo bài CÙNG source_ref chạy song song, từ 2 phiên khác nhau
# (curl --parallel dùng chung cookie -> gửi cookie phiên riêng bằng header cho từng yêu cầu)
$sessE = (curl.exe -s -b $jarE -c $jarE -H 'Accept: application/json' "$B/admin/api/session") -join '' | ConvertFrom-Json
$sidA = [regex]::Match([IO.File]::ReadAllText($jarA), 'tt_sess\s+(\S+)').Groups[1].Value
$sidE = [regex]::Match([IO.File]::ReadAllText($jarE), 'tt_sess\s+(\S+)').Groups[1].Value
$emptyBefore = Sql "SELECT COUNT(*) FROM posts WHERE title=''"
foreach ($case in @(@('Bai dua song song', 'Bai dua song song'), @('Bai dua A', 'Bai dua B'))) {
  $raceRef = 'fbpost:race-' + [guid]::NewGuid().ToString('N')
  $pargs = @('-s', '--parallel', '--parallel-immediate')
  foreach ($k in 0, 1) {
    $who = @(@($sidA, $apiCsrf), @($sidE, [string]$sessE.data.csrf))[$k]
    [IO.File]::WriteAllText("$S\race$k.json", (@{ title = $case[$k]; body_md = 'x'; category_id = $catId; status = 'draft'; source = 'facebook'; source_ref = $raceRef } | ConvertTo-Json -Compress), (New-Object Text.UTF8Encoding $false))
    if ($k -eq 1) { $pargs += '--next' }
    $pargs += @('-o', "$S\race$k.out", '-w', '%{http_code}\n', '-H', "Cookie: tt_sess=$($who[0])", '-H', 'Content-Type: application/json', '-H', 'Accept: application/json',
                '-H', "X-CSRF-Token: $($who[1])", '--data-binary', "@$S\race$k.json", "$B/admin/api/posts")
  }
  $codes = (curl.exe @pargs) | Where-Object { $_ } | Sort-Object
  Check "Song song cùng source_ref ($($case[0]) / $($case[1])) -> 1 bài (201), yêu cầu kia 409, không lỗi 500" (($codes -join ',') -eq '201,409' -and (Sql "SELECT COUNT(*) FROM posts WHERE source_ref='$raceRef'") -eq '1') ($codes -join ',')
}
Check 'Song song: không để lại bản nháp rỗng (transaction)' ((Sql "SELECT COUNT(*) FROM posts WHERE title=''") -eq $emptyBefore) ''
# Bài trùng nằm trong thùng rác: báo rõ, không 500 (source_ref là UNIQUE nên bài trong thùng rác vẫn giữ chỗ)
$tRef = 'fbpost:trash-' + [guid]::NewGuid().ToString('N')
$r = Api-Post '/admin/api/posts' $jarA $apiCsrf @{ title = 'Bai se vao thung rac'; body_md = 'x'; category_id = $catId; status = 'draft'; source = 'facebook'; source_ref = $tRef }
Post-Form "/admin/posts/$($r.json.data.id)/trash" $jarA @("_csrf=$(Csrf (Get-Page '/admin/posts' $jarA))") | Out-Null
$r = Api-Post '/admin/api/posts' $jarA $apiCsrf @{ title = 'Bai dang lai'; body_md = 'x'; category_id = $catId; status = 'draft'; source = 'facebook'; source_ref = $tRef }
Check 'Đăng lại bài đang trong thùng rác -> 409 trashed + thông báo khôi phục' ($r.code -eq '409' -and $r.json.trashed -and $r.json.error -match 'thùng rác') "$($r.code) $($r.body)"

Write-Output '== Phim, trang tĩnh, cài đặt =='
$fe = Get-Page '/admin/films/1' $jarA
$t = Csrf $fe
$r = Post-Form '/admin/films/1/milestones' $jarA @("_csrf=$t", 'label=Moc thu', 'title=Them moc kiem thu')
$newM = Sql "SELECT id FROM film_milestones WHERE film_id=1 ORDER BY sort_order DESC LIMIT 1"
Check 'Thêm mốc -> mốc cuối' ((Sql "SELECT title FROM film_milestones WHERE id=$newM") -eq 'Them moc kiem thu') $r
$rankSql = "SELECT COUNT(*) FROM film_milestones a JOIN film_milestones b ON b.id=$newM WHERE a.film_id=1 AND (a.sort_order < b.sort_order OR (a.sort_order = b.sort_order AND a.id < b.id))"
$before = Sql $rankSql
Post-Form "/admin/milestones/$newM/move" $jarA @("_csrf=$t", 'direction=up') | Out-Null
$after = Sql $rankSql
Check 'Đưa mốc lên -> thứ hạng giảm 1' ([int]$after -eq [int]$before - 1) "$before -> $after"
Post-Form "/admin/milestones/$newM/delete" $jarA @("_csrf=$t") | Out-Null
Check 'Xóa mốc' ((Sql "SELECT COUNT(*) FROM film_milestones WHERE id=$newM") -eq '0')
$fields = @("_csrf=$t", 'title=ĐỢI', 'title_en=WAITING', 'status=released', 'is_featured=1', 'badge=Nhan thu nghiem', 'year=2026', 'duration_min=8', 'actors=Thanh Thanh', 'related_tag=phimngandoi', "poster_media_id=$mid")
$r = Post-Form '/admin/films/1' $jarA $fields
Check 'Sửa phim + gắn poster -> lưu' ($r -like '303|*' -and (Sql "SELECT CONCAT(badge,'|',poster_media_id) FROM films WHERE id=1") -eq "Nhan thu nghiem|$mid") $r
$fp = (curl.exe -s "$B/phim/doi") -join ''
Check 'Trang phim dùng poster thật' ($fp -match "$([regex]::Escape($mpath))-960\.webp")
$r = Post-Form '/admin/films/1' $jarA @("_csrf=$t", 'title=ĐỢI', 'status=released', 'year=1800')
Check 'Năm ngoài khoảng -> 422' ($r -like '422|*') $r
$pe = Get-Page '/admin/pages/1' $jarA
$r = Post-Form '/admin/pages/1' $jarA @("_csrf=$(Csrf $pe)", 'title=Giới thiệu', 'body_md=Noi dung gioi thieu moi', 'status=published', 'translation_of=1')
Check 'Trang trỏ bản dịch về chính nó -> 422' ($r -like '422|*') $r
$r = Post-Form '/admin/pages/1' $jarA @("_csrf=$(Csrf $pe)", 'title=Giới thiệu', 'body_md=Noi dung gioi thieu moi', 'status=published')
Check 'Sửa trang tĩnh -> nội dung ra ngoài' ($r -like '303|*' -and ((curl.exe -s "$B/gioi-thieu") -join '') -match 'Noi dung gioi thieu moi') $r
$se = Get-Page '/admin/settings' $jarA
$sf = @("_csrf=$(Csrf $se)", 'site_name=Thanh Thanh', 'site_tagline=Biên kịch', 'mission=Đưa lát cắt đời sống người ngồi xe lăn vào điện ảnh.', 'author_name=Thanh Thanh', 'social_facebook=http://khong-https.vn')
$r = Post-Form '/admin/settings' $jarA $sf
Check 'Cài đặt: link không https -> 422' ($r -like '422|*') $r
$code = (Post-Form '/admin/settings' $jarE @("_csrf=$(Csrf (Get-Page '/admin' $jarE))", 'site_name=Hack'))
Check 'Editor POST cài đặt -> 403' ($code -like '403|*') $code

Write-Output '== Thùng rác, audit, đăng xuất =='
$r = Post-Form "/admin/posts/$postId/trash" $jarA @("_csrf=$t")
$code = curl.exe -s -o NUL -w '%{http_code}' "$B/goc-nghe/bai-thu-nghiem-doi-slug"
Check 'Bỏ vào thùng rác -> bài 404 ngoài web' ($code -eq '404') $code
Post-Form "/admin/posts/$postId/restore" $jarA @("_csrf=$t") | Out-Null
$code = curl.exe -s -o NUL -w '%{http_code}' "$B/goc-nghe/bai-thu-nghiem-doi-slug"
Check 'Khôi phục -> bài 200 lại' ($code -eq '200') $code
Check 'audit_log có login/publish/trash/restore/upload' ([int](Sql "SELECT COUNT(DISTINCT action) FROM audit_log WHERE action IN ('login','publish','trash','restore','upload')") -eq 5)
$r = Post-Form '/admin/logout' $jarA @("_csrf=$t")
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jarA "$B/admin"
Check 'Đăng xuất -> /admin chuyển về đăng nhập' ($r -like '303|*' -and $code -eq '303') "$r / $code"
$uidA = Sql "SELECT id FROM users WHERE email='qa-admin@local.test'"
Login $jarA 'qa-admin@local.test' $pwA $true | Out-Null
Login $jarR 'qa-admin@local.test' $pwA $true | Out-Null
Check 'Có token ghi nhớ trên 2 thiết bị' ([int](Sql "SELECT COUNT(*) FROM remember_tokens WHERE user_id=$uidA AND expires_at > NOW()") -ge 2)
$r = Post-Form '/admin/logout-all' $jarA @("_csrf=$(Csrf (Get-Page '/admin' $jarA))")
Check 'Đăng xuất mọi thiết bị -> thu hồi hết token' ($r -like '303|*' -and (Sql "SELECT COUNT(*) FROM remember_tokens WHERE user_id=$uidA") -eq '0') $r
Login $jarR 'qa-admin@local.test' $pwA $true | Out-Null
$pwA | & $php tools\create_user.php --site=thanhthanh --email=qa-admin@local.test --reset 2>$null | Out-Null
Check 'create_user --reset -> thu hồi token ghi nhớ' ((Sql "SELECT COUNT(*) FROM remember_tokens WHERE user_id=$uidA") -eq '0')

Write-Output '== Giới hạn đăng nhập sai =='
Sql 'DELETE FROM login_attempts;' | Out-Null
$last = ''
foreach ($i in 1..6) { $last = Login "$S\jar-x.txt" 'qa-editor@local.test' 'sai-mat-khau-xyz' }
$page = Get-Page '/admin/login' "$S\jar-x.txt"
$r = Login "$S\jar-x.txt" 'qa-editor@local.test' $pwE
Check 'Sau 5 lần sai, mật khẩu đúng cũng bị chặn 15 phút' ($r -like '422|*') $r
$r = Login "$S\jar-y.txt" 'qa-admin@local.test' $pwA
Check 'Tài khoản KHÁC từ cùng IP vẫn đăng nhập được (khóa theo cặp IP+email)' ($r -eq "303|$B/admin") $r
Sql 'DELETE FROM login_attempts;' | Out-Null

Sql "DELETE FROM users WHERE email LIKE '%@local.test';" | Out-Null
Write-Output "`nKẾT QUẢ: $pass đạt, $fail lỗi (đã xóa tài khoản thử; chạy lại seed + dev/sample.sql để dọn dữ liệu thử)"
if ($fail -gt 0) { exit 1 }
