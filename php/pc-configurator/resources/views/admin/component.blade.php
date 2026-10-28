@extends('layouts.app')
@section('title', 'Редактор комплектующего')
@section('content')
@include('admin._nav')
<div class="page-heading">
    <span class="eyebrow">АДМИН-ПАНЕЛЬ</span>
    <h1>{{ $component ? 'Редактирование комплектующего' : 'Новое комплектующее' }}</h1>
</div>
<div class="panel editor-panel component-editor">
    @if($errors->any())
        <div class="alert danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    <form method="post" action="{{ $component ? route('admin.components.update', $component) : route('admin.components.store') }}">
        @csrf
        @if($component) @method('PUT') @endif
        <label class="field-label" for="component-category">Категория</label>
        <select id="component-category" name="category" required>
            @foreach(config('component_specs') as $code => $definition)
                <option value="{{ $code }}" @selected(old('category', $component->category ?? 'cpu') === $code)>{{ $definition['label'] }}</option>
            @endforeach
        </select>
        <div class="component-fields-grid">
            <div><label class="field-label">Производитель</label><input name="brand" required maxlength="80" value="{{ old('brand', $component->brand ?? '') }}"></div>
            <div><label class="field-label">Название</label><input name="name" required maxlength="255" value="{{ old('name', $component->name ?? '') }}"></div>
            <div><label class="field-label">Цена, ₽</label><input type="number" name="price" min="0" required value="{{ old('price', $component->price ?? 0) }}"></div>
            <div><label class="field-label">Потребление, Вт</label><input type="number" name="power_watts" min="0" required value="{{ old('power_watts', $component->power_watts ?? 0) }}"></div>
        </div>
        <h2 class="component-spec-title">Технические характеристики</h2>
        @foreach(config('component_specs') as $code => $definition)
            <div class="component-spec-group" data-category="{{ $code }}" hidden>
                <div class="component-fields-grid">
                    @foreach($definition['fields'] as $key => $field)
                        @php
                            $label = $field[0];
                            $type = $field[1];
                            $options = $field[2] ?? [];
                            $required = $field[3] ?? false;
                            $saved = $component && $component->category === $code ? ($component->specs[$key] ?? null) : null;
                            $value = old('category', $component->category ?? 'cpu') === $code ? old('attributes.'.$key, $saved) : $saved;
                        @endphp
                        <div class="component-spec-field">
                            <label class="field-label" for="spec-{{ $code }}-{{ $key }}">{{ $label }}@if($required) <span class="component-required">*</span>@endif</label>
                            @if($type === 'select')
                                <select id="spec-{{ $code }}-{{ $key }}" data-field-name="attributes[{{ $key }}]" data-required="{{ $required ? 1 : 0 }}">
                                    <option value="">Выберите значение</option>
                                    @foreach($options as $option)
                                        <option value="{{ $option }}" @selected((string)$value === (string)$option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @elseif($type === 'multiple')
                                <div class="component-checkboxes" id="spec-{{ $code }}-{{ $key }}">
                                    @foreach($options as $option)
                                        <label><input type="checkbox" data-field-name="attributes[{{ $key }}][]" value="{{ $option }}" @checked(in_array($option, (array)$value, true))><span>{{ $option }}</span></label>
                                    @endforeach
                                </div>
                            @elseif($type === 'boolean')
                                <select id="spec-{{ $code }}-{{ $key }}" data-field-name="attributes[{{ $key }}]">
                                    <option value="0" @selected(!$value)>Нет</option>
                                    <option value="1" @selected((bool)$value)>Да</option>
                                </select>
                            @else
                                <input id="spec-{{ $code }}-{{ $key }}" type="{{ in_array($type, ['number','decimal']) ? 'number' : 'text' }}" @if($type === 'decimal') step="any" @elseif($type === 'number') step="1" @endif @if(in_array($type, ['number','decimal'])) min="0" @endif data-field-name="attributes[{{ $key }}]" data-required="{{ $required ? 1 : 0 }}" value="{{ $value ?? '' }}">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="component-editor-actions">
            <button class="btn" type="submit">Сохранить</button>
            <a class="btn ghost" href="{{ route('admin.components.index') }}">Отмена</a>
        </div>
    </form>
</div>
<script>
(function () {
    const category = document.getElementById('component-category');
    const groups = document.querySelectorAll('.component-spec-group');
    function updateFields() {
        groups.forEach(function (group) {
            const active = group.dataset.category === category.value;
            group.hidden = !active;
            group.querySelectorAll('[data-field-name]').forEach(function (input) {
                input.disabled = !active;
                input.name = active ? input.dataset.fieldName : '';
                if (input.dataset.required === '1' && input.type !== 'checkbox') {
                    input.required = active;
                }
            });
        });
    }
    category.addEventListener('change', updateFields);
    updateFields();
})();
</script>
@endsection
