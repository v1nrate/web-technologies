<?php

declare(strict_types=1);

class MainController
{
    public function index(): void
    {
        header('Content-Type: text/html; charset=UTF-8');

        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Роутинг</title></head><body>';
        echo '<h1>Главная страница</h1>';
        echo '<p><a href="hello/Руслан">Поздороваться</a></p>';
        echo '<p><a href="bye/Руслан">Попрощаться</a></p>';
        echo '</body></html>';
    }

    public function sayHello(string $name): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        echo 'Привет, ' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function sayBye(string $name): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        echo 'Пока, ' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
