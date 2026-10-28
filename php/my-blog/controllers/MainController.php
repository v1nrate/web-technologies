<?php
require_once __DIR__ . '/../view.php';
class MainController
{
    public function index(): void
    {
        render('home');
    }

    public function sayHello(string $name): void
    {
        render('message', ['title' => 'Страница приветствия', 'message' => 'Привет, ' . $name]);
    }

    public function sayBye(string $name): void
    {
        render('message', ['message' => 'Пока, ' . $name]);
    }
}
