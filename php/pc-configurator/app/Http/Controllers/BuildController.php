<?php

namespace App\Http\Controllers;

use App\Models\Build;
use App\Models\Component;
use App\Services\CompatibilityService;
use Illuminate\Http\Request;

class BuildController
{
    private const CATEGORIES = ['cpu', 'motherboard', 'gpu', 'ram', 'ssd', 'psu', 'case', 'cooler'];

    public function index()
    {
        return view('builds.index', ['groups' => Component::orderBy('price')->get()->groupBy('category'), 'categories' => self::CATEGORIES]);
    }

    private function parts(Request $request, CompatibilityService $service): array
    {
        $data = $request->validate(['title' => 'required|string|max:100', 'parts' => 'required|array|max:8', 'parts.*' => 'nullable|integer|exists:components,id']);
        $ids = array_values(array_filter($data['parts']));
        $items = Component::whereIn('id', $ids)->get();
        if ($items->count() !== count(array_unique($ids)) || $items->pluck('category')->unique()->count() !== $items->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['parts' => 'Выбраны повторяющиеся или некорректные комплектующие.']);
        }
        foreach ($data['parts'] as $category => $id) {
            if (!in_array($category, self::CATEGORIES, true) || ($id && $items->firstWhere('id', (int) $id)?->category !== $category)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['parts' => 'Некорректная категория комплектующего.']);
            }
        }
        $check = $service->check($items);
        return ['title' => $data['title'], 'items' => $items->pluck('id')->all(), 'total_price' => $items->sum('price'), 'warnings' => $check['warnings']];
    }

    public function preview(Request $request, CompatibilityService $service)
    {
        $data = $request->validate(['parts' => 'sometimes|array|max:8', 'parts.*' => 'nullable|integer|exists:components,id']);
        $selection = $data['parts'] ?? [];
        foreach ($selection as $category => $id) {
            if (!in_array($category, self::CATEGORIES, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['parts' => 'Неизвестная категория.']);
            }
        }
        $ids = array_values(array_filter($selection));
        if (count($ids) !== count(array_unique($ids))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['parts' => 'Комплектующие не должны повторяться.']);
        }
        $items = Component::whereIn('id', $ids)->get();
        foreach ($selection as $category => $id) {
            if ($id && $items->firstWhere('id', (int) $id)?->category !== $category) {
                throw \Illuminate\Validation\ValidationException::withMessages(['parts' => 'Категория комплектующего не совпадает.']);
            }
        }
        $check = $service->check($items);
        $baseline = collect($check['issues'])->where('severity', 'error')->count();
        $alternatives = [];
        foreach ($check['issues'] as $index => $issue) {
            if ($issue['severity'] !== 'error') {
                continue;
            }
            foreach ($issue['categories'] as $category) {
                $options = [];
                $current = $items->firstWhere('category', $category);
                foreach (Component::where('category', $category)->orderBy('price')->get() as $candidate) {
                    if ($current && $candidate->id === $current->id) {
                        continue;
                    }
                    $trial = $items->reject(fn ($part) => $part->category === $category)->push($candidate);
                    $result = $service->check($trial);
                    $trialErrors = collect($result['issues'])->where('severity', 'error');
                    if ($trialErrors->count() >= $baseline || $trialErrors->contains(fn ($error) => $error['title'] === $issue['title'])) {
                        continue;
                    }
                    $options[] = ['id' => $candidate->id, 'name' => $candidate->name, 'price' => $candidate->price, 'category' => $category];
                    if (count($options) >= 5) {
                        break;
                    }
                }
                if ($options) {
                    $alternatives[$index][$category] = $options;
                }
            }
        }
        return response()->json(['check' => $check, 'alternatives' => $alternatives, 'total' => $items->sum('price')]);
    }

    public function calculate(Request $request, CompatibilityService $service)
    {
        $data = $this->parts($request, $service);
        $data['user_id'] = $request->user()?->id;
        return redirect()->route('builds.show', Build::create($data));
    }

    public function show(Build $build, CompatibilityService $service)
    {
        $items = Component::whereIn('id', $build->items)->get();
        return view('builds.show', ['build' => $build, 'items' => $items, 'check' => $service->check($items)]);
    }

    public function edit(Request $request, Build $build)
    {
        abort_unless($build->user_id && $build->user_id === $request->user()->id, 403);
        return view('builds.index', ['build' => $build, 'groups' => Component::orderBy('price')->get()->groupBy('category'), 'categories' => self::CATEGORIES]);
    }

    public function update(Request $request, Build $build, CompatibilityService $service)
    {
        abort_unless($build->user_id && $build->user_id === $request->user()->id, 403);
        $build->update($this->parts($request, $service));
        return redirect()->route('builds.show', $build)->with('status', 'Сборка обновлена.');
    }

    public function duplicate(Request $request, Build $build)
    {
        abort_unless($build->user_id && $build->user_id === $request->user()->id, 403);

        $copy = Build::create([
            'title' => mb_substr('Копия — ' . $build->title, 0, 100),
            'items' => $build->items ?? [],
            'total_price' => $build->total_price,
            'warnings' => $build->warnings ?? [],
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('builds.edit', $copy)->with('status', 'Копия сборки создана.');
    }

    public function destroy(Request $request, Build $build)
    {
        abort_unless($build->user_id && $build->user_id === $request->user()->id, 403);
        $build->delete();
        return redirect()->route('account')->with('status', 'Сборка удалена.');
    }
}
