@extends('layouts.app')
@section('title', 'Админ-панель')
@section('content')
<div class="page-heading"><span class="eyebrow">УПРАВЛЕНИЕ</span><h1>Админ-панель</h1><p>Комплектующие и статьи сайта.</p></div>
<div class="section-heading"><h2>Комплектующие</h2><a href="{{ route('admin.components.new') }}">Добавить комплектующее →</a></div>
<div class="panel data-panel">@foreach($components as $component)<div class="data-row"><div><strong>{{ $component->name }}</strong><p class="muted">{{ strtoupper($component->category) }} · {{ number_format($component->price,0,',',' ') }} ₽</p></div><div class="row-actions"><a class="btn ghost" href="{{ route('admin.components.edit', $component) }}">Изменить</a><form method="post" action="{{ route('admin.components.destroy', $component) }}" onsubmit="return confirm('Удалить комплектующее?')">@csrf @method('DELETE')<button class="btn ghost">Удалить</button></form></div></div>@endforeach</div><div class="pagination">{{ $components->links() }}</div>
<div class="section-heading"><h2>Статьи</h2><a href="{{ route('admin.articles.new') }}">Добавить статью →</a></div>
<div class="panel data-panel">@foreach($articles as $article)<div class="data-row"><div><strong>{{ $article->title }}</strong><p class="muted">{{ $article->author }}</p></div><div class="row-actions"><a class="btn ghost" href="{{ route('admin.articles.edit', $article) }}">Изменить</a><form method="post" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Удалить статью?')">@csrf @method('DELETE')<button class="btn ghost">Удалить</button></form></div></div>@endforeach</div>
@endsection
