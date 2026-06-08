<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $columnExists = $pdo->query(
        "SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'combos'
           AND COLUMN_NAME = 'is_verified'"
    )->fetchColumn();

    if ((int) $columnExists === 0) {
        $pdo->exec('ALTER TABLE combos ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 1 AFTER difficulty');
    }

    $pdo->exec('UPDATE combos SET is_verified = 1');
    $pdo->exec(
        "UPDATE combos
         SET is_verified = 0
         WHERE id LIKE '%-core-%'
            OR id LIKE '%-cancel-rush-%'
            OR notes LIKE '%仮登録%'"
    );
};
