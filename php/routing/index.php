<?php

declare(strict_types=1);

require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/controllers/MainController.php';

$router = new Router();

$router->get('/', [MainController::class, 'index']);
$router->get('/hello/{name}', [MainController::class, 'sayHello']);
$router->get('/bye/{name}', [MainController::class, 'sayBye']);

$router->dispatch();
