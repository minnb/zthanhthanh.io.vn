<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\AdminFilm;
use App\Models\Media;

/** Phim + dòng thời gian (milestones). */
final class FilmController extends AdminController
{
    public function index(): string
    {
        $this->guard('editor');
        return $this->render('films/index', ['title' => 'Phim', 'films' => AdminFilm::all()]);
    }

    public function edit(string $id): string
    {
        $this->guard('editor');
        $film = AdminFilm::find((int) $id) ?? $this->notFound();
        return $this->editor($film, []);
    }

    public function update(string $id): string
    {
        $this->guard('editor');
        $film = AdminFilm::find((int) $id) ?? $this->notFound();

        $errors = [];
        $data = [];
        foreach (AdminFilm::FIELDS as $field => $type) {
            $data[$field] = match ($type) {
                'int' => Request::str($field) === '' ? '' : Request::int($field, -1),
                'bool' => Request::bool($field),
                'text' => Request::raw($field),
                default => mb_substr(Request::str($field), 0, 255),
            };
        }
        if ($data['title'] === '') {
            $errors['title'] = 'Phim cần có tên.';
        }
        if (!isset(AdminFilm::STATUSES[(string) $data['status']])) {
            $errors['status'] = 'Trạng thái không hợp lệ.';
        }
        foreach (['duration_min' => [1, 600], 'year' => [1900, 2100]] as $field => [$min, $max]) {
            if ($data[$field] !== '' && ($data[$field] < $min || $data[$field] > $max)) {
                $errors[$field] = "Giá trị phải từ {$min} đến {$max}.";
            }
        }
        if ($data['poster_media_id'] !== '' && Media::find((int) $data['poster_media_id']) === null) {
            $errors['poster_media_id'] = 'Ảnh poster không còn tồn tại.';
        }
        if ($data['trailer_url'] !== '' && !preg_match('#^https://#i', (string) $data['trailer_url'])) {
            $errors['trailer_url'] = 'Link trailer phải bắt đầu bằng https://';
        }
        if ($data['related_tag'] !== '' && !preg_match('/^[a-z0-9-]+$/', (string) $data['related_tag'])) {
            $errors['related_tag'] = 'Tag chỉ gồm chữ thường không dấu, số và gạch nối.';
        }
        if ($errors !== []) {
            http_response_code(422);
            $form = $data + $film;
            $form['poster'] = $data['poster_media_id'] !== '' ? Media::find((int) $data['poster_media_id']) : null;
            return $this->editor($form, $errors);
        }

        AdminFilm::update((int) $film['id'], $data);
        $this->changed('update', 'film', (int) $film['id']);
        $this->redirect('/admin/films/' . $film['id'], 'Đã lưu phim.');
    }

    public function addMilestone(string $id): string
    {
        $this->guard('editor');
        $film = AdminFilm::find((int) $id) ?? $this->notFound();
        [$label, $title, $desc] = self::milestoneInput();
        if ($label === '' || $title === '') {
            $this->redirect('/admin/films/' . $film['id'] . '#milestones', 'Mốc cần có nhãn và tiêu đề.', 'error');
        }
        AdminFilm::addMilestone((int) $film['id'], $label, $title, $desc, Request::bool('is_highlight'));
        $this->changed('create', 'film_milestone', (int) $film['id']);
        $this->redirect('/admin/films/' . $film['id'] . '#milestones', 'Đã thêm mốc.');
    }

    public function updateMilestone(string $id): string
    {
        $this->guard('editor');
        $m = AdminFilm::milestone((int) $id) ?? $this->notFound();
        [$label, $title, $desc] = self::milestoneInput();
        if ($label === '' || $title === '') {
            $this->redirect('/admin/films/' . $m['film_id'] . '#milestones', 'Mốc cần có nhãn và tiêu đề.', 'error');
        }
        AdminFilm::updateMilestone((int) $m['id'], $label, $title, $desc, Request::bool('is_highlight'));
        $this->changed('update', 'film_milestone', (int) $m['id']);
        $this->redirect('/admin/films/' . $m['film_id'] . '#milestone-' . $m['id'], 'Đã lưu mốc.');
    }

    public function deleteMilestone(string $id): string
    {
        $this->guard('editor');
        $m = AdminFilm::milestone((int) $id) ?? $this->notFound();
        AdminFilm::deleteMilestone((int) $m['id']);
        $this->changed('delete', 'film_milestone', (int) $m['id'], ['title' => $m['title']]);
        $this->redirect('/admin/films/' . $m['film_id'] . '#milestones', 'Đã xóa mốc.');
    }

    public function moveMilestone(string $id): string
    {
        $this->guard('editor');
        $m = AdminFilm::milestone((int) $id) ?? $this->notFound();
        AdminFilm::moveMilestone((int) $m['id'], Request::str('direction') === 'up' ? 'up' : 'down');
        $this->changed('move', 'film_milestone', (int) $m['id']);
        $this->redirect('/admin/films/' . $m['film_id'] . '#milestone-' . $m['id']);
    }

    /**
     * @param array<string, mixed> $film
     * @param array<string, string> $errors
     */
    private function editor(array $film, array $errors): string
    {
        return $this->render('films/edit', [
            'title' => 'Phim: ' . $film['title'],
            'film' => $film,
            'errors' => $errors,
            'statuses' => AdminFilm::STATUSES,
            'milestones' => AdminFilm::milestones((int) $film['id']),
        ]);
    }

    /** @return array{0: string, 1: string, 2: string} */
    private static function milestoneInput(): array
    {
        return [
            mb_substr(Request::str('label'), 0, 60),
            mb_substr(Request::str('title'), 0, 160),
            mb_substr(Request::str('description'), 0, 255),
        ];
    }
}
