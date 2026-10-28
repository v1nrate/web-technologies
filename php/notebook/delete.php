<?php
function delete_page(): string
{
    $pdo = db();
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $id = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);
        if (!valid_csrf() || !$id) {
            $message = '<p class="notice error">Ошибка: запись не удалена</p>';
        } else {
            $stmt = $pdo->prepare('SELECT surname FROM contacts WHERE id = ?');
            $stmt->execute([$id]);
            $surname = $stmt->fetchColumn();
            if ($surname !== false) {
                $stmt = $pdo->prepare('DELETE FROM contacts WHERE id = ?');
                $stmt->execute([$id]);
                $message = '<p class="notice success">Запись с фамилией ' . e((string)$surname) . ' удалена</p>';
            } else {
                $message = '<p class="notice error">Ошибка: запись не найдена</p>';
            }
        }
    }
    $contacts = $pdo->query('SELECT id,surname,name,patronymic FROM contacts ORDER BY surname ASC,name ASC,id ASC')->fetchAll();
    $html = '<section class="form-section"><h2>Удаление записи</h2>' . $message;
    if (!$contacts) return $html . '<p class="empty">Контактов для удаления пока нет.</p></section>';
    $selected = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $html .= '<p class="muted">Выберите контакт, который хотите удалить.</p><div class="contact-links">';
    foreach ($contacts as $c) {
        $initials = mb_substr($c['name'],0,1) . '.' . (!empty($c['patronymic']) ? mb_substr($c['patronymic'],0,1) . '.' : '');
        $active = (int)$c['id'] === $selected ? ' selected' : '';
        $html .= '<a class="contact-link' . $active . '" href="index.php?action=delete&amp;id=' . (int)$c['id'] . '">' . e($c['surname'] . ' ' . $initials) . '</a>';
    }
    $html .= '</div>';
    if ($selected && in_array($selected, array_map('intval', array_column($contacts,'id')), true)) {
        $html .= '<div class="confirm-box"><p>Удалить выбранную запись? Это действие нельзя отменить.</p><form method="post" action="index.php?action=delete"><input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '"><input type="hidden" name="delete_id" value="' . $selected . '"><button class="danger-btn" type="submit">Удалить запись</button> <a class="cancel-link" href="index.php?action=delete">Отмена</a></form></div>';
    }
    return $html . '</section>';
}
