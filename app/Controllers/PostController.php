<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Markdown;
use App\Core\Seo;
use App\Core\View;
use App\Models\Category;
use App\Models\Post;
use App\Models\Site;
use App\Models\Tag;

final class PostController
{
    /** Danh sách bài của một danh mục: /hau-truong-bien-kich, /goc-nghe... */
    public function category(string $category): string
    {
        $cat = Category::findBySlug($category) ?? throw HttpException::notFound();
        $path = '/' . $cat['slug'];
        $page = Pagination::currentPage($path);
        $result = Post::paginate((int) $cat['id'], null, $page);
        Pagination::assertInRange($page, $result['pages']);

        return View::render('pages/post-list', [
            'title' => (string) ($cat['seo_title'] ?: $cat['name']) . ($page > 1 ? ' – Trang ' . $page : ''),
            'description' => (string) ($cat['seo_desc'] ?: $cat['description']),
            // Danh mục chưa có bài là trang mỏng -> không cho index (khớp sitemap)
            'noindex' => $result['total'] === 0,
            'canonicalPath' => Pagination::path($path, $page),
            'heading' => (string) $cat['name'],
            'intro' => (string) $cat['description'],
            'eyebrow' => 'Chuyên mục',
            'basePath' => $path,
            'result' => $result,
            'breadcrumb' => [['label' => 'Trang chủ', 'url' => '/'], ['label' => (string) $cat['name']]],
        ]);
    }

    /** Chi tiết bài: /{danh-muc}/{slug} */
    public function show(string $category, string $slug): string
    {
        $post = Post::findPublished($category, $slug) ?? throw HttpException::notFound();
        $description = Seo::description((string) ($post['seo_desc'] ?: ($post['excerpt'] ?: Markdown::excerpt($post['text'], 155))));

        return View::render('pages/post-single', [
            'title' => (string) ($post['seo_title'] ?: $post['title']),
            'description' => $description,
            'noindex' => (bool) $post['noindex'],
            'canonicalPath' => (string) $post['url'],
            'ogType' => 'article',
            'publishedAt' => (string) $post['published_at'],
            'modifiedAt' => (string) ($post['updated_at'] ?: $post['published_at']),
            'jsonLd' => [Seo::blogPostingSchema($post, Site::info(), $description)],
            'post' => $post,
            'related' => Post::related($post, 3),
            'breadcrumb' => [
                ['label' => 'Trang chủ', 'url' => '/'],
                ['label' => (string) $post['category_name'], 'url' => '/' . $post['category_slug']],
                ['label' => (string) $post['title']],
            ],
        ]);
    }

    /** Bài theo chủ đề: /chu-de/{tag} */
    public function tag(string $tag): string
    {
        $t = Tag::findBySlug($tag) ?? throw HttpException::notFound();
        $path = '/chu-de/' . $t['slug'];
        $page = Pagination::currentPage($path);
        $result = Post::paginate(null, (int) $t['id'], $page);
        Pagination::assertInRange($page, $result['pages']);

        return View::render('pages/post-list', [
            'title' => 'Chủ đề #' . $t['name'] . ($page > 1 ? ' – Trang ' . $page : ''),
            'description' => 'Các bài viết của Thanh Thanh về chủ đề #' . $t['name'] . '.',
            // Trang tag ít bài là nội dung mỏng -> không cho index (quy tắc SEO)
            'noindex' => $result['total'] < 3,
            'canonicalPath' => Pagination::path($path, $page),
            'heading' => '#' . $t['name'],
            'intro' => '',
            'eyebrow' => 'Chủ đề',
            'basePath' => $path,
            'result' => $result,
            'breadcrumb' => [['label' => 'Trang chủ', 'url' => '/'], ['label' => '#' . $t['name']]],
        ]);
    }
}
