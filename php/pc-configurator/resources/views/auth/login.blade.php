@extends('layouts.app')
@section('title', 'Вход')
@section('content')
<div class="auth-panel panel"><span class="eyebrow">АККАУНТ</span><h1>Вход</h1>
@if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('login.submit') }}">@csrf
<label class="field-label">Электронная почта</label><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
<label class="field-label">Пароль</label><input type="password" name="password" required autocomplete="current-password">
<button class="btn wide">Войти</button></form>
<p class="muted">Нет аккаунта? <a class="text-link" href="{{ route('register') }}">Зарегистрироваться</a></p></div>
@endsection
