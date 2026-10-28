@extends('layouts.app')
@section('title', 'Мои сборки — PC Configurator')
@section('content')
<section class="page-heading account-heading">
    <span class="eyebrow">ЛИЧНЫЙ КАБИНЕТ</span>
    <div class="account-heading-row">
        <div>
            <h1>Мои сборки</h1>
            <p>Сохраняйте конфигурации, сравнивайте стоимость и возвращайтесь к редактированию в любое время.</p>
        </div>
        <a class="btn" href="{{ route('builder') }}">+ Новая сборка</a>
    </div>
</section>

<form class="account-filters" method="get" action="{{ route('account') }}">
    <label><span>Поиск</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Название сборки"></label>
    <label><span>Статус</span><select name="status">
        <option value="all">Все сборки</option>
        <option value="compatible" @selected(($filters['status'] ?? '') === 'compatible')>Совместимые</option>
        <option value="errors" @selected(($filters['status'] ?? '') === 'errors')>С ошибками</option>
        <option value="incomplete" @selected(($filters['status'] ?? '') === 'incomplete')>Незавершённые</option>
    </select></label>
    <label><span>Сортировка</span><select name="sort">
        <option value="newest">Сначала новые</option>
        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Сначала старые</option>
        <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Сначала дешёвые</option>
        <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Сначала дорогие</option>
        <option value="title" @selected(($filters['sort'] ?? '') === 'title')>По названию</option>
    </select></label>
    <button class="btn" type="submit">Найти</button>
    <a class="btn ghost" href="{{ route('account') }}">Сбросить</a>
</form>

<div class="account-results">Найдено сборок: <strong>{{ $builds->total() }}</strong></div>
<div class="account-build-grid">
    @forelse($builds as $build)
        <article class="account-build-card">
            <div class="account-card-top">
                <span class="account-status {{ $build->account_status === 'compatible' ? 'account-status-ok' : ($build->account_status === 'errors' ? 'account-status-error' : 'account-status-incomplete') }}">
                    @if($build->account_status === 'errors') {{ $build->account_errors }} крит. ошибок
                    @elseif($build->account_status === 'incomplete') Не завершена
                    @else Проверенные параметры совместимы
                    @endif
                </span>
                <span class="account-date">{{ $build->created_at->format('d.m.Y') }}</span>
            </div>
            <h2><a href="{{ route('builds.show', $build) }}">{{ $build->title }}</a></h2>
            <div class="account-price">{{ number_format($build->total_price, 0, ',', ' ') }} ₽</div>
            <p class="account-meta">Выбрано комплектующих: {{ $build->account_parts_count }} из 8
                @if($build->account_missing > 0) · Не выбрано: {{ $build->account_missing }} @endif
            </p>
            <div class="account-card-actions">
                <a class="btn" href="{{ route('builds.edit', $build) }}">Редактировать</a>
                <a class="btn ghost" href="{{ route('builds.show', $build) }}">Просмотр</a>
                <form action="{{ route('builds.duplicate', $build) }}" method="post">@csrf<button class="btn ghost" type="submit">Копировать</button></form>
                <button class="btn ghost account-share" type="button" data-url="{{ route('builds.show', $build) }}">Ссылка</button>
                <form action="{{ route('builds.destroy', $build) }}" method="post" onsubmit="return confirm('Удалить эту сборку? Это действие нельзя отменить.')">@csrf @method('DELETE')<button class="btn ghost account-delete" type="submit">Удалить</button></form>
            </div>
        </article>
    @empty
        <div class="account-empty">
            <h2>Сборки не найдены</h2>
            <p>Попробуйте изменить фильтры или создайте новую конфигурацию.</p>
            <a class="btn" href="{{ route('builder') }}">Открыть конфигуратор</a>
        </div>
    @endforelse
</div>
<div class="pagination">{{ $builds->links() }}</div>
<script>
document.querySelectorAll('.account-share').forEach(button => {
    button.addEventListener('click', async () => {
        const url = button.dataset.url;
        try {
            await navigator.clipboard.writeText(url);
            const old = button.textContent;
            button.textContent = 'Скопировано';
            setTimeout(() => { button.textContent = old; }, 1800);
        } catch {
            window.prompt('Скопируйте ссылку на сборку:', url);
        }
    });
});
</script>
@endsection
