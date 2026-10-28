@extends('layouts.app')
@section('title', 'Настройки профиля — PC Configurator')
@section('content')
<section class="page-heading">
    <span class="eyebrow">ЛИЧНЫЙ КАБИНЕТ</span>
    <h1>Настройки профиля</h1>
    <p>Измените имя, адрес электронной почты или пароль.</p>
</section>
<div class="profile-layout">
    <section class="panel profile-panel">
        <h2>Личные данные</h2>
        <form method="post" action="{{ route('profile.update') }}" class="profile-form">
            @csrf
            @method('PATCH')
            <label>Имя<input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" autocomplete="name"></label>
            @error('name')<p class="profile-error">{{ $message }}</p>@enderror
            <label>Электронная почта<input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email"></label>
            @error('email')<p class="profile-error">{{ $message }}</p>@enderror
            <button type="submit" class="btn">Сохранить изменения</button>
        </form>
    </section>
    <section class="panel profile-panel">
        <h2>Безопасность</h2>
        <p class="muted">Чтобы изменить пароль, подтвердите текущий.</p>
        <form method="post" action="{{ route('profile.password') }}" class="profile-form">
            @csrf
            @method('PUT')
            <label>Текущий пароль<input type="password" name="current_password" required autocomplete="current-password"></label>
            @error('current_password', 'password')<p class="profile-error">{{ $message }}</p>@enderror
            <label>Новый пароль<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
            @error('password', 'password')<p class="profile-error">{{ $message }}</p>@enderror
            <label>Повторите новый пароль<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
            <button type="submit" class="btn">Изменить пароль</button>
        </form>
    </section>
</div>
<div class="profile-footer"><a class="btn ghost" href="{{ route('account') }}">Вернуться к моим сборкам</a></div>
@endsection
