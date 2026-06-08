<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $dataFile = __DIR__ . '/../../data/combos.json';
    $json = file_get_contents($dataFile);

    if ($json === false) {
        throw new RuntimeException('Could not read data/combos.json');
    }

    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    $characterStatement = $pdo->prepare(
        'INSERT INTO characters (id, name, sort_order)
         VALUES (:id, :name, :sort_order)
         ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)'
    );

    foreach (($data['characters'] ?? []) as $index => $character) {
        $characterStatement->execute([
            'id' => $character['id'],
            'name' => $character['name'],
            'sort_order' => $index + 1,
        ]);
    }

    $comboStatement = $pdo->prepare(
        'INSERT INTO combos (
            id, character_id, starter, situation, damage_classic, damage_modern,
            drive, super_art, difficulty, notes
        ) VALUES (
            :id, :character_id, :starter, :situation, :damage_classic, :damage_modern,
            :drive, :super_art, :difficulty, :notes
        ) ON DUPLICATE KEY UPDATE
            character_id = VALUES(character_id),
            starter = VALUES(starter),
            situation = VALUES(situation),
            damage_classic = VALUES(damage_classic),
            damage_modern = VALUES(damage_modern),
            drive = VALUES(drive),
            super_art = VALUES(super_art),
            difficulty = VALUES(difficulty),
            notes = VALUES(notes)'
    );
    $deleteSteps = $pdo->prepare('DELETE FROM combo_steps WHERE combo_id = ?');
    $deleteTags = $pdo->prepare('DELETE FROM combo_tags WHERE combo_id = ?');
    $stepStatement = $pdo->prepare(
        'INSERT INTO combo_steps (combo_id, position, move_name) VALUES (?, ?, ?)'
    );
    $tagStatement = $pdo->prepare(
        'INSERT INTO combo_tags (combo_id, tag) VALUES (?, ?)'
    );

    foreach (($data['combos'] ?? []) as $combo) {
        $comboStatement->execute([
            'id' => $combo['id'],
            'character_id' => $combo['character'],
            'starter' => $combo['starter'],
            'situation' => $combo['situation'],
            'damage_classic' => $combo['damageClassic'] ?? $combo['damage'] ?? 0,
            'damage_modern' => $combo['damageModern'] ?? null,
            'drive' => $combo['drive'] ?? 0,
            'super_art' => $combo['superArt'] ?? 0,
            'difficulty' => $combo['difficulty'] ?? 1,
            'notes' => $combo['notes'] ?? null,
        ]);

        $deleteSteps->execute([$combo['id']]);
        foreach (($combo['route'] ?? []) as $position => $moveName) {
            $stepStatement->execute([$combo['id'], $position + 1, $moveName]);
        }

        $deleteTags->execute([$combo['id']]);
        foreach (($combo['tags'] ?? []) as $tag) {
            $tagStatement->execute([$combo['id'], $tag]);
        }
    }
};
