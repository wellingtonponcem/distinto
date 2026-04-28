<?php
require_once __DIR__ . '/env.php';

class Database {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]);

                // Migração de Nível de Usuário
                try {
                    $stmt = self::$instance->query("SHOW COLUMNS FROM users LIKE 'nivel'");
                    if (!$stmt->fetch()) {
                        self::$instance->exec("ALTER TABLE users ADD COLUMN nivel INT NOT NULL DEFAULT 0");
                        self::$instance->exec("UPDATE users SET nivel = 1 ORDER BY id ASC LIMIT 1");
                    }
                } catch (Exception $e) {}
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['erro' => 'Falha na conexão com o banco de dados.']));
            }
        }
        return self::$instance;
    }
}
