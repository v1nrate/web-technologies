<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/builder', [BuildController::class, 'index'])->name('builder');
Route::post('/builder', [BuildController::class, 'calculate'])->middleware('throttle:15,1')->name('builder.calculate');
Route::post('/builder/preview', [BuildController::class, 'preview'])->middleware('throttle:60,1')->name('builder.preview');
Route::get('/builds/{build}', [BuildController::class, 'show'])->name('builds.show');
Route::get('/articles', [ArticleController::class, 'index'])->name('articles');
Route::get('/articles/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.submit');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/account/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/account/password', [ProfileController::class, 'password'])->middleware('throttle:6,1')->name('profile.password');
    Route::get('/builds/{build}/edit', [BuildController::class, 'edit'])->name('builds.edit');
    Route::put('/builds/{build}', [BuildController::class, 'update'])->name('builds.update');
    Route::post('/builds/{build}/duplicate', [BuildController::class, 'duplicate'])->middleware('throttle:15,1')->name('builds.duplicate');
    Route::delete('/builds/{build}', [BuildController::class, 'destroy'])->name('builds.destroy');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/components/new', [AdminController::class, 'componentForm'])->name('admin.components.new');
    Route::post('/admin/components', [AdminController::class, 'componentSave'])->name('admin.components.store');
    Route::get('/admin/components/{component}/edit', [AdminController::class, 'componentForm'])->name('admin.components.edit');
    Route::put('/admin/components/{component}', [AdminController::class, 'componentSave'])->name('admin.components.update');
    Route::delete('/admin/components/{component}', [AdminController::class, 'componentDelete'])->name('admin.components.destroy');
    Route::get('/admin/articles/new', [AdminController::class, 'articleForm'])->name('admin.articles.new');
    Route::post('/admin/articles', [AdminController::class, 'articleSave'])->name('admin.articles.store');
    Route::get('/admin/articles/{article}/edit', [AdminController::class, 'articleForm'])->name('admin.articles.edit');
    Route::put('/admin/articles/{article}', [AdminController::class, 'articleSave'])->name('admin.articles.update');
    Route::delete('/admin/articles/{article}', [AdminController::class, 'articleDelete'])->name('admin.articles.destroy');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/components', [AdminController::class, 'components'])->name('components.index');
    Route::get('/components/export', [AdminController::class, 'exportComponents'])->name('components.export');
    Route::post('/components/import/preview', [AdminController::class, 'previewImportComponents'])->name('components.import.preview');
    Route::post('/components/import', [AdminController::class, 'importComponents'])->name('components.import');
    Route::post('/components/bulk-delete', [AdminController::class, 'bulkDeleteComponents'])->name('components.bulk-delete');
    Route::get('/articles', [AdminController::class, 'articles'])->name('articles.index');
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
    Route::get('/builds', [AdminController::class, 'builds'])->name('builds.index');
    Route::delete('/builds/{build}', [AdminController::class, 'deleteBuild'])->name('builds.destroy');
});
