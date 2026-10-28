<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/menu.php';
require_once __DIR__ . '/viewer.php';
require_once __DIR__ . '/form.php';
require_once __DIR__ . '/add.php';
require_once __DIR__ . '/edit.php';
require_once __DIR__ . '/delete.php';

$section = (string)($_GET['action'] ?? 'view');
if (!in_array($section, ['view','add','edit','delete'], true)) $section = 'view';
$sort = (string)($_GET['sort'] ?? 'added');
if (!in_array($sort, ['added','surname','birth'], true)) $sort = 'added';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;

try {
    switch ($section) {
        case 'add': $content = add_page(); break;
        case 'edit': $content = edit_page(); break;
        case 'delete': $content = delete_page(); break;
        default: $content = viewer($sort, $page);
    }
} catch (PDOException $ex) {
    http_response_code(500);
    $content = '<section><h2>Ошибка подключения к базе данных</h2><p class="notice error">Проверьте параметры в db.php и импорт файла database.sql.</p></section>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notebook — Записная книжка</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
<div class="layout">
    <header class="site-header"><div class="header-inner"><span class="eyebrow">PHP Part 1 · Домашняя работа 5.1</span><h1>Notebook</h1><p>Записная книжка контактов</p></div></header>
    <main class="main-content">
        <?= menu() ?>
        <?= $content ?>
    </main>
    <footer class="site-footer">Задание для самостоятельной работы · PHP Part 1</footer>
</div>
</body>
</html>
