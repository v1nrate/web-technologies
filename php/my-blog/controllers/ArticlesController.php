<?php
require_once __DIR__ . '/../view.php';
class ArticlesController
{
    public function show(string $id): void
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            http_response_code(404);
            render('message', ['message' => 'Статья не найдена']);
            return;
        }

        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $statement = $pdo->prepare('SELECT id, title, body, user_id FROM articles WHERE id = ?');
            $statement->execute([(int) $id]);
            $article = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$article) {
                http_response_code(404);
                render('message', ['message' => 'Статья не найдена']);
                return;
            }

            $statement = $pdo->prepare('SELECT nickname FROM users WHERE id = ?');
            $statement->execute([$article['user_id']]);
            $author = $statement->fetch(PDO::FETCH_ASSOC);
            render('article', [
                'title' => $article['title'],
                'article' => $article,
                'nickname' => $author['nickname'] ?? 'Неизвестный автор'
            ]);
        } catch (PDOException $exception) {
            http_response_code(500);
            render('message', ['message' => 'Не удалось подключиться к базе данных']);
        }
    }
}
