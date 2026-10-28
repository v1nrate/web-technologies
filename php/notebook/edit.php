<?php
function edit_page(): string
{
    $pdo = db();
    $contacts = $pdo->query('SELECT id,surname,name FROM contacts ORDER BY surname ASC, name ASC, id ASC')->fetchAll();
    $html = '<section class="form-section"><h2>Редактирование записи</h2>';
    if (!$contacts) return $html . '<p class="empty">Контактов для редактирования пока нет.</p></section>';
    $ids = array_map('intval', array_column($contacts, 'id'));
    $selected = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!in_array($selected, $ids, true)) $selected = $ids[0];
    $html .= '<div class="contact-links" aria-label="Выберите контакт">';
    foreach ($contacts as $c) {
        $active = (int)$c['id'] === $selected ? ' selected' : '';
        $html .= '<a class="contact-link' . $active . '" href="index.php?action=edit&amp;id=' . (int)$c['id'] . '">' . e($c['surname'] . ' ' . $c['name']) . '</a>';
    }
    $html .= '</div>';
    $stmt = $pdo->prepare('SELECT * FROM contacts WHERE id = ?');
    $stmt->execute([$selected]);
    $values = $stmt->fetch();
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values = contact_fields($_POST);
        $error = valid_csrf() ? contact_error($values) : 'Ошибка проверки формы. Обновите страницу.';
        if ($error === null) {
            try {
                $data = db_values($values);
                $data['id'] = $selected;
                $stmt = $pdo->prepare('UPDATE contacts SET surname=:surname,name=:name,patronymic=:patronymic,gender=:gender,birth_date=:birth_date,phone=:phone,address=:address,email=:email,comment=:comment WHERE id=:id');
                $stmt->execute($data);
                $message = '<p class="notice success">Запись изменена</p>';
            } catch (PDOException $ex) {
                $message = '<p class="notice error">Ошибка: запись не изменена</p>';
            }
        } else {
            $message = '<p class="notice error">Ошибка: запись не изменена. ' . e($error) . '</p>';
        }
    }
    return $html . $message . contact_form($values, 'Сохранить изменения') . '</section>';
}
