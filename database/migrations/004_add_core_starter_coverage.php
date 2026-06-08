<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $characters = $pdo
        ->query('SELECT id FROM characters ORDER BY sort_order, id')
        ->fetchAll(PDO::FETCH_COLUMN);

    $finishers = [
        'ryu' => ['強昇龍拳', 760],
        'ken' => ['強昇龍拳', 760],
        'chunli' => ['天昇脚', 740],
        'luke' => ['強ライジングアッパー', 780],
        'jamie' => ['酔疾歩', 690],
        'manon' => ['ロン・ポワン', 720],
        'kimberly' => ['武神旋風脚', 700],
        'marisa' => ['グラディウス', 840],
        'jp' => ['トリグラフ', 720],
        'juri' => ['強天穿輪', 740],
        'deejay' => ['ジャックナイフマキシマム', 770],
        'cammy' => ['キャノンスパイク', 760],
        'lily' => ['トマホークバスター', 760],
        'zangief' => ['ダブルラリアット', 700],
        'dhalsim' => ['ヨガブラスト', 680],
        'honda' => ['スーパー頭突き', 760],
        'blanka' => ['ローリングアタック', 740],
        'guile' => ['サマーソルトキック', 780],
        'aki' => ['凶襲突', 720],
        'rashid' => ['スピニングミキサー', 740],
        'ed' => ['サイコアッパー', 760],
        'akuma' => ['豪昇龍拳', 800],
        'bison' => ['ダブルニープレス', 760],
        'terry' => ['ライジングタックル', 770],
        'mai' => ['飛翔龍炎陣', 750],
        'elena' => ['スクラッチホイール', 730],
        'sagat' => ['タイガーアッパーカット', 790],
        'cviper' => ['サンダーナックル', 780],
        'alex' => ['フラッシュチョップ', 760],
        'ingrid' => ['サンアッパー', 740],
    ];

    $templates = [
        ['lp', '立弱P', '通常ヒット', ['立弱P', '屈弱P'], 1360, 2, ['弱始動', '中央', '仮登録']],
        ['crlp', '屈弱P', '通常ヒット', ['屈弱P', '屈弱P'], 1280, 2, ['弱始動', '中央', '仮登録']],
        ['lk', '立弱K', '通常ヒット', ['立弱K', '屈弱P'], 1320, 2, ['弱始動', '中央', '仮登録']],
        ['crlk', '屈弱K', '通常ヒット', ['屈弱K', '屈弱P'], 1420, 2, ['弱始動', '中央', '仮登録']],
        ['mp', '立中P', '通常ヒット', ['立中P', '屈中P'], 2520, 3, ['中始動', '中央', '仮登録']],
        ['crmp', '屈中P', '通常ヒット', ['屈中P', '立弱P'], 2380, 3, ['中始動', '中央', '仮登録']],
        ['mk', '立中K', '通常ヒット', ['立中K', '屈弱P'], 2240, 3, ['中始動', '中央', '仮登録']],
        ['crmk', '屈中K', '通常ヒット', ['屈中K'], 2180, 3, ['中始動', '差し返し', '仮登録']],
        ['hp', '立強P', '通常ヒット', ['立強P', '屈中P'], 3020, 3, ['強始動', '中央', '仮登録']],
        ['crhp', '屈強P', '通常ヒット', ['屈強P'], 2880, 3, ['強始動', '中央', '仮登録']],
        ['hk', '立強K', '通常ヒット', ['立強K'], 2840, 3, ['強始動', '中央', '仮登録']],
        ['crhk-pc', '屈強K', 'パニッシュカウンター', ['屈強K', '起き攻め'], 2160, 2, ['足払い', 'パニッシュカウンター', '仮登録']],
        ['jump-mk', 'ジャンプ中K', '通常ヒット', ['ジャンプ中K', '立中P'], 3180, 3, ['ジャンプ始動', '中央', '仮登録']],
        ['jump-hp', 'ジャンプ強P', '通常ヒット', ['ジャンプ強P', '立強P'], 3460, 3, ['ジャンプ始動', '中央', '仮登録']],
        ['di-wall', 'ドライブインパクト', '壁やられ', ['ドライブインパクト', '立強P'], 3620, 3, ['DI', '画面端', '仮登録']],
        ['pc-hp', '立強P', 'パニッシュカウンター', ['立強P', '屈強P'], 3560, 4, ['パニッシュカウンター', '高火力', '仮登録']],
        ['od-mid', '屈中P', 'OD使用 通常ヒット', ['屈中P', 'OD必殺技', '立中P'], 3260, 4, ['OD', '中央', '仮登録']],
        ['sa3-finish', '立中P', 'SA3締め', ['立中P', 'キャンセル必殺技', 'SA3'], 4480, 4, ['SA3', '倒し切り', '仮登録']],
    ];

    $comboStatement = $pdo->prepare(
        'INSERT INTO combos (
            id, character_id, starter, situation, damage_classic, damage_modern,
            drive, super_art, difficulty, notes
        ) VALUES (
            :id, :character_id, :starter, :situation, :damage_classic, :damage_modern,
            :drive, :super_art, :difficulty, :notes
        ) ON DUPLICATE KEY UPDATE
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
    $stepStatement = $pdo->prepare('INSERT INTO combo_steps (combo_id, position, move_name) VALUES (?, ?, ?)');
    $tagStatement = $pdo->prepare('INSERT INTO combo_tags (combo_id, tag) VALUES (?, ?)');

    foreach ($characters as $character) {
        [$finisher, $finisherDamage] = $finishers[$character] ?? ['必殺技締め', 720];

        foreach ($templates as $template) {
            [$slug, $starter, $situation, $routePrefix, $baseDamage, $difficulty, $tags] = $template;
            $id = "{$character}-core-{$slug}";
            $drive = str_contains($slug, 'od') ? 2 : (str_contains($slug, 'di') ? 1 : 0);
            $superArt = str_contains($slug, 'sa3') ? 3 : 0;
            $damageClassic = $baseDamage + ($drive * 180) + ($superArt * 360);
            $damageModern = (int) round($damageClassic * 0.9);
            $route = array_merge($routePrefix, [$finisher]);

            $comboStatement->execute([
                'id' => $id,
                'character_id' => $character,
                'starter' => $starter,
                'situation' => $situation,
                'damage_classic' => $damageClassic,
                'damage_modern' => $damageModern,
                'drive' => $drive,
                'super_art' => $superArt,
                'difficulty' => $difficulty,
                'notes' => '主要始動を選べるようにするための仮登録ルートです。実戦用の正確なダメージとルートはトレモ実測後に差し替えてください。',
            ]);

            $deleteSteps->execute([$id]);
            foreach ($route as $position => $moveName) {
                $stepStatement->execute([$id, $position + 1, $moveName]);
            }

            $deleteTags->execute([$id]);
            foreach ($tags as $tag) {
                $tagStatement->execute([$id, $tag]);
            }
        }
    }
};
