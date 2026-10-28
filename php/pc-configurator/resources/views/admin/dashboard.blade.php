@extends('layouts.app')
@section('title', 'Панель управления — PC Configurator')
@section('content')
<section class="page-heading">
    <span class="eyebrow">ПАНЕЛЬ УПРАВЛЕНИЯ</span>
    <h1>Администрирование</h1>
    <p>Сводка проекта, последние изменения и быстрые действия.</p>
</section>
@include('admin._nav')
<div class="admin-stats">
    <a href="{{ route('admin.components.index') }}"><span>Комплектующие</span><strong>{{ $componentCount }}</strong></a>
    <a href="{{ route('admin.users.index') }}"><span>Пользователи</span><strong>{{ $userCount }}</strong></a>
    <a href="{{ route('admin.builds.index') }}"><span>Сборки</span><strong>{{ $buildCount }}</strong></a>
    <a href="{{ route('admin.articles.index') }}"><span>Статьи</span><strong>{{ $articleCount }}</strong></a>
</div>
<div class="admin-dashboard-note">Сборки гостей: <strong>{{ $guestBuildCount }}</strong> · Сборки зарегистрированных пользователей: <strong>{{ $buildCount - $guestBuildCount }}</strong></div>
<div class="admin-grid">
    <section class="panel admin-box">
        <h2>Категории комплектующих</h2>
        @forelse($categories as $category)
            <div class="admin-line"><span>{{ strtoupper($category->category) }}</span><strong>{{ $category->total }}</strong></div>
        @empty<p class="muted">Каталог пуст.</p>@endforelse
    </section>
    <section class="panel admin-box">
        <h2>Последние сборки</h2>
        @forelse($recentBuilds as $build)
            <div class="admin-line"><a href="{{ route('builds.show', $build) }}">{{ $build->title }}</a><span>{{ number_format($build->total_price, 0, ',', ' ') }} ₽</span></div>
        @empty<p class="muted">Пока нет сохранённых сборок.</p>@endforelse
    </section>
    <section class="panel admin-box">
        <h2>Новые пользователи</h2>
        @forelse($recentUsers as $user)
            <div class="admin-line"><span>{{ $user->name }}</span><span>{{ $user->created_at?->format('d.m.Y') }}</span></div>
        @empty<p class="muted">Пользователей пока нет.</p>@endforelse
        <a class="admin-dashboard-more" href="{{ route('admin.users.index') }}">Все пользователи</a>
    </section>
    <section class="panel admin-box">
        <h2>Последние статьи</h2>
        @forelse($recentArticles as $article)
            <div class="admin-line"><a href="{{ route('admin.articles.edit', $article) }}">{{ $article->title }}</a><span>{{ $article->created_at?->format('d.m.Y') }}</span></div>
        @empty<p class="muted">Статей пока нет.</p>@endforelse
        <a class="admin-dashboard-more" href="{{ route('admin.articles.index') }}">Все статьи</a>
    </section>
</div>
<div class="admin-quick">
    <a class="btn" href="{{ route('admin.components.new') }}">Добавить комплектующее</a>
    <a class="btn ghost" href="{{ route('admin.articles.new') }}">Написать статью</a>
    <a class="btn ghost" href="{{ route('admin.users.index') }}">Управлять пользователями</a>
</div>
@endsection
