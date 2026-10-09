<?php
/**
 * Trang chủ: render lần lượt các khối trong $blocks (bỏ qua khối không tồn tại trong theme).
 *
 * @var list<string> $blocks
 */
foreach ($blocks as $block) {
    $block = (string) $block;
    if (preg_match('/^[a-z0-9-]+$/', $block) && is_file(\App\Core\View::path('blocks/' . $block))) {
        echo partial('blocks/' . $block, get_defined_vars());
    }
}
