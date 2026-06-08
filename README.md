# SF6 Combo Selector

ストリートファイター6のキャラクター、始動技、状況、使用ゲージ条件からコンボ候補を選出するReact + PHPのサイトです。

## 使い方

```powershell
npm install
php database/migrate.php
npm run dev
```

PHP APIは `api/combos.php` です。XAMPPのApacheから開く場合は、DBからコンボデータを読み込みます。DBに接続できない時だけ `data/combos.json` を予備として読み込みます。

## DB設定

初期設定では次のDBを使います。

```text
DB名: sf6_combo_selector
ユーザー: root
パスワード: なし
```

変更したい場合は環境変数 `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` を設定してください。

## マイグレーション

```powershell
php database/migrate.php
```

作成される主なテーブル:

- `characters`
- `combos`
- `combo_steps`
- `combo_tags`
- `migrations`

## コンボ追加

`data/combos.json` の `combos` に次の形式で追加します。始動技と状況の選択肢は、選択中キャラに登録されたコンボから自動で作られます。

```json
{
  "id": "ryu-example",
  "character": "ryu",
  "starter": "屈中P",
  "situation": "通常ヒット",
  "route": ["屈中P", "中足刀", "強昇龍拳"],
  "damageClassic": 2460,
  "damageModern": 2250,
  "drive": 0,
  "superArt": 0,
  "difficulty": 2,
  "tags": ["中央", "低コスト"],
  "notes": "実戦で使う理由や注意点"
}
```

全キャラの枠は入れてあります。実データはこのJSONに増やすほど検索候補が増えます。`damageModern` が未入力の場合は暫定でクラシックダメージの90%として表示します。
