<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS characters (
            id VARCHAR(64) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS combos (
            id VARCHAR(100) PRIMARY KEY,
            character_id VARCHAR(64) NOT NULL,
            starter VARCHAR(120) NOT NULL,
            situation VARCHAR(160) NOT NULL,
            damage_classic INT UNSIGNED NOT NULL,
            damage_modern INT UNSIGNED NULL,
            drive TINYINT UNSIGNED NOT NULL DEFAULT 0,
            super_art TINYINT UNSIGNED NOT NULL DEFAULT 0,
            difficulty TINYINT UNSIGNED NOT NULL DEFAULT 1,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_combos_character
                FOREIGN KEY (character_id) REFERENCES characters(id)
                ON UPDATE CASCADE
                ON DELETE CASCADE,
            INDEX idx_combos_character_starter (character_id, starter),
            INDEX idx_combos_character_situation (character_id, situation),
            INDEX idx_combos_cost (drive, super_art)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS combo_steps (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            combo_id VARCHAR(100) NOT NULL,
            position SMALLINT UNSIGNED NOT NULL,
            move_name VARCHAR(160) NOT NULL,
            CONSTRAINT fk_combo_steps_combo
                FOREIGN KEY (combo_id) REFERENCES combos(id)
                ON UPDATE CASCADE
                ON DELETE CASCADE,
            UNIQUE KEY uq_combo_step_position (combo_id, position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS combo_tags (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            combo_id VARCHAR(100) NOT NULL,
            tag VARCHAR(80) NOT NULL,
            CONSTRAINT fk_combo_tags_combo
                FOREIGN KEY (combo_id) REFERENCES combos(id)
                ON UPDATE CASCADE
                ON DELETE CASCADE,
            UNIQUE KEY uq_combo_tag (combo_id, tag)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
