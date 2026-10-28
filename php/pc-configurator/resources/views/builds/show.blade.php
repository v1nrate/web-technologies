@extends('layouts.app')
@section('title', $build->title . ' — PC Configurator')
@section('content')
@php
    $categoryNames = ['cpu' => 'Процессор', 'motherboard' => 'Материнская плата', 'gpu' => 'Видеокарта', 'ram' => 'Оперативная память', 'ssd' => 'Накопитель', 'psu' => 'Блок питания', 'case' => 'Корпус', 'cooler' => 'Охлаждение'];
    $errorsCount = collect($check['issues'])->where('severity', 'error')->count();
    $warningsCount = collect($check['issues'])->where('severity', 'warning')->count();
    $missingNames = collect($check['missing'])->map(fn ($key) => $categoryNames[$key] ?? $key)->all();
@endphp
<div class="page-heading">
    <span class="eyebrow">РЕЗУЛЬТАТ РАСЧЁТА</span>
    <h1>{{ $build->title }}</h1>
    <p>Ссылка на эту страницу позволяет открыть сохранённую конфигурацию.</p>
</div>
<div class="result-layout">
    <div class="panel">
        <h2>Комплектующие</h2>
        @foreach($items as $part)
            <div class="result-row">
                <div>
                    <span class="eyebrow">{{ $categoryNames[$part->category] ?? $part->category }}</span>
                    <strong>{{ $part->name }}</strong>
                </div>
                <b>{{ number_format($part->price, 0, ',', ' ') }} ₽</b>
            </div>
        @endforeach
        <div class="result-row grand">
            <strong>Итого</strong>
            <strong>{{ number_format($items->sum('price'), 0, ',', ' ') }} ₽</strong>
        </div>
    </div>
    <div class="panel compatibility-panel">
        <h2>Совместимость</h2>
        @if($errorsCount > 0)
            <p class="compatibility-status is-error">Обнаружено критических ошибок: {{ $errorsCount }}</p>
        @elseif($warningsCount > 0 || count($missingNames) > 0)
            <p class="compatibility-status is-warning">Критических ошибок нет, но сборка требует внимания</p>
        @else
            <div class="alert good">Проверенные параметры совместимости не выявили конфликтов.</div>
        @endif
        @foreach($check['issues'] as $issue)
            <section class="compatibility-issue {{ $issue['severity'] === 'error' ? 'is-error' : 'is-warning' }}">
                <h3>{{ $issue['severity'] === 'error' ? '✕' : '!' }} {{ $issue['title'] }}</h3>
                <p>{{ $issue['message'] }}</p>
                <p class="compatibility-advice"><strong>Что сделать:</strong> {{ $issue['recommendation'] }}</p>
                @auth
                    @if($build->user_id === auth()->id())
                        <a class="compatibility-action" href="{{ route('builds.edit', $build) }}">Изменить комплектующие и подобрать замену</a>
                    @endif
                @endauth
            </section>
        @endforeach
        @if(count($missingNames))<div class="live-missing"><strong>Не выбраны комплектующие:</strong><p>{{ implode(', ', $missingNames) }}</p></div>@endif
        <div class="compatibility-power">
            <div><span>Ориентировочное потребление</span><strong>{{ $check['estimated_watts'] }} Вт</strong></div>
            <div><span>Рекомендуемая мощность БП (+25%)</span><strong>{{ $check['recommended_psu_watts'] }} Вт</strong></div>
            @if($check['psu_watts'] !== null)
                <div><span>Выбранный блок питания</span><strong>{{ $check['psu_watts'] }} Вт</strong></div>
            @endif
        </div>
        <p class="muted">Расчёт ориентировочный и не заменяет официальные спецификации производителей. Отсутствующие характеристики не считаются подтверждённой совместимостью.</p>
        @auth
            @if($build->user_id === auth()->id())
                <a class="btn wide" href="{{ route('builds.edit', $build) }}">Редактировать сборку</a>
                <form method="post" action="{{ route('builds.destroy', $build) }}" onsubmit="return confirm('Удалить сборку?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn ghost wide" type="submit">Удалить</button>
                </form>
            @endif
        @endauth
        <a class="btn wide" href="{{ route('builder') }}">Новая сборка</a>
    </div>
</div>
@endsection
