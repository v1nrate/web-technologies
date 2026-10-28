<?php
$url = 'https://httpbin.org/';
$headers = false;
$errorMessage = '';

// get_headers() может вернуть false при сетевой ошибке.
// Временный обработчик превращает предупреждение PHP в понятное сообщение.
set_error_handler(static function (int $severity, string $message) use (&$errorMessage): bool {
    $errorMessage = $message;
    return true;
});
try {
    $headers = get_headers($url);
} finally {
    restore_error_handler();
}

$result = is_array($headers)
    ? implode("\n", $headers)
    : "Не удалось получить HTTP-заголовки с {$url}.\nПроверьте подключение к интернету и настройки PHP.";
if ($errorMessage !== '' && $headers === false) {
    $result .= "\n\nПодробности: {$errorMessage}";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feedback Form — HTTP-заголовки</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page">
    <header class="site-header">
        <a class="brand" href="https://mospolytech.ru/" target="_blank" rel="noopener noreferrer" aria-label="Московский Политех — официальный сайт">
            <img src="images/logo_mos_pol.png" alt="Московский Политех" class="university-logo">
            <span class="brand-fallback">Московский Политех</span>
        </a>
        <h1>Feedback Form</h1>
        <span class="header-spacer" aria-hidden="true"></span>
    </header>
    <main class="content">
        <nav class="navigation" aria-label="Навигация">
            <a href="index.php">Обратная связь</a>
            <a class="active" aria-current="page" href="headers.php">HTTP-заголовки</a>
        </nav>
        <section class="panel" aria-labelledby="headers-title">
            <p class="eyebrow">Домашняя работа 4.1</p>
            <h2 id="headers-title">HTTP-заголовки ответа</h2>
            <p class="description">Результат выполнения PHP-функции <code>get_headers('https://httpbin.org/')</code>:</p>
            <label class="field-label" for="headers-result">Полученные заголовки</label>
            <textarea class="headers-output" id="headers-result" rows="15" readonly><?= htmlspecialchars($result, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
            <a class="text-link" href="headers.php">Обновить заголовки</a>
        </section>
    </main>
    <footer class="site-footer">Задание для самостоятельной работы</footer>
</div>
</body>
</html>
