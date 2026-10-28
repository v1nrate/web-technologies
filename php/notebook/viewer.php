<?php
function viewer(string $sort, int $page): string
{
    $order = [
        'added' => 'id ASC',
        'surname' => 'surname ASC, name ASC, id ASC',
        'birth' => 'birth_date IS NULL ASC, birth_date ASC, surname ASC, id ASC',
    ][$sort] ?? 'id ASC';
    $pdo = db();
    $total = (int)$pdo->query('SELECT COUNT(*) FROM contacts')->fetchColumn();
    $pages = max(1, (int)ceil($total / 10));
    $page = min(max(1, $page), $pages);
    $offset = ($page - 1) * 10;
    $stmt = $pdo->query("SELECT * FROM contacts ORDER BY $order LIMIT 10 OFFSET $offset");
    $rows = $stmt->fetchAll();
    $html = '<section><div class="section-heading"><h2>Список контактов</h2><span class="muted">Всего записей: ' . $total . '</span></div>';
    if (!$rows) return $html . '<p class="empty">Пока нет контактов. Перейдите в раздел «Добавление записи».</p></section>';
    $html .= '<div class="table-scroll"><table><thead><tr><th>№</th><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Пол</th><th>Дата рождения</th><th>Телефон</th><th>Адрес</th><th>Email</th><th>Комментарий</th></tr></thead><tbody>';
    foreach ($rows as $i => $row) {
        $html .= '<tr><td>' . ($offset + $i + 1) . '</td>';
        foreach (['surname','name','patronymic','gender','birth_date','phone','address','email','comment'] as $key) {
            $value = (string)($row[$key] ?? '');
            $html .= '<td>' . ($value !== '' ? nl2br(e($value)) : '<span class="muted">—</span>') . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    if ($pages > 1) {
        $html .= '<nav class="pagination" aria-label="Страницы">';
        for ($i = 1; $i <= $pages; $i++) {
            $active = $i === $page ? ' current' : '';
            $html .= '<a class="page-link' . $active . '" href="index.php?action=view&amp;sort=' . e($sort) . '&amp;page=' . $i . '"' . ($active ? ' aria-current="page"' : '') . '>' . $i . '</a>';
        }
        $html .= '</nav>';
    }
    return $html . '</section>';
}
