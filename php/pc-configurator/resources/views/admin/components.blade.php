@extends('layouts.app')
@php
    $totalComponents = $totalComponents ?? \App\Models\Component::count();
@endphp
@section('title', 'Комплектующие — админ-панель')
@section('content')
<div class="page-heading">
    <span class="eyebrow">КАТАЛОГ</span>
    <h1>Комплектующие</h1>
    <p>Поиск, фильтрация, импорт CSV и управление товарами.</p>
</div>

@include('admin._nav')

<div class="admin-category-stats">
    <a class="admin-stat-chip {{ !request()->filled('category') ? 'is-active' : '' }}" href="{{ route('admin.components.index') }}" aria-label="Показать все комплектующие"><strong>{{ $totalComponents }}</strong><span>Всего комплектующих</span></a>
    @foreach($categories as $key => $label)
        <a class="admin-stat-chip {{ request('category') === $key ? 'is-active' : '' }}" href="{{ route('admin.components.index', ['category' => $key]) }}"><strong>{{ $statistics[$key] ?? 0 }}</strong><span>{{ $label }}</span></a>
    @endforeach
</div>

<div class="section-heading">
    <h2>Каталог</h2>
    <a class="btn admin-add-button" href="{{ route('admin.components.new') }}">+ Добавить</a>
</div>

<form class="admin-filters panel" method="get">
    <input name="search" placeholder="Название или производитель" value="{{ request('search') }}">
    <select name="category">
        <option value="">Все категории</option>
        @foreach($categories as $category => $categoryLabel)
            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $categoryLabel }}</option>
        @endforeach
    </select>
    <select name="brand">
        <option value="">Все бренды</option>
        @foreach($brands as $brand)
            <option value="{{ $brand }}" @selected(request('brand') === $brand)>{{ $brand }}</option>
        @endforeach
    </select>
    <select name="sort">
        <option value="newest" @selected(request('sort', 'newest') === 'newest')>Сначала новые</option>
        <option value="price_asc" @selected(request('sort') === 'price_asc')>Цена ↑</option>
        <option value="price_desc" @selected(request('sort') === 'price_desc')>Цена ↓</option>
        <option value="name" @selected(request('sort') === 'name')>По названию</option>
    </select>
    <input type="number" name="min_price" min="0" placeholder="Цена от, ₽" value="{{ request('min_price') }}">
    <input type="number" name="max_price" min="0" placeholder="Цена до, ₽" value="{{ request('max_price') }}">
    <select name="socket"><option value="">Любой сокет</option>@foreach(['AM4','AM5','LGA1700','LGA1851'] as $value)<option value="{{ $value }}" @selected(request('socket') === $value)>{{ $value }}</option>@endforeach</select>
    <select name="memory_type"><option value="">Любая память</option>@foreach(['DDR4','DDR5','DDR4/DDR5'] as $value)<option value="{{ $value }}" @selected(request('memory_type') === $value)>{{ $value }}</option>@endforeach</select>
    <select name="form_factor"><option value="">Любой форм-фактор</option>@foreach(['ATX','mATX','Mini-ITX','E-ATX'] as $value)<option value="{{ $value }}" @selected(request('form_factor') === $value)>{{ $value }}</option>@endforeach</select>
    <button class="btn">Применить</button>
    <a class="btn ghost" href="{{ route('admin.components.index') }}">Сбросить</a>
    <a class="btn ghost" href="{{ route('admin.components.export', request()->query()) }}">Экспорт CSV</a>
</form>

<form id="bulk-delete-form" method="post" action="{{ route('admin.components.bulk-delete') }}">
    @csrf

    <div id="bulk-actions" class="panel" hidden aria-live="polite" style="margin-bottom:16px;padding:16px 20px;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <strong id="selected-count">Выбрано: 0</strong>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <button class="btn" type="submit" style="background:#ef6464;color:#10131c;">Удалить выбранные</button>
            <button id="clear-selection" class="btn ghost" type="button">Снять выделение</button>
        </div>
    </div>

    <div class="panel admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all" aria-label="Выбрать все комплектующие на странице"></th>
                    <th>Комплектующее</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Мощность</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($components as $component)
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="{{ $component->id }}" aria-label="Выбрать {{ $component->name }}"></td>
                        <td>
                            <strong>{{ $component->name }}</strong>
                            <small>{{ $component->brand }}</small>
                        </td>
                        <td>{{ $categories[$component->category] ?? $component->category }}</td>
                        <td>{{ number_format($component->price, 0, ',', ' ') }} ₽</td>
                        <td>{{ $component->power_watts }} Вт</td>
                        <td><a href="{{ route('admin.components.edit', $component) }}">Изменить</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">По запросу ничего не найдено.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-quick">
        <span class="muted">Найдено: {{ $components->total() }} · Показано: {{ $components->count() }} · Страница {{ $components->currentPage() }} из {{ $components->lastPage() }}</span>
    </div>
</form>

<nav class="admin-pagination" aria-label="Страницы каталога">
    @if($components->onFirstPage())<span class="disabled">← Назад</span>@else<a href="{{ $components->previousPageUrl() }}">← Назад</a>@endif
    <span>{{ $components->currentPage() }} / {{ $components->lastPage() }}</span>
    @if($components->hasMorePages())<a href="{{ $components->nextPageUrl() }}">Вперёд →</a>@else<span class="disabled">Вперёд →</span>@endif
</nav>

<section class="panel admin-box admin-import">
    <h2>Импорт из CSV</h2>
    <p class="muted">UTF-8, разделитель «;», максимум 500 строк и 5 МБ. Совпадающие категория, бренд и название обновляются. Сначала можно проверить файл без записи в базу.</p>
    <form id="csv-import-form" method="post" action="{{ route('admin.components.import') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".csv,.txt" required>
        <button class="btn ghost" type="submit" formaction="{{ route('admin.components.import.preview') }}">Проверить CSV</button>
        <button class="btn" type="submit" onclick="return confirm('Импортировать записи в каталог? Совпадающие модели будут обновлены.')">Импортировать</button>
    </form>
    <p class="muted">Столбцы: category;brand;name;price;power_watts;specs. В specs — JSON-объект.</p>
    <a class="admin-template-link" href="data:text/csv;charset=utf-8,category%3Bbrand%3Bname%3Bprice%3Bpower_watts%3Bspecs%0Acpu%3BAMD%3BExample%20CPU%3B12000%3B65%3B%22%7B%22%22socket%22%22%3A%22%22AM4%22%22%2C%22%22cores%22%22%3A6%7D%22" download="components-template.csv">Скачать шаблон CSV</a>
    @if(session('csv_preview'))
        <div class="admin-preview" role="status">
            <strong>Предварительная проверка: {{ session('csv_preview.total') }} корректных записей</strong>
            @if(count(session('csv_preview.errors', [])))
                <p>Ошибки:</p><ul>@foreach(session('csv_preview.errors') as $message)<li>{{ $message }}</li>@endforeach</ul>
            @else
                <p>Ошибок формата не обнаружено. Импорт ещё не выполнен.</p>
            @endif
            <p>Примеры: {{ implode(', ', session('csv_preview.examples', [])) }}</p>
        </div>
    @endif
</section>

<script>
    (() => {
        const form = document.getElementById('bulk-delete-form');
        const selectAll = document.getElementById('select-all');
        const checkboxes = Array.from(form.querySelectorAll('input[name="ids[]"]'));
        const actions = document.getElementById('bulk-actions');
        const counter = document.getElementById('selected-count');
        const clearButton = document.getElementById('clear-selection');

        const updateSelection = () => {
            const selected = checkboxes.filter(checkbox => checkbox.checked).length;
            counter.textContent = `Выбрано: ${selected}`;
            actions.hidden = selected === 0;
            actions.style.display = selected === 0 ? 'none' : 'flex';
            selectAll.checked = checkboxes.length > 0 && selected === checkboxes.length;
            selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
        };

        selectAll.addEventListener('change', () => {
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateSelection();
        });

        checkboxes.forEach(checkbox => checkbox.addEventListener('change', updateSelection));

        clearButton.addEventListener('click', () => {
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelection();
        });

        form.addEventListener('submit', event => {
            const selected = checkboxes.filter(checkbox => checkbox.checked).length;
            if (selected === 0 || !confirm(`Удалить выбранные комплектующие (${selected} шт.)?`)) {
                event.preventDefault();
            }
        });

        updateSelection();
    })();
</script>
@endsection
