<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title ?? 'Мой блог') ?></title>
    <link rel="stylesheet" href="<?= h(BASE_PATH) ?>/styles/styles.css">
</head>
<body>
<table class="layout">
    <tr>
        <td colspan="2" class="header">Мой блог</td>
    </tr>
    <tr>
        <td class="mainContent"><?= $content ?></td>
        <td width="300px" class="sidebar">
            <div class="sidebarHeader">Меню</div>
            <ul>
                <li><a href="<?= h(BASE_PATH) ?>/">Главная страница</a></li>
                <li><a href="<?= h(BASE_PATH) ?>/hello/Руслан">Приветствие</a></li>
                <li><a href="<?= h(BASE_PATH) ?>/bye/Руслан">Прощание</a></li>
                <li><a href="<?= h(BASE_PATH) ?>/articles/1">Статья 1</a></li>
                <li><a href="<?= h(BASE_PATH) ?>/articles/2">Статья 2</a></li>
            </ul>
        </td>
    </tr>
    <tr>
        <td class="footer" colspan="2">Все права защищены (c) Мой блог</td>
    </tr>
</table>
</body>
</html>
