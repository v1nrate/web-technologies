<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/controllers/MainController.php';
require_once __DIR__ . '/controllers/ArticlesController.php';

$router = new Router();
$router->get('/', [MainController::class, 'index']);
$router->get('/hello/{name}', [MainController::class, 'sayHello']);
$router->get('/bye/{name}', [MainController::class, 'sayBye']);
$router->get('/articles/{id}', [ArticlesController::class, 'show']);
$router->dispatch();
