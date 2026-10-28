@extends('layouts.app')
@section('title', 'Регистрация')
@section('content')
<div class="auth-panel panel"><span class="eyebrow">АККАУНТ</span><h1>Регистрация</h1>
@if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('register.submit') }}">@csrf
<label class="field-label">Имя</label><input name="name" value="{{ old('name') }}" required maxlength="100">
<label class="field-label">Электронная почта</label><input type="email" name="email" value="{{ old('email') }}" required>
<label class="field-label">Пароль (не менее 8 символов)</label><input type="password" name="password" required minlength="8">
<label class="field-label">Повтор пароля</label><input type="password" name="password_confirmation" required minlength="8">
<button class="btn wide">Создать аккаунт</button></form></div>
@endsection
