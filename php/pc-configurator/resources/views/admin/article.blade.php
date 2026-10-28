@extends('layouts.app')
@section('title', $article ? 'Редактирование статьи' : 'Новая статья')
@section('content')
@include('admin._nav')
<div class="page-heading"><span class="eyebrow">ПУБЛИКАЦИИ</span><h1>{{ $article ? 'Редактирование статьи' : 'Новая статья' }}</h1><p>Редактор Markdown с предпросмотром. Черновики не видны посетителям.</p></div>
@if($errors->any())<div class="alert danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ $article ? route('admin.articles.update', $article) : route('admin.articles.store') }}" class="panel article-editor-panel">
@csrf
@if($article) @method('PUT') @endif
<div class="article-form-grid">
<label>Заголовок<input name="title" required maxlength="255" value="{{ old('title', $article->title ?? '') }}"></label>
<label>Адрес статьи<input name="slug" required maxlength="255" pattern="[A-Za-z0-9_-]+" value="{{ old('slug', $article->slug ?? '') }}"></label>
<label>Категория<select name="category" required>@foreach($categories as $key => $name)<option value="{{ $key }}" @selected(old('category', $article->category ?? 'guides') === $key)>{{ $name }}</option>@endforeach</select></label>
<label>Статус<select name="status" required><option value="draft" @selected(old('status', $article->status ?? 'draft') === 'draft')>Черновик</option><option value="published" @selected(old('status', $article->status ?? 'draft') === 'published')>Опубликована</option></select></label>
<label>Автор<input name="author" required maxlength="255" value="{{ old('author', $article->author ?? auth()->user()->name) }}"></label>
<label>Обложка (HTTPS-ссылка)<input name="cover_url" type="url" placeholder="https://example.com/cover.jpg" value="{{ old('cover_url', $article->cover_url ?? '') }}"></label>
</div>
<label class="article-full-field">Краткое описание<textarea name="excerpt" rows="3" maxlength="2000" required>{{ old('excerpt', $article->excerpt ?? '') }}</textarea></label>
<div class="article-editor-heading"><strong>Текст статьи</strong><span>Markdown · предпросмотр обновляется автоматически</span></div>
<div class="article-toolbar" id="markdown-toolbar"><button type="button" data-markdown="heading">Заголовок</button><button type="button" data-markdown="bold"><b>Жирный</b></button><button type="button" data-markdown="italic"><i>Курсив</i></button><button type="button" data-markdown="list">Список</button><button type="button" data-markdown="link">Ссылка</button><button type="button" data-markdown="image">Изображение</button><button type="button" data-markdown="code">Код</button></div>
<div class="article-editor-grid"><textarea id="markdown-body" name="body" rows="21" required placeholder="## Заголовок раздела&#10;&#10;Текст статьи...">{{ old('body', $article->body ?? '') }}</textarea><div class="article-preview"><div class="article-preview-title">Предпросмотр</div><div id="article-preview-body" class="article-body">Текст статьи появится здесь.</div></div></div>
<div class="article-editor-actions"><button class="btn" type="submit">Сохранить статью</button><a class="btn ghost" href="{{ route('admin.articles.index') }}">Отмена</a>@if($article)<a class="btn ghost" href="{{ route('articles.show', $article) }}" target="_blank" rel="noopener">Открыть статью</a>@endif</div>
</form>
<script src="{{ asset('js/article-editor.js') }}" defer></script>
@endsection
