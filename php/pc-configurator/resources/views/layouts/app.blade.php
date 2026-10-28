<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Каталог компьютерных комплектующих и конфигуратор ПК">
    <title>@yield('title', 'PC Configurator')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=4">
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a class="logo" href="{{ route('home') }}"><span class="logo-mark">PC</span> CONFIGURATOR</a>
        <button class="mobile-menu-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" aria-label="Открыть меню">
            <span></span><span></span><span></span>
        </button>
        <nav id="site-navigation">
            <a href="{{ route('catalog') }}">Каталог</a>
            <a href="{{ route('builder') }}">Конфигуратор</a>
            <a href="{{ route('articles') }}">Статьи</a>
            @auth
                <a href="{{ route('account') }}">Мои сборки</a>
                <a href="{{ route('profile.edit') }}">Профиль</a>
                @if(auth()->user()->is_admin)<a href="{{ route('admin.index') }}">Админ-панель</a>@endif
                <form method="post" action="{{ route('logout') }}" class="nav-form">@csrf<button type="submit">Выйти</button></form>
            @else
                <a href="{{ route('login') }}">Войти</a>
                <a href="{{ route('register') }}">Регистрация</a>
            @endauth
        </nav>
    </div>
</header>
<main class="container">
    @if(session('status'))<div class="alert good">{{ session('status') }}</div>@endif
    @yield('content')
</main>
<footer class="site-footer"><div class="container">PC Configurator · Учебный курсовой проект · Цены демонстрационные</div></footer>
<script>
    (() => {
        const button = document.querySelector('.mobile-menu-toggle');
        const navigation = document.getElementById('site-navigation');
        if (!button || !navigation) return;
        const closeMenu = () => {
            button.setAttribute('aria-expanded', 'false');
            navigation.classList.remove('is-open');
        };
        button.addEventListener('click', () => {
            const open = button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', String(open));
            navigation.classList.toggle('is-open', open);
        });
        navigation.addEventListener('click', event => {
            if (event.target.closest('a')) closeMenu();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeMenu();
        });
        window.matchMedia('(min-width: 861px)').addEventListener('change', event => {
            if (event.matches) closeMenu();
        });
    })();
</script>
</body>
</html>
