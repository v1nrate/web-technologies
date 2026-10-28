<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Component;
use App\Models\Build;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController
{
    private function guard(Request $request): void { abort_unless($request->user()?->is_admin, 403); }
    public function index(Request $request)
    {
        $this->guard($request);
        return view('admin.dashboard', ['componentCount' => Component::count(), 'articleCount' => Article::count(), 'userCount' => User::count(), 'buildCount' => Build::count(), 'recentBuilds' => Build::with('user')->latest()->limit(6)->get(), 'categories' => Component::selectRaw('category, count(*) as total')->groupBy('category')->orderBy('category')->get(), 'recentUsers' => User::latest()->limit(5)->get(), 'recentArticles' => Article::latest()->limit(5)->get(), 'guestBuildCount' => Build::whereNull('user_id')->count()]);
    }
    public function componentForm(Request $request, ?Component $component = null)
    {
        $this->guard($request);
        return view('admin.component', ['component' => $component]);
    }
    public function componentSave(Request $request, ?Component $component = null)
    {
        $this->guard($request);
        $categories = config('component_specs');
        $category = $request->input('category');
        $definition = $categories[$category] ?? null;
        if (!$definition) {
            return back()->withErrors(['category' => 'Выберите допустимую категорию.'])->withInput();
        }
        $rules = [
            'category' => ['required', Rule::in(array_keys($categories))],
            'brand' => 'required|string|max:80',
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0|max:999999999',
            'power_watts' => 'required|integer|min:0|max:10000',
        ];
        foreach ($definition['fields'] as $key => $field) {
            [$label, $type] = $field;
            $options = $field[2] ?? [];
            $required = $field[3] ?? false;
            $prefix = $required ? 'required' : 'nullable';
            $rules["attributes.$key"] = match ($type) {
                'number' => "$prefix|integer|min:0|max:1000000",
                'decimal' => "$prefix|numeric|min:0|max:1000000",
                'select' => [$prefix, Rule::in($options)],
                'boolean' => "$prefix|boolean",
                'multiple' => [$prefix, 'array', 'min:1'],
                default => "$prefix|string|max:255",
            };
            if ($type === 'multiple') {
                $rules["attributes.$key.*"] = ['string', Rule::in($options)];
            }
        }
        $data = $request->validate($rules);
        $specs = [];
        foreach ($definition['fields'] as $key => $field) {
            $type = $field[1];
            $value = $data['attributes'][$key] ?? null;
            if ($type === 'boolean') {
                $specs[$key] = (bool) $value;
            } elseif ($type === 'multiple') {
                $specs[$key] = array_values($value ?? []);
            } elseif ($value !== null && $value !== '') {
                $specs[$key] = match ($type) {
                    'number' => (int) $value,
                    'decimal' => (float) $value,
                    default => $value,
                };
            }
        }
        unset($data['attributes']);
        $data['specs'] = $specs;
        if ($component) $component->update($data);
        else Component::create($data);
        return redirect()->route('admin.components.index')->with('status', 'Комплектующее сохранено.');
    }
    public function componentDelete(Request $request, Component $component)
    {
        $this->guard($request);
        if ($this->referencedIds([$component->id])) return back()->withErrors(['component' => 'Комплектующее используется в сохранённой сборке. Сначала удалите сборку.']);
        $component->delete();
        return back()->with('status', 'Комплектующее удалено.');
    }
    public function articleForm(Request $request, ?Article $article = null)
    {
        $this->guard($request);
        return view('admin.article', ['article' => $article, 'categories' => Article::CATEGORIES]);
    }
    public function articleSave(Request $request, ?Article $article = null)
    {
        $this->guard($request);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => 'required|string|max:2000',
            'body' => 'required|string|max:150000',
            'author' => 'required|string|max:255',
            'category' => ['required', Rule::in(array_keys(Article::CATEGORIES))],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'cover_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'],
        ]);
        if ($article) $article->update($data);
        else Article::create($data);
        return redirect()->route('admin.articles.index')->with('status', 'Статья сохранена.');
    }
    public function articleDelete(Request $request, Article $article)
    {
        $this->guard($request);
        $article->delete();
        return back()->with('status', 'Статья удалена.');
    }

    private const CATEGORY_LABELS = [
        'cpu' => 'Процессоры', 'motherboard' => 'Материнские платы',
        'gpu' => 'Видеокарты', 'ram' => 'Оперативная память',
        'ssd' => 'Накопители', 'psu' => 'Блоки питания',
        'case' => 'Корпуса', 'cooler' => 'Охлаждение',
    ];

    private function filteredComponents(Request $request)
    {
        $query = Component::query();
        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%"));
        }
        if (array_key_exists($request->input('category'), self::CATEGORY_LABELS)) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('brand')) $query->where('brand', $request->input('brand'));
        if ($request->filled('min_price') && is_numeric($request->input('min_price'))) $query->where('price', '>=', max(0, (int) $request->input('min_price')));
        if ($request->filled('max_price') && is_numeric($request->input('max_price'))) $query->where('price', '<=', max(0, (int) $request->input('max_price')));
        if ($request->filled('socket')) $query->where('specs->socket', $request->input('socket'));
        if ($request->filled('memory_type')) $query->where('specs->memory_type', $request->input('memory_type'));
        if ($request->filled('form_factor')) $query->where('specs->form_factor', $request->input('form_factor'));
        return match ($request->input('sort', 'newest')) {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderBy('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };
    }

    public function components(Request $request)
    {
        $this->guard($request);
        return view('admin.components', [
            'components' => $this->filteredComponents($request)->paginate(20)->withQueryString(),
            'brands' => Component::distinct()->orderBy('brand')->pluck('brand'),
            'categories' => self::CATEGORY_LABELS,
            'statistics' => Component::selectRaw('category, COUNT(*) as total')->groupBy('category')->pluck('total', 'category'),
            'totalComponents' => Component::count(),
        ]);
    }

    private function referencedIds(array $ids): array
    {
        $wanted = array_fill_keys(array_map('intval', $ids), true);
        $used = [];
        Build::query()->select(['id', 'items'])->chunkById(200, function ($builds) use (&$used, $wanted) {
            foreach ($builds as $build) {
                foreach (($build->items ?? []) as $id) {
                    if (isset($wanted[(int) $id])) $used[(int) $id] = true;
                }
            }
        });
        return array_keys($used);
    }

    public function bulkDeleteComponents(Request $request)
    {
        $this->guard($request);
        $data = $request->validate(['ids' => 'required|array|min:1|max:200', 'ids.*' => 'required|integer|distinct|exists:components,id']);
        $referenced = $this->referencedIds($data['ids']);
        if ($referenced) return back()->withErrors(['bulk' => 'Удаление отменено: '.count($referenced).' выбранных комплектующих используются в сохранённых сборках. Сначала удалите соответствующие сборки.']);
        $count = Component::whereIn('id', $data['ids'])->delete();
        return back()->with('status', "Удалено комплектующих: {$count}.");
    }

    public function exportComponents(Request $request): StreamedResponse
    {
        $this->guard($request);
        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['category', 'brand', 'name', 'price', 'power_watts', 'specs'], ';');
            foreach ($this->filteredComponents($request)->cursor() as $component) {
                fputcsv($handle, [
                    $component->category, $component->brand, $component->name,
                    $component->price, $component->power_watts,
                    json_encode($component->specs ?? new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ], ';');
            }
            fclose($handle);
        }, 'components-'.date('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function readComponentCsv(Request $request): array
    {
        $request->validate(['file' => 'required|file|max:5120']);
        $file = $request->file('file');
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Нужен файл CSV или TXT.']);
        }
        $handle = fopen($file->getRealPath(), 'rb');
        if (!$handle) throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Не удалось открыть файл.']);
        try {
            $header = fgetcsv($handle, 0, ';');
            if (!$header) throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Пустой CSV.']);
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $required = ['category', 'brand', 'name', 'price', 'power_watts', 'specs'];
            if (array_diff($required, $header) || count(array_unique($header)) !== count($header)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Нужны уникальные столбцы: '.implode(';', $required)]);
            }
            $rows = []; $errors = []; $line = 1;
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $line++;
                if ($row === [null]) continue;
                if (count($rows) >= 500) { $errors[] = 'Максимум 500 строк данных.'; break; }
                if (count($row) !== count($header)) { $errors[] = "Строка {$line}: неверное количество столбцов."; continue; }
                $item = array_combine($header, $row);
                $specs = json_decode($item['specs'], true);
                if (trim($item['specs']) === '{}') $specs = [];
                $data = [
                    'category' => $item['category'], 'brand' => trim($item['brand']), 'name' => trim($item['name']),
                    'price' => $item['price'], 'power_watts' => $item['power_watts'], 'specs' => $specs,
                ];
                $validator = Validator::make($data, [
                    'category' => ['required', Rule::in(array_keys(self::CATEGORY_LABELS))],
                    'brand' => 'required|string|max:80', 'name' => 'required|string|max:255',
                    'price' => 'required|integer|min:0|max:999999999',
                    'power_watts' => 'required|integer|min:0|max:10000', 'specs' => 'present|array',
                ]);
                if ($validator->fails() || !is_array($specs) || (count($specs) && array_is_list($specs))) {
                    $errors[] = "Строка {$line}: неверные значения категории, цены, мощности, названия или JSON.";
                    continue;
                }
                $data['price'] = (int) $data['price'];
                $data['power_watts'] = (int) $data['power_watts'];
                $rows[] = $data;
            }
            return [$rows, $errors];
        } finally { fclose($handle); }
    }

    public function previewImportComponents(Request $request)
    {
        $this->guard($request);
        [$rows, $errors] = $this->readComponentCsv($request);
        return back()->with('csv_preview', [
            'total' => count($rows), 'errors' => array_slice($errors, 0, 15),
            'examples' => array_slice(array_map(fn ($row) => $row['brand'].' '.$row['name'], $rows), 0, 8),
        ]);
    }

    public function importComponents(Request $request)
    {
        $this->guard($request);
        [$rows, $errors] = $this->readComponentCsv($request);
        if ($errors) return back()->withErrors(['file' => 'Импорт отменён. '.implode(' ', array_slice($errors, 0, 8))]);
        if (!$rows) return back()->withErrors(['file' => 'Нет записей для импорта.']);
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                Component::updateOrCreate(
                    ['category' => $row['category'], 'brand' => $row['brand'], 'name' => $row['name']], $row
                );
            }
        });
        return back()->with('status', 'Импортировано или обновлено записей: '.count($rows));
    }

    public function articles(Request $request)
    {
        $this->guard($request);
        $query = Article::query();
        if ($request->filled('search')) {
            $term = $request->string('search')->toString();
            $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('author', 'like', "%{$term}%"));
        }
        if (array_key_exists($request->input('category'), Article::CATEGORIES)) $query->where('category', $request->input('category'));
        if (in_array($request->input('status'), ['draft', 'published'], true)) $query->where('status', $request->input('status'));
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title'),
            default => $query->latest(),
        };
        return view('admin.articles', ['articles' => $query->paginate(15)->withQueryString(), 'categories' => Article::CATEGORIES]);
    }

    public function users(Request $request)
    {
        $this->guard($request);
        $query = User::withCount('builds');
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
        }
        return view('admin.users', ['users' => $query->latest()->paginate(20)->withQueryString()]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $this->guard($request);
        $data = $request->validate(['is_admin' => 'required|boolean']);
        if ($user->id === $request->user()->id) return back()->withErrors(['role' => 'Нельзя менять собственную роль.']);
        if (!$data['is_admin'] && $user->is_admin && User::where('is_admin', true)->count() <= 1) return back()->withErrors(['role' => 'Нельзя удалить последнего администратора.']);
        $user->is_admin = (bool)$data['is_admin'];
        $user->save();
        return back()->with('status', 'Роль пользователя обновлена.');
    }

    public function builds(Request $request)
    {
        $this->guard($request);
        $query = Build::with('user');
        if ($request->filled('search')) $query->where('title', 'like', '%'.$request->input('search').'%');
        return view('admin.builds', ['builds' => $query->latest()->paginate(20)->withQueryString()]);
    }

    public function deleteBuild(Request $request, Build $build)
    {
        $this->guard($request);
        $build->delete();
        return back()->with('status', 'Сборка удалена.');
    }

}
