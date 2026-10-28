@extends('layouts.app')
@section('title', 'PC Configurator — собери свой компьютер')
@section('content')
<section class="hero"><div><span class="eyebrow">КОНФИГУРАТОР ПК</span><h1>Собери компьютер<br><em>под свои задачи.</em></h1><p>Подбирай комплектующие, рассчитывай стоимость и проверяй совместимость в одном месте.</p><a class="btn" href="{{ route('builder') }}">Начать сборку →</a><a class="btn ghost" href="{{ route('catalog') }}">Смотреть каталог</a></div><div class="hero-visual"><div class="chip">CPU</div><div class="chip">GPU</div><div class="chip">RAM</div><div class="chip">SSD</div><div class="hero-caption">ТВОЯ ИДЕАЛЬНАЯ СБОРКА</div></div></section>
<section class="stats"><div><strong>{{ $count }}</strong><span>комплектующих в каталоге</span></div><div><strong>8</strong><span>категорий</span></div><div><strong>6</strong><span>проверок совместимости</span></div></section>
<div class="section-heading"><h2>Полезные статьи</h2><a href="{{ route('articles') }}">Все статьи →</a></div><div class="cards">@foreach($articles as $article)<a class="card" href="{{ route('articles.show', $article) }}"><span class="eyebrow">РУКОВОДСТВО</span><h3>{{ $article->title }}</h3><p>{{ $article->excerpt }}</p><span class="muted">{{ $article->author }}</span></a>@endforeach</div>
@endsection
