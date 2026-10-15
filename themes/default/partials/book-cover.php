<?php
/**
 * Bìa sách bằng chữ (màu theo thứ tự sách --book-1..10). Chỉ để trang trí: tên sách đã có ở chữ bên cạnh.
 *
 * @var array<string, mixed> $book
 */
$tone = ((int) ($book['id'] ?? 1) - 1) % 10 + 1;
$title = (string) ($book['title'] ?? '');
// Tên dài kiểu "Story: Substance, Structure..." -> chỉ in phần trước dấu ":" / "(" lên bìa
$parts = preg_split('/[:(]/u', $title) ?: [$title];
$short = trim((string) $parts[0]);
?>
<div class="book-cover" style="--bc: var(--book-<?= $tone ?>)" aria-hidden="true">
  <span class="book-cover__title"><?= e($short !== '' ? $short : $title) ?></span>
  <span class="book-cover__author"><?= e((string) ($book['author'] ?? '')) ?></span>
</div>
