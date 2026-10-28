@extends('layouts.app')
@section('title', 'Конфигуратор ПК')
@section('content')
<div class="page-heading"><span class="eyebrow">СОБЕРИ СВОЙ ПК</span><h1>Конфигуратор</h1><p>Выбери комплектующие. После расчёта появятся стоимость и результаты проверок совместимости.</p></div>
@if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ isset($build) ? route('builds.update', $build) : route('builder.calculate') }}" class="builder">@csrf @isset($build) @method('PUT') @endisset
<div class="builder-main"><div class="builder-filter-toolbar"><label><input type="checkbox" id="only-compatible"> Показывать только совместимые варианты</label><span id="builder-filter-note" class="muted">Фильтр учитывает известные параметры совместимости</span></div><label class="field-label" for="title">Название сборки</label><input id="title" name="title" maxlength="100" value="{{ old('title', $build->title ?? 'Моя сборка') }}" required>
@foreach(['cpu'=>'Процессор','motherboard'=>'Материнская плата','gpu'=>'Видеокарта','ram'=>'Оперативная память','ssd'=>'SSD','psu'=>'Блок питания','case'=>'Корпус','cooler'=>'Охлаждение'] as $key=>$label)<div class="builder-row"><div><span class="eyebrow">{{ strtoupper($key) }}</span><h3>{{ $label }}</h3></div><select name="parts[{{ $key }}]" class="part-select" data-category="{{ $key }}"><option value="" data-price="0">Не выбран</option>@foreach($groups->get($key, collect()) as $part)<option value="{{ $part->id }}" data-price="{{ $part->price }}" data-specs="{!! e(json_encode($part->specs ?? [], JSON_UNESCAPED_UNICODE)) !!}" @selected(old('parts.'.$key, isset($build) ? (collect($build->items)->contains($part->id) ? $part->id : null) : null)==$part->id)>{{ $part->name }} — {{ number_format($part->price,0,',',' ') }} ₽</option>@endforeach</select></div>@endforeach</div>
<aside class="builder-summary"><span class="eyebrow">ВАША КОНФИГУРАЦИЯ</span><h2>Итоговая стоимость</h2><div id="live-total" class="total">0 ₽</div><div id="live-compatibility" class="live-compatibility" aria-live="polite"><p class="muted">Проверяем совместимость…</p></div><p class="muted">Предварительный расчёт. Итоговая цена пересчитывается на сервере.</p><button class="btn wide" type="submit">{{ isset($build) ? 'Сохранить изменения →' : 'Рассчитать и сохранить →' }}</button><p class="tiny">Сборки доступны по ссылке. Для управления своими сборками войдите в аккаунт.</p></aside></form>
<script>
const selects = [...document.querySelectorAll('.part-select')];
const total = document.querySelector('#live-total');
const panel = document.querySelector('#live-compatibility');
const previewUrl = @json(route('builder.preview'));
const token = @json(csrf_token());
const onlyCompatible = document.querySelector('#only-compatible');
const filterNote = document.querySelector('#builder-filter-note');
function specs(option) { try { return JSON.parse(option?.dataset.specs || '{}'); } catch { return {}; } }
function compatible(category, candidate, chosen) {
    const cpu = chosen.cpu, board = chosen.motherboard, ram = chosen.ram, computerCase = chosen.case, gpu = chosen.gpu, cooler = chosen.cooler;
    const a = specs(candidate);
    const b = key => specs(chosen[key]);
    const match = (left, right) => !left || !right || left === right;
    if (category === 'cpu') return match(a.socket, b('motherboard').socket) && (!cooler || !a.socket || !Array.isArray(b('cooler').sockets) || b('cooler').sockets.includes(a.socket));
    if (category === 'motherboard') return match(a.socket, b('cpu').socket) && match(a.memory_type, b('ram').memory_type) && (!computerCase || !a.form_factor || !Array.isArray(b('case').form_factors) || b('case').form_factors.includes(a.form_factor));
    if (category === 'ram') return match(a.memory_type, b('motherboard').memory_type) && (!b('motherboard').max_ram_gb || !a.capacity_gb || Number(a.capacity_gb) <= Number(b('motherboard').max_ram_gb)) && (!b('motherboard').ram_slots || !a.modules || Number(a.modules) <= Number(b('motherboard').ram_slots));
    if (category === 'gpu') return !b('case').max_gpu_mm || !a.length_mm || Number(a.length_mm) <= Number(b('case').max_gpu_mm);
    if (category === 'cooler') return (!cpu || !b('cpu').socket || !Array.isArray(a.sockets) || a.sockets.includes(b('cpu').socket)) && (!b('case').max_cooler_mm || !a.height_mm || Number(a.height_mm) <= Number(b('case').max_cooler_mm));
    if (category === 'case') return (!board || !b('motherboard').form_factor || !Array.isArray(a.form_factors) || a.form_factors.includes(b('motherboard').form_factor)) && (!gpu || !a.max_gpu_mm || !b('gpu').length_mm || Number(b('gpu').length_mm) <= Number(a.max_gpu_mm)) && (!cooler || !a.max_cooler_mm || !b('cooler').height_mm || Number(b('cooler').height_mm) <= Number(a.max_cooler_mm));
    if (category === 'ssd') return !board || !a.interface || !((a.interface.startsWith('NVMe') || String(a.form_factor || '').startsWith('M.2')) && Number(b('motherboard').m2_slots) === 0) && !(a.interface === 'SATA' && Number(b('motherboard').sata_ports) === 0);
    if (category === 'psu') return !computerCase || !a.length_mm || !b('case').max_psu_mm || Number(a.length_mm) <= Number(b('case').max_psu_mm);
    return true;
}
function applyCompatibleFilter() {
    const chosen = Object.fromEntries(selects.map(select => [select.dataset.category, select.selectedOptions[0]?.value ? select.selectedOptions[0] : null]));
    let hidden = 0;
    selects.forEach(select => {
        [...select.options].forEach(option => {
            if (!option.value) return;
            const ok = !onlyCompatible.checked || option.selected || compatible(select.dataset.category, option, chosen);
            option.hidden = !ok;
            option.disabled = !ok;
            if (!ok) hidden++;
        });
    });
    filterNote.textContent = onlyCompatible.checked ? `Скрыто вариантов: ${hidden}. Проверяются известные характеристики.` : 'Фильтр учитывает известные параметры совместимости';
}
onlyCompatible.addEventListener('change', applyCompatibleFilter);
const categoryNames = {cpu: 'Процессор', motherboard: 'Материнская плата', gpu: 'Видеокарта', ram: 'Оперативная память', ssd: 'Накопитель', psu: 'Блок питания', case: 'Корпус', cooler: 'Охлаждение'};
let requestNumber = 0;
const money = value => new Intl.NumberFormat('ru-RU').format(value) + ' ₽';
function node(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;
    return element;
}
function render(check, alternatives) {
    panel.replaceChildren();
    const issues = Array.isArray(check.issues) ? check.issues : [];
    const missing = Array.isArray(check.missing) ? check.missing : [];
    const errors = issues.filter(issue => issue.severity === 'error').length;
    const warnings = issues.length - errors;
    const status = node('div', 'compact-status');
    status.append(node('strong', errors ? 'live-status-error' : warnings || missing.length ? 'live-status-warning' : 'live-status-ok', errors ? `${errors} критических ошибок` : warnings ? `${warnings} предупреждений` : missing.length ? 'Сборка не завершена' : 'Проверенные параметры совместимы'));
    if (missing.length) status.append(node('span', 'compact-count', `Не выбрано: ${missing.length}`));
    panel.append(status);
    const list = node('div', 'compact-issue-list');
    issues.forEach((issue, index) => {
        const details = node('details', 'compact-issue ' + (issue.severity === 'error' ? 'is-error' : 'is-warning'));
        const summary = node('summary', 'compact-issue-summary');
        const label = node('span', 'compact-issue-label');
        label.append(node('strong', '', issue.title), node('small', '', issue.severity === 'error' ? 'Критическая ошибка' : 'Предупреждение'));
        summary.append(node('span', 'compact-issue-symbol', issue.severity === 'error' ? '×' : '!'), label, node('span', 'compact-chevron', '⌄'));
        details.append(summary);
        const content = node('div', 'compact-issue-content');
        content.append(node('p', '', issue.message));
        if (issue.recommendation) content.append(node('p', 'compatibility-advice', 'Что сделать: ' + issue.recommendation));
        const groups = alternatives[index] || alternatives[String(index)] || {};
        const entries = Object.entries(groups).filter(([, candidates]) => Array.isArray(candidates) && candidates.length);
        if (entries.length) {
            const chooser = node('div', 'compact-replacement');
            chooser.append(node('div', 'compact-replacement-title', 'Подобрать замену'));
            const tabs = node('div', 'compact-replacement-tabs');
            const options = node('div', 'compact-replacement-options');
            entries.forEach(([category, candidates], position) => {
                const tab = node('button', 'compact-replacement-tab' + (position === 0 ? ' is-active' : ''), categoryNames[category] || category);
                tab.type = 'button';
                tab.addEventListener('click', () => {
                    tabs.querySelectorAll('button').forEach(button => button.classList.remove('is-active'));
                    tab.classList.add('is-active');
                    options.replaceChildren();
                    const select = node('select', 'live-replacement-select');
                    select.setAttribute('aria-label', 'Выберите совместимую замену');
                    select.append(new Option('Выберите модель', ''));
                    candidates.forEach(candidate => select.append(new Option(candidate.name + ' — ' + money(candidate.price), String(candidate.id))));
                    const apply = node('button', 'btn compact-apply', 'Применить');
                    apply.type = 'button';
                    apply.disabled = true;
                    select.addEventListener('change', () => { apply.disabled = !select.value; });
                    apply.addEventListener('click', () => {
                        const target = selects.find(item => item.dataset.category === category);
                        if (!target || !select.value) return;
                        target.value = select.value;
                        target.dispatchEvent(new Event('change', {bubbles: true}));
                        target.scrollIntoView({behavior: 'smooth', block: 'center'});
                    });
                    options.append(select, apply);
                });
                tabs.append(tab);
            });
            chooser.append(tabs, options);
            content.append(chooser);
            tabs.firstElementChild.click();
        }
        details.append(content);
        list.append(details);
    });
    panel.append(list);
    if (missing.length) {
        const details = node('details', 'compact-missing');
        details.append(node('summary', '', `Не выбраны комплектующие (${missing.length})`));
        details.append(node('p', '', missing.map(category => categoryNames[category] || category).join(', ')));
        panel.append(details);
    }
    const power = node('div', 'compact-power');
    const values = [['Потребление', check.estimated_watts], ['БП с запасом', check.recommended_psu_watts], ['Выбранный БП', check.psu_watts]];
    values.forEach(([label, value]) => {
        if (value === null || value === undefined) return;
        const line = node('div', 'compact-power-row');
        line.append(node('span', '', label), node('strong', '', `${value} Вт`));
        power.append(line);
    });
    panel.append(power);
}

async function update() {
    const parts = Object.fromEntries(selects.map(select => [select.dataset.category, select.value || null]));
    total.textContent = money(selects.reduce((sum, select) => sum + Number(select.selectedOptions[0]?.dataset.price || 0), 0));
    const number = ++requestNumber;
    panel.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(previewUrl, {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token}, body:JSON.stringify({parts})});
        if (!response.ok) throw new Error('Ошибка проверки: ' + response.status);
        const result = await response.json();
        if (number !== requestNumber) return;
        total.textContent = money(result.total);
        render(result.check, result.alternatives || {});
        panel.removeAttribute('aria-busy');
    } catch (error) {
        if (number === requestNumber) { panel.removeAttribute('aria-busy'); panel.replaceChildren(node('p', 'live-status-error', 'Не удалось проверить совместимость. ' + error.message)); }
    }
}
selects.forEach(select => select.addEventListener('change', () => { applyCompatibleFilter(); update(); }));
const preselectedCategory = @json(request('category'));
const preselectedComponent = @json(request('component'));
if (preselectedCategory && preselectedComponent && !@json(isset($build))) {
    const target = selects.find(select => select.dataset.category === preselectedCategory);
    if (target && [...target.options].some(option => option.value === String(preselectedComponent))) target.value = String(preselectedComponent);
}
applyCompatibleFilter();
update();
</script>
@endsection
