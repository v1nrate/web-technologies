<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController
{
    public function index(Request $request)
    {
        $query = Article::where('status', 'published');
        if ($request->filled('search')) {
            $term = $request->string('search')->toString();
            $query->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('excerpt', 'like', "%{$term}%"));
        }
        if (array_key_exists($request->input('category'), Article::CATEGORIES)) $query->where('category', $request->input('category'));
        if ($request->input('sort') === 'oldest') $query->oldest();
        else $query->latest();
        return view('articles.index', ['articles' => $query->paginate(9)->withQueryString(), 'categories' => Article::CATEGORIES]);
    }

    public function show(Article $article)
    {
        abort_if($article->status !== 'published' && !auth()->user()?->is_admin, 404);
        $related = Article::where('status', 'published')->where('category', $article->category)->whereKeyNot($article->id)->latest()->limit(3)->get();
        return view('articles.show', compact('article', 'related'));
    }
}
