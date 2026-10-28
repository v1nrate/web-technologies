@extends('layouts.app')
@section('title', 'Каталог комплектующих')
@section('content')
<div class="page-heading"><span class="eyebrow">КОМПЛЕКТУЮЩИЕ</span><h1>Каталог</h1><p>Подбирай детали по характеристикам. Цены демонстрационные.</p></div>
<form class="catalog-filter-panel" method="get" action="{{ route('catalog') }}">
    <div class="catalog-filter-grid">
        <label>Поиск<input type="search" name="q" value="{{ request('q') }}" placeholder="Модель или производитель"></label>
        <label>Категория<select name="category" onchange="this.form.submit()"><option value="">Все категории</option>@foreach($definitions as $key => $definition)<option value="{{ $key }}" @selected($selectedCategory === $key)>{{ $definition['label'] }}</option>@endforeach</select></label>
        <label>Производитель<select name="brand"><option value="">Все производители</option>@foreach($brands as $brand)<option value="{{ $brand }}" @selected(request('brand') === $brand)>{{ $brand }}</option>@endforeach</select></label>
        <label>Цена от, ₽<input type="number" name="min_price" min="0" value="{{ request('min_price') }}"></label>
        <label>Цена до, ₽<input type="number" name="max_price" min="0" value="{{ request('max_price') }}"></label>
        <label>Сортировка<select name="sort"><option value="price_asc" @selected(request('sort', 'price_asc') === 'price_asc')>Сначала дешевле</option><option value="price_desc" @selected(request('sort') === 'price_desc')>Сначала дороже</option><option value="name" @selected(request('sort') === 'name')>По названию</option><option value="newest" @selected(request('sort') === 'newest')>Сначала новые</option></select></label>
        @foreach($filterable as $key => $definition)
        <label>{{ $definition[0] }}<select name="spec_{{ $key }}"><option value="">Любое значение</option>@foreach($definition[2] ?? [] as $option)<option value="{{ $option }}" @selected(request('spec_'.$key) === $option)>{{ $option }}</option>@endforeach</select></label>
        @endforeach
    </div>
    <div class="catalog-filter-actions"><button class="btn" type="submit">Применить фильтры</button><a class="btn catalog-reset" href="{{ route('catalog') }}">Сбросить</a></div>
</form>
<p class="catalog-count">Найдено комплектующих: {{ $components->total() }}</p>
<div class="cards catalog-cards">
@forelse($components as $part)
    <article class="card catalog-product">
        <span class="eyebrow">{{ $definitions[$part->category]['label'] ?? $part->category }} · {{ $part->brand }}</span>
        <h3>{{ $part->name }}</h3>
        <div class="catalog-specs">
        @foreach(($part->specs ?? []) as $key => $value)
            @php($field = $definitions[$part->category]['fields'][$key] ?? null)
            @if($field && $value !== null && $value !== '')
                <div><span>{{ $field[0] }}</span><strong>{{ is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'Да' : 'Нет') : $value) }}</strong></div>
            @endif
        @endforeach
        </div>
        <div class="catalog-card-bottom"><strong class="price">{{ number_format($part->price, 0, ',', ' ') }} ₽</strong><a href="{{ route('builder', ['category' => $part->category, 'component' => $part->id]) }}" class="btn">В сборку</a></div>
    </article>
@empty
    <p>По выбранным фильтрам ничего не найдено. Попробуй изменить параметры.</p>
@endforelse
</div>
<div class="pagination">{{ $components->links() }}</div>
@endsection
