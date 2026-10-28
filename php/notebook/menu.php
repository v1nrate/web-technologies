<?php
function menu(): string
{
    $section = $GLOBALS['section'] ?? 'view';
    $sort = $GLOBALS['sort'] ?? 'added';
    $items = ['view' => 'Просмотр', 'add' => 'Добавление записи', 'edit' => 'Редактирование записи', 'delete' => 'Удаление записи'];
    $html = '<nav class="main-menu" aria-label="Главное меню">';
    foreach ($items as $key => $label) {
        $active = $section === $key ? ' active' : '';
        $html .= '<a class="menu-link' . $active . '" href="index.php?action=' . $key . '">' . e($label) . '</a>';
    }
    $html .= '</nav>';
    if ($section === 'view') {
        $html .= '<nav class="sub-menu" aria-label="Сортировка">';
        foreach (['added' => 'По порядку добавления', 'surname' => 'По фамилии', 'birth' => 'По дате рождения'] as $key => $label) {
            $active = $sort === $key ? ' active' : '';
            $html .= '<a class="menu-link' . $active . '" href="index.php?action=view&amp;sort=' . $key . '">' . e($label) . '</a>';
        }
        $html .= '</nav>';
    }
    return $html;
}
