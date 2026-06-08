<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function fallbackJson(): void
{
    $dataFile = __DIR__ . '/../data/combos.json';

    if (!is_file($dataFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Combo data file was not found.'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $json = file_get_contents($dataFile);

    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Combo data could not be read.'], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo $json;
}

try {
    $config = require __DIR__ . '/../config/database.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $characters = $pdo
        ->query('SELECT id, name FROM characters ORDER BY sort_order, name')
        ->fetchAll();

    $comboRows = $pdo
        ->query(
            'SELECT
                id,
                character_id AS `character`,
                starter,
                situation,
                damage_classic AS damageClassic,
                damage_modern AS damageModern,
                drive,
                super_art AS superArt,
                difficulty,
                is_verified AS isVerified,
                notes
             FROM combos
             ORDER BY character_id, starter, situation, id'
        )
        ->fetchAll();

    $stepRows = $pdo
        ->query('SELECT combo_id, move_name FROM combo_steps ORDER BY combo_id, position')
        ->fetchAll();
    $tagRows = $pdo
        ->query('SELECT combo_id, tag FROM combo_tags ORDER BY combo_id, tag')
        ->fetchAll();

    $routes = [];
    foreach ($stepRows as $row) {
        $routes[$row['combo_id']][] = $row['move_name'];
    }

    $tags = [];
    foreach ($tagRows as $row) {
        $tags[$row['combo_id']][] = $row['tag'];
    }

    $combos = array_map(static function (array $combo) use ($routes, $tags): array {
        $combo['damageClassic'] = (int) $combo['damageClassic'];
        $combo['damageModern'] = $combo['damageModern'] === null ? null : (int) $combo['damageModern'];
        $combo['drive'] = (int) $combo['drive'];
        $combo['superArt'] = (int) $combo['superArt'];
        $combo['difficulty'] = (int) $combo['difficulty'];
        $combo['isVerified'] = (bool) $combo['isVerified'];
        $combo['route'] = $routes[$combo['id']] ?? [];
        $combo['tags'] = $tags[$combo['id']] ?? [];

        return $combo;
    }, $comboRows);

    echo json_encode([
        'characters' => $characters,
        'combos' => $combos,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    fallbackJson();
}
