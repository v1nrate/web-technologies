@extends('layouts.app')
@section('title', 'Сборки — админ-панель')
@section('content')
<div class="page-heading"><span class="eyebrow">КОНФИГУРАЦИИ</span><h1>Сохранённые сборки</h1></div>@include('admin._nav')
<form method="get" class="admin-filters panel"><input name="search" value="{{ request('search') }}" placeholder="Название сборки"><button class="btn">Найти</button></form>
<div class="panel data-panel">@forelse($builds as $build)<div class="data-row"><div><strong>{{ $build->title }}</strong><p class="muted">{{ $build->user?->name ?? 'Гостевая сборка' }} · {{ number_format($build->total_price,0,',',' ') }} ₽ · {{ $build->created_at->format('d.m.Y') }} · {{ count($build->warnings ?? []) }} предупреждений</p></div><div class="row-actions"><a class="btn ghost" href="{{ route('builds.show',$build) }}">Открыть</a><form method="post" action="{{ route('admin.builds.destroy',$build) }}" onsubmit="return confirm('Безвозвратно удалить сборку?')">@csrf @method('DELETE')<button class="btn ghost">Удалить</button></form></div></div>@empty<div class="admin-box">Сборок пока нет.</div>@endforelse</div><div class="pagination">{{ $builds->links() }}</div>
@endsection
