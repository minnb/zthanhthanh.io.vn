<?php
declare(strict_types=1);

/*
 * Danh sách URL cho tools/smoke.php: [path, mã HTTP kỳ vọng, (tùy chọn) Location kỳ vọng].
 * Các URL bài viết/tag dựa trên database/dev/sample.sql (chỉ local). Bước 6 có bài thật thì cập nhật slug.
 */
return [
    ['/', 200],
    ['/bai-viet', 200],
    ['/hau-truong-bien-kich', 200],
    ['/goc-nghe', 200],
    ['/dien-thuyet', 200],
    ['/hau-truong-bien-kich/khong-de-nguoi-khac-quyet-dinh-gioi-han-cua-ban-than', 200],
    ['/goc-nghe/dung-giua-cho-rau-rao-ban-sach-account', 200],
    ['/goc-nghe/giay-phep-phan-loai-phim', 200],
    ['/chu-de/phimngandoi', 200],
    ['/phim', 200],
    ['/phim/doi', 200],
    ['/phim/dua-con-den-tu-coi-am', 200],
    ['/gioi-thieu', 200],
    ['/lien-he', 200],
    ['/goc-sang-tac', 200],
    ['/tu-sach', 200],
    ['/en/press-kit', 200],

    // SEO kỹ thuật
    ['/sitemap.xml', 200],
    ['/robots.txt', 200],
    ['/feed.xml', 200],

    // Chuyển hướng URL chuẩn
    ['/phim/', 301, '/phim'],
    ['/goc-nghe?trang=1', 301, '/goc-nghe'],
    ['/bai-cu-thu-nghiem', 301, '/goc-nghe/giay-phep-phan-loai-phim'],   // bảng redirects (dev/sample.sql)

    // Không tồn tại / không công khai
    ['/khong-ton-tai', 404],
    ['/hau-truong-bien-kich/giay-phep-phan-loai-phim', 404],   // sai danh mục
    ['/hau-truong-bien-kich/bai-nhap-khong-duoc-hien', 404],   // bài nháp
    ['/chu-de/khong-co', 404],
    ['/phim/xyz', 404],
    ['/goc-nghe?trang=abc', 404],
    ['/goc-nghe?trang=99', 404],
    ['/bai-viet?trang=99', 404],
];
