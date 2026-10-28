CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    CONSTRAINT fk_articles_users FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (id, nickname) VALUES
    (1, 'Ruslan'),
    (2, 'Admin')
ON DUPLICATE KEY UPDATE nickname = VALUES(nickname);

INSERT INTO articles (id, user_id, title, body) VALUES
    (1, 1, 'Статья 1', 'Всем привет, это текст первой статьи'),
    (2, 2, 'Статья 2', 'Всем привет, это текст второй статьи')
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), title = VALUES(title), body = VALUES(body);
