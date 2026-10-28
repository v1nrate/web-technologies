<?php

namespace App\Http\Controllers;

use App\Models\Component;
use Illuminate\Http\Request;

class CatalogController
{
    public function index(Request $request)
    {
        $definitions = config('component_specs', []);
        $category = $request->query('category');
        $query = Component::query();
        if (is_string($category) && array_key_exists($category, $definitions)) {
            $query->where('category', $category);
        } else {
            $category = null;
        }
        if ($request->filled('q')) {
            $term = str_replace(['%', '_'], ['\\%', '\\_'], mb_substr((string) $request->query('q'), 0, 100));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%");
            });
        }
        if ($request->filled('brand') && is_string($request->query('brand'))) {
            $query->where('brand', $request->query('brand'));
        }
        if ($request->filled('min_price') && is_numeric($request->query('min_price'))) {
            $query->where('price', '>=', max(0, (float) $request->query('min_price')));
        }
        if ($request->filled('max_price') && is_numeric($request->query('max_price'))) {
            $query->where('price', '<=', max(0, (float) $request->query('max_price')));
        }
        $fields = $category ? ($definitions[$category]['fields'] ?? []) : [];
        $filterable = [];
        foreach ($fields as $key => $definition) {
            if (($definition[1] ?? '') === 'select') {
                $filterable[$key] = $definition;
                if ($request->filled('spec_'.$key) && in_array($request->query('spec_'.$key), $definition[2] ?? [], true)) {
                    $query->where('specs->'.$key, $request->query('spec_'.$key));
                }
            }
        }
        $sort = $request->query('sort', 'price_asc');
        if ($sort === 'price_desc') $query->orderByDesc('price');
        elseif ($sort === 'name') $query->orderBy('name');
        elseif ($sort === 'newest') $query->orderByDesc('id');
        else $query->orderBy('price');
        $brands = Component::query()->when($category, fn ($q) => $q->where('category', $category))->distinct()->orderBy('brand')->pluck('brand');
        return view('components-catalog.index', [
            'components' => $query->paginate(18)->withQueryString(),
            'definitions' => $definitions,
            'selectedCategory' => $category,
            'filterable' => $filterable,
            'brands' => $brands,
        ]);
    }
}
