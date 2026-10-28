<?php

namespace App\Http\Controllers;

use App\Models\Build;
use App\Models\Component;
use App\Services\CompatibilityService;
use Illuminate\Http\Request;

class AccountController
{
    public function index(Request $request, CompatibilityService $service)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'sort' => 'nullable|in:newest,oldest,price_asc,price_desc,title',
            'status' => 'nullable|in:all,compatible,errors,incomplete',
        ]);

        $query = $request->user()->builds();

        if ($search = trim($filters['q'] ?? '')) {
            $query->where('title', 'like', '%' . addcslashes($search, '%_\\') . '%');
        }

        switch ($filters['sort'] ?? 'newest') {
            case 'oldest': $query->orderBy('created_at')->orderBy('id'); break;
            case 'price_asc': $query->orderBy('total_price')->orderBy('id'); break;
            case 'price_desc': $query->orderByDesc('total_price')->orderByDesc('id'); break;
            case 'title': $query->orderBy('title')->orderBy('id'); break;
            default: $query->latest();
        }

        $builds = $query->get();
        $componentIds = $builds->flatMap(fn (Build $build) => $build->items ?? [])->unique()->all();
        $components = Component::whereIn('id', $componentIds)->get()->keyBy('id');
        $categories = ['cpu', 'motherboard', 'gpu', 'ram', 'ssd', 'psu', 'case', 'cooler'];

        $builds->each(function (Build $build) use ($components, $service, $categories) {
            $parts = collect($build->items ?? [])->map(fn ($id) => $components->get($id))->filter()->values();
            $result = $service->check($parts);
            $errors = collect($result['issues'] ?? [])->where('severity', 'error')->count();
            $missing = count(array_diff($categories, $parts->pluck('category')->all()));
            $build->account_errors = $errors;
            $build->account_missing = $missing;
            $build->account_parts_count = $parts->count();
            $build->account_status = $errors ? 'errors' : ($missing ? 'incomplete' : 'compatible');
        });

        $status = $filters['status'] ?? 'all';
        if ($status !== 'all') {
            $builds = $builds->filter(fn (Build $build) => $build->account_status === $status)->values();
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 9;
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $builds->forPage($page, $perPage)->values(),
            $builds->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('account.index', ['builds' => $paginator, 'filters' => $filters]);
    }
}
