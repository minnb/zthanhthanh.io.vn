<?php
/**
 * Layout cho trang dev (chỉ local): dùng layout chính + vài style bảng chẩn đoán.
 *
 * @var string $content
 */
$content = '<style>.dev-table{width:100%;border-collapse:collapse;background:var(--card);margin-top:12px}'
    . '.dev-table th,.dev-table td{text-align:left;padding:8px 10px;border-bottom:1px solid var(--line);font-size:15px}'
    . '.ok{color:var(--success);font-weight:600}.bad{color:var(--danger);font-weight:600}.dev h2{margin-top:32px}</style>'
    . '<div class="container dev section">' . $content . '</div>';
echo \App\Core\View::partial('layouts/base', get_defined_vars() + ['noindex' => true]);
