@extends('layouts.app')
@section('title', 'Статьи о компьютерах')
@section('content')
<div class="page-heading"><span class="eyebrow">БАЗА ЗНАНИЙ</span><h1>Статьи</h1><p>Руководства по выбору комплектующих и сборке ПК.</p></div>
<form class="panel article-filter-form" method="get"><input name="search" placeholder="Найти статью" value="{{ request('search') }}"><select name="category"><option value="">Все темы</option>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>@endforeach</select><select name="sort"><option value="newest">Сначала новые</option><option value="oldest" @selected(request('sort') === 'oldest')>Сначала старые</option></select><button class="btn">Найти</button><a class="btn ghost" href="{{ route('articles') }}">Сбросить</a></form>
<div class="article-card-grid">@forelse($articles as $article)<a class="article-list-card" href="{{ route('articles.show', $article) }}">@if($article->cover_url)<img class="article-cover" src="{{ $article->cover_url }}" alt="" loading="lazy">@endif<div class="article-card-inner"><span class="eyebrow">{{ $article->category_label }}</span><h2>{{ $article->title }}</h2><p>{{ $article->excerpt }}</p><div class="article-card-meta">{{ $article->author }} · {{ $article->created_at->format('d.m.Y') }}</div><span class="article-read-more">Читать статью →</span></div></a>@empty<div class="panel article-empty">Статьи не найдены.</div>@endforelse</div>
<div class="pagination">{{ $articles->links() }}</div>
@endsection
