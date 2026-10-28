<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feedback Form — Форма обратной связи</title>
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
            <a class="active" aria-current="page" href="index.php">Обратная связь</a>
            <a href="headers.php">HTTP-заголовки</a>
        </nav>
        <section class="panel" aria-labelledby="form-title">
            <p class="eyebrow">Домашняя работа 4.1</p>
            <h2 id="form-title">Форма обратной связи</h2>
            <p class="description">Заполните форму. После отправки данные будут переданы сервису httpbin.org методом POST.</p>
            <form action="https://httpbin.org/post" method="post" accept-charset="UTF-8">
                <div class="form-grid">
                    <div class="field"><label for="username">Имя пользователя <span class="required">*</span></label><input id="username" name="username" type="text" autocomplete="name" required maxlength="100" placeholder="Ваше имя"></div>
                    <div class="field"><label for="email">Электронная почта <span class="required">*</span></label><input id="email" name="email" type="email" autocomplete="email" required maxlength="254" placeholder="name@example.com"></div>
                </div>
                <fieldset class="field"><legend>Тип обращения <span class="required">*</span></legend><div class="choice-group"><label class="choice"><input type="radio" name="feedback_type" value="complaint" required> Жалоба</label><label class="choice"><input type="radio" name="feedback_type" value="suggestion"> Предложение</label><label class="choice"><input type="radio" name="feedback_type" value="thanks"> Благодарность</label></div></fieldset>
                <div class="field"><label for="message">Текст сообщения <span class="required">*</span></label><textarea id="message" name="message" rows="6" required maxlength="5000" placeholder="Напишите ваше сообщение..."></textarea></div>
                <fieldset class="field"><legend>Предпочтительный способ ответа</legend><p class="hint">Можно выбрать один или оба варианта.</p><div class="choice-group"><label class="choice"><input type="checkbox" name="response_sms" value="yes"> SMS</label><label class="choice"><input type="checkbox" name="response_email" value="yes"> Email</label></div></fieldset>
                <button class="button" type="submit">Отправить сообщение</button>
            </form>
        </section>
    </main>
    <footer class="site-footer">Задание для самостоятельной работы</footer>
</div>
</body>
</html>
