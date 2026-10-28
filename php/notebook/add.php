<?php
function add_page(): string
{
    $values = contact_fields([]);
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values = contact_fields($_POST);
        $error = valid_csrf() ? contact_error($values) : 'Ошибка проверки формы. Обновите страницу.';
        if ($error === null) {
            try {
                $stmt = db()->prepare('INSERT INTO contacts (surname,name,patronymic,gender,birth_date,phone,address,email,comment) VALUES (:surname,:name,:patronymic,:gender,:birth_date,:phone,:address,:email,:comment)');
                $stmt->execute(db_values($values));
                $message = '<p class="notice success">Запись добавлена</p>';
                $values = contact_fields([]);
            } catch (PDOException $ex) {
                $message = '<p class="notice error">Ошибка: запись не добавлена</p>';
            }
        } else {
            $message = '<p class="notice error">Ошибка: запись не добавлена. ' . e($error) . '</p>';
        }
    }
    return '<section class="form-section"><h2>Добавление записи</h2>' . $message . contact_form($values, 'Добавить запись') . '</section>';
}
