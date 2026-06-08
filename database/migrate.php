<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/database.php';

function connect(array $config, bool $withDatabase): PDO
{
    $database = $withDatabase ? ';dbname=' . $config['database'] : '';
    $dsn = sprintf(
        'mysql:host=%s;port=%s%s;charset=%s',
        $config['host'],
        $config['port'],
        $database,
        $config['charset']
    );

    return new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

$server = connect($config, false);
$server->exec(
    sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $config['database'])
    )
);

$pdo = connect($config, true);
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(190) NOT NULL UNIQUE,
        migrated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = $pdo
    ->query('SELECT migration FROM migrations ORDER BY migration')
    ->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied);

$migrationFiles = glob(__DIR__ . '/migrations/*.php') ?: [];
sort($migrationFiles);

foreach ($migrationFiles as $file) {
    $name = basename($file);

    if (isset($applied[$name])) {
        echo "skip  {$name}\n";
        continue;
    }

    echo "run   {$name}\n";
    $migration = require $file;

    try {
        $migration($pdo);
        $statement = $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
        $statement->execute([$name]);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

echo "done  {$config['database']}\n";
