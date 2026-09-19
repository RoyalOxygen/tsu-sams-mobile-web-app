<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use PDOStatement;

final class Database
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $db = self::$config['db'];
        try {
            if (($db['driver'] ?? 'sqlite') === 'mysql') {
                self::$pdo = self::connectMysql($db);
            } else {
                self::$pdo = self::connectSqlite($db);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Database connection failed.';
            exit;
        }
        return self::$pdo;
    }

    /**
     * Connect to MySQL, creating the database first if it does not exist.
     * Falls back to a server-level connection so first-run installs work.
     */
    private static function connectMysql(array $db): PDO
    {
        $base = sprintf(
            'mysql:host=%s;port=%d;charset=%s',
            $db['host'],
            $db['port'],
            $db['charset']
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        try {
            $pdo = new PDO($base . ';dbname=' . $db['name'], $db['user'], $db['pass'], $options);
        } catch (PDOException $e) {
            $pdo = new PDO($base, $db['user'], $db['pass'], $options);
            $pdo->exec(
                'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', (string)$db['name'])
                . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            $pdo->exec('USE `' . str_replace('`', '', (string)$db['name']) . '`');
        }
        return $pdo;
    }

    private static function connectSqlite(array $db): PDO
    {
        $path = $db['sqlite_path'];
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA temp_store = MEMORY');
        $pdo->exec('PRAGMA cache_size = -16000');
        return $pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $place = implode(',', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . $place . ')';
        self::query($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        $vals = [];
        foreach ($data as $k => $v) {
            $sets[] = $k . ' = ?';
            $vals[] = $v;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(',', $sets) . ' WHERE ' . $where;
        $stmt = self::query($sql, array_merge($vals, $whereParams));
        return $stmt->rowCount();
    }

    /**
     * Execute a multi-statement SQL file. Statements are split on semicolons
     * that terminate a line, which is sufficient for the bundled schemas.
     */
    public static function runSql(string $sql): void
    {
        $pdo = self::pdo();
        foreach (self::splitStatements($sql) as $statement) {
            $pdo->exec($statement);
        }
    }

    /**
     * @return list<string>
     */
    public static function splitStatements(string $sql): array
    {
        $out = [];
        foreach (preg_split('/;\s*[\r\n]+/', $sql) ?: [] as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '' || str_starts_with($chunk, '--')) {
                continue;
            }
            $out[] = rtrim($chunk, ';');
        }
        return $out;
    }

    public static function driver(): string
    {
        return self::$config['db']['driver'] ?? 'sqlite';
    }

    public static function tableExists(string $table): bool
    {
        try {
            self::pdo()->query('SELECT 1 FROM ' . $table . ' LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
