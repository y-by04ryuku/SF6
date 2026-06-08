<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $characters = $pdo
        ->query('SELECT id FROM characters ORDER BY sort_order, id')
        ->fetchAll(PDO::FETCH_COLUMN);

    $finishers = [
        'ryu' => ['強昇龍拳', '真空波動拳'],
        'ken' => ['強昇龍拳', '疾風迅雷脚'],
        'chunli' => ['天昇脚', '蒼天乱華'],
        'luke' => ['強ライジングアッパー', 'ペイルライダー'],
        'jamie' => ['酔疾歩', '月牙叉炮'],
        'manon' => ['ロン・ポワン', 'グラン・フェッテ'],
        'kimberly' => ['武神旋風脚', '武神乱拍子'],
        'marisa' => ['グラディウス', 'アポロニア'],
        'jp' => ['トリグラフ', 'ザロスト・メディア'],
        'juri' => ['強天穿輪', '回旋断界落'],
        'deejay' => ['ジャックナイフマキシマム', 'サタデーナイト'],
        'cammy' => ['キャノンスパイク', 'デルタレッドアサルト'],
        'lily' => ['トマホークバスター', 'レイジングタイフーン'],
        'zangief' => ['ダブルラリアット', 'ボリショイストームバスター'],
        'dhalsim' => ['ヨガブラスト', 'ヨガサンバースト'],
        'honda' => ['スーパー頭突き', '千秋楽'],
        'blanka' => ['ローリングアタック', 'グランドシェイブキャノンボール'],
        'guile' => ['サマーソルトキック', 'ソニックハリケーン'],
        'aki' => ['凶襲突', '死屍累々'],
        'rashid' => ['スピニングミキサー', 'アルタイル'],
        'ed' => ['サイコアッパー', 'サイコチェンバー'],
        'akuma' => ['豪昇龍拳', '禍坏'],
        'bison' => ['ダブルニープレス', 'アンリミテッドサイコクラッシャー'],
        'terry' => ['ライジングタックル', 'バスターウルフ'],
        'mai' => ['飛翔龍炎陣', '不知火流・炎舞仇桜'],
        'elena' => ['スクラッチホイール', 'ブレイブダンス'],
        'sagat' => ['タイガーアッパーカット', 'タイガージェノサイド'],
        'cviper' => ['サンダーナックル', 'バーストタイム'],
        'alex' => ['フラッシュチョップ', 'ハイパーボム'],
        'ingrid' => ['サンアッパー', 'サンデルタ'],
    ];

    $templates = [
        ['mp-cr', '立中P', 'キャンセルラッシュ 通常ヒット', ['立中P', 'キャンセルラッシュ', '立中P', '屈中P'], 3720, 3, 0, 3, ['キャンセルラッシュ', '中央', '火力伸ばし']],
        ['crmp-cr', '屈中P', 'キャンセルラッシュ 通常ヒット', ['屈中P', 'キャンセルラッシュ', '立中P', '屈強P'], 3860, 3, 0, 4, ['キャンセルラッシュ', '中央', '確認重視']],
        ['crmk-cr', '屈中K', 'キャンセルラッシュ 通常ヒット', ['屈中K', 'キャンセルラッシュ', '立中P', '屈中P'], 3420, 3, 0, 4, ['キャンセルラッシュ', '差し返し', '中足始動']],
        ['hp-cr', '立強P', 'キャンセルラッシュ 通常ヒット', ['立強P', 'キャンセルラッシュ', '立中P', '屈強P'], 4320, 3, 0, 4, ['キャンセルラッシュ', '強始動', '高火力']],
        ['pc-hp-cr', '立強P', 'パニッシュカウンター キャンセルラッシュ', ['立強P', 'キャンセルラッシュ', '立強P', '屈強P'], 4860, 3, 0, 5, ['キャンセルラッシュ', 'パニッシュカウンター', '最大候補']],
        ['cr-sa3', '立中P', 'キャンセルラッシュ SA3締め', ['立中P', 'キャンセルラッシュ', '立強P', 'キャンセル必殺技', 'SA3'], 5720, 3, 3, 5, ['キャンセルラッシュ', 'SA3', '倒し切り']],
        ['double-cr', '立中P', 'ダブルキャンセルラッシュ', ['立中P', 'キャンセルラッシュ', '立中P', 'キャンセルラッシュ', '立強P'], 6020, 6, 0, 5, ['キャンセルラッシュ', '2回使用', '最大候補']],
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
        [$finisher, $saName] = $finishers[$character] ?? ['必殺技締め', 'SA3'];

        foreach ($templates as $template) {
            [$slug, $starter, $situation, $routePrefix, $baseDamage, $drive, $superArt, $difficulty, $tags] = $template;
            $id = "{$character}-cancel-rush-{$slug}";
            $route = array_merge($routePrefix, [$superArt === 3 ? $saName : $finisher]);
            $damageClassic = $baseDamage + ($superArt * 220);
            $damageModern = (int) round($damageClassic * 0.9);

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
                'notes' => 'キャンセルラッシュを含む仮登録ルートです。Drive消費はキャンセルラッシュ1回につき3本として登録しています。実戦用の正確なダメージと細部はトレモ実測後に差し替えてください。',
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
