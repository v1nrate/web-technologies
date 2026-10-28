<?php
const DB_HOST = 'localhost';
const DB_NAME = 'notebook';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function valid_csrf(): bool
{
    return isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        && hash_equals(csrf_token(), $_POST['csrf_token']);
}

function contact_fields(array $source): array
{
    $keys = ['surname','name','patronymic','gender','birth_date','phone','address','email','comment'];
    $out = [];
    foreach ($keys as $key) {
        $out[$key] = trim((string)($source[$key] ?? ''));
    }
    return $out;
}

function contact_error(array $v): ?string
{
    if ($v['surname'] === '' || $v['name'] === '') return 'Заполните фамилию и имя.';
    if (mb_strlen($v['surname']) > 100 || mb_strlen($v['name']) > 100 || mb_strlen($v['patronymic']) > 100) return 'Слишком длинное ФИО.';
    if (!in_array($v['gender'], ['', 'мужской', 'женский'], true)) return 'Некорректное значение пола.';
    if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) return 'Некорректный email.';
    if ($v['birth_date'] !== '') {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v['birth_date']);
        if (!$d || $d->format('Y-m-d') !== $v['birth_date']) return 'Некорректная дата рождения.';
    }
    if (mb_strlen($v['phone']) > 50 || mb_strlen($v['address']) > 255 || mb_strlen($v['email']) > 254 || mb_strlen($v['comment']) > 2000) return 'Одно из полей слишком длинное.';
    return null;
}

function db_values(array $v): array
{
    foreach (['patronymic','gender','birth_date','phone','address','email','comment'] as $key) {
        if ($v[$key] === '') $v[$key] = null;
    }
    return $v;
}
