<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Component;

class HomeController
{
    public function index()
    {
        return view('home', ['count' => Component::count(), 'articles' => Article::latest()->take(3)->get()]);
    }
}
