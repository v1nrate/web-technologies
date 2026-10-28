@extends('layouts.app')
@section('title', $article->title)
@section('content')
<article class="article article-detail"><a href="{{ route('articles') }}" class="back">← Все статьи</a>@if($article->status === 'draft')<div class="alert danger">Предпросмотр черновика — посетителям статья недоступна.</div>@endif<span class="eyebrow">{{ $article->category_label }} · {{ $article->created_at->format('d.m.Y') }} · {{ $article->author }}</span><h1>{{ $article->title }}</h1><p class="lead">{{ $article->excerpt }}</p>@if($article->cover_url)<img class="article-hero" src="{{ $article->cover_url }}" alt="" loading="lazy">@endif
@php($headings = preg_match_all('/^#{1,3}\s+(.+)$/mu', $article->body, $matches) ? $matches[1] : [])
@if(count($headings) > 1)<nav class="article-toc"><strong>Содержание</strong><ol>@foreach($headings as $heading)<li><a href="#section-{{ substr(sha1($heading), 0, 10) }}">{{ $heading }}</a></li>@endforeach</ol></nav>@endif
<div class="article-body">{!! \App\Support\ArticleMarkdown::render($article->body) !!}</div></article>
@if($related->isNotEmpty())<section class="article-related"><h2>Похожие статьи</h2><div class="article-card-grid">@foreach($related as $item)<a class="article-list-card" href="{{ route('articles.show', $item) }}"><div class="article-card-inner"><span class="eyebrow">{{ $item->category_label }}</span><h2>{{ $item->title }}</h2><p>{{ $item->excerpt }}</p><span class="article-read-more">Читать →</span></div></a>@endforeach</div></section>@endif
@endsection
