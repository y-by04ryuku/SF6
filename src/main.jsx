import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import {
  Activity,
  BatteryCharging,
  ChevronDown,
  Gamepad2,
  Gauge,
  Keyboard,
  ListFilter,
  Search,
  ShieldAlert,
  ShieldCheck,
  Sparkles,
  Swords,
  Trophy,
  Zap
} from 'lucide-react';
import './styles.css';

const defaultData = {
  characters: [],
  combos: []
};

const controlLabels = {
  classic: 'クラシック',
  modern: 'モダン'
};

function getComboDamage(combo, controlStyle) {
  if (controlStyle === 'modern') {
    return combo.damageModern ?? Math.round(combo.damageClassic * 0.9);
  }

  return combo.damageClassic;
}

function getDamageNote(combo, controlStyle) {
  if (controlStyle !== 'modern') return 'クラシック基準';
  return combo.damageModern ? 'モダン実測値' : 'モダン暫定換算';
}

function scoreCombo(combo, controlStyle) {
  const gaugePenalty = combo.drive * 230 + combo.superArt * 360;
  const difficultyPenalty = combo.difficulty * 90;
  const verifiedBonus = combo.isVerified ? 180 : -900;
  return getComboDamage(combo, controlStyle) - gaugePenalty - difficultyPenalty + verifiedBonus;
}

function uniqueValues(values) {
  return [...new Set(values.filter(Boolean))];
}

function labelFor(combo, maxDamageId, bestValueId) {
  if (!combo.isVerified) return '未検証';
  if (combo.id === maxDamageId) return '最大ダメージ';
  if (combo.id === bestValueId) return 'バランス良';
  if (combo.drive === 0 && combo.superArt === 0) return '省ゲージ';
  return '候補';
}

function normalizeCombo(combo) {
  return {
    ...combo,
    damageClassic: combo.damageClassic ?? combo.damage ?? 0,
    damageModern: combo.damageModern ?? null,
    isVerified: combo.isVerified !== false
  };
}

function App() {
  const [data, setData] = useState(defaultData);
  const [character, setCharacter] = useState('ryu');
  const [starter, setStarter] = useState('');
  const [situation, setSituation] = useState('');
  const [controlStyle, setControlStyle] = useState('classic');
  const [maxDrive, setMaxDrive] = useState(6);
  const [maxSuper, setMaxSuper] = useState(3);
  const [mode, setMode] = useState('balanced');
  const [query, setQuery] = useState('');
  const [showUnverified, setShowUnverified] = useState(false);

  useEffect(() => {
    fetch('/api/combos.php')
      .then((response) => response.ok ? response.json() : fetch('/data/combos.json').then((jsonResponse) => jsonResponse.json()))
      .then((apiData) => {
        const combos = (apiData.combos || []).map(normalizeCombo);
        setData({ ...defaultData, ...apiData, combos });
      })
      .catch(() => {
        fetch('/data/combos.json')
          .then((response) => response.json())
          .then((apiData) => {
            const combos = (apiData.combos || []).map(normalizeCombo);
            setData({ ...defaultData, ...apiData, combos });
          })
          .catch(() => setData(defaultData));
      });
  }, []);

  const visibleCombos = useMemo(
    () => data.combos.filter((combo) => showUnverified || combo.isVerified),
    [data.combos, showUnverified]
  );

  const characterCombos = useMemo(
    () => visibleCombos.filter((combo) => combo.character === character),
    [character, visibleCombos]
  );

  const starters = useMemo(
    () => uniqueValues(characterCombos.map((combo) => combo.starter)),
    [characterCombos]
  );

  const situations = useMemo(
    () => uniqueValues(characterCombos.filter((combo) => combo.starter === starter).map((combo) => combo.situation)),
    [characterCombos, starter]
  );

  useEffect(() => {
    if (starters.length === 0) {
      setStarter('');
      setSituation('');
      return;
    }

    if (!starters.includes(starter)) {
      setStarter(starters[0]);
    }
  }, [starter, starters]);

  useEffect(() => {
    if (situations.length === 0) {
      setSituation('');
      return;
    }

    if (!situations.includes(situation)) {
      setSituation(situations[0]);
    }
  }, [situation, situations]);

  const filtered = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();

    return characterCombos
      .filter((combo) => combo.starter === starter)
      .filter((combo) => combo.situation === situation)
      .filter((combo) => combo.drive <= maxDrive && combo.superArt <= maxSuper)
      .filter((combo) => {
        if (!normalizedQuery) return true;
        return [...combo.route, ...(combo.tags || []), combo.notes || ''].join(' ').toLowerCase().includes(normalizedQuery);
      })
      .sort((a, b) => {
        if (mode === 'damage') return getComboDamage(b, controlStyle) - getComboDamage(a, controlStyle);
        if (mode === 'cheap') {
          return (a.drive + a.superArt * 2) - (b.drive + b.superArt * 2)
            || getComboDamage(b, controlStyle) - getComboDamage(a, controlStyle);
        }
        return scoreCombo(b, controlStyle) - scoreCombo(a, controlStyle);
      });
  }, [characterCombos, starter, situation, maxDrive, maxSuper, mode, query, controlStyle]);

  const maxDamage = filtered.reduce(
    (best, combo) => !best || getComboDamage(combo, controlStyle) > getComboDamage(best, controlStyle) ? combo : best,
    null
  );
  const bestValue = filtered.reduce(
    (best, combo) => !best || scoreCombo(combo, controlStyle) > scoreCombo(best, controlStyle) ? combo : best,
    null
  );
  const selectedCharacter = data.characters.find((item) => item.id === character);
  const hasCharacterData = characterCombos.length > 0;
  const unverifiedCount = data.combos.filter((combo) => combo.character === character && !combo.isVerified).length;

  return (
    <main className="app-shell">
      <section className="hero">
        <div className="hero-copy">
          <p className="eyebrow">Street Fighter 6 Combo Lab</p>
          <h1>始動から、信頼できるコンボ候補へ。</h1>
          <p>
            標準では検証済みルートだけを表示します。未検証データは分離して、実測後に差し替えられるようにしています。
          </p>
        </div>
        <div className="hero-panel">
          <span><ShieldCheck size={18} /> 検証済み優先</span>
          <strong>{data.characters.length || '-'} fighters</strong>
        </div>
      </section>

      <section className="workspace">
        <aside className="control-panel">
          <div className="panel-title">
            <ListFilter size={19} />
            条件入力
          </div>

          <label>
            キャラクター
            <span className="select-wrap">
              <select value={character} onChange={(event) => setCharacter(event.target.value)}>
                {data.characters.map((item) => (
                  <option key={item.id} value={item.id}>{item.name}</option>
                ))}
              </select>
              <ChevronDown size={16} />
            </span>
          </label>

          <div className="style-toggle" aria-label="操作タイプ">
            <button className={controlStyle === 'classic' ? 'active' : ''} onClick={() => setControlStyle('classic')}>
              <Keyboard size={17} />
              クラシック
            </button>
            <button className={controlStyle === 'modern' ? 'active' : ''} onClick={() => setControlStyle('modern')}>
              <Gamepad2 size={17} />
              モダン
            </button>
          </div>

          <label className="check-row">
            <input type="checkbox" checked={showUnverified} onChange={(event) => setShowUnverified(event.target.checked)} />
            <span>
              未検証も表示
              <small>{selectedCharacter?.name || ''}の未検証 {unverifiedCount}件</small>
            </span>
          </label>

          <label>
            始動技
            <span className="select-wrap">
              <select value={starter} onChange={(event) => setStarter(event.target.value)} disabled={!hasCharacterData}>
                {starters.length === 0 ? (
                  <option value="">データ未登録</option>
                ) : starters.map((item) => (
                  <option key={item} value={item}>{item}</option>
                ))}
              </select>
              <ChevronDown size={16} />
            </span>
          </label>

          <label>
            状況
            <span className="select-wrap">
              <select value={situation} onChange={(event) => setSituation(event.target.value)} disabled={!hasCharacterData}>
                {situations.length === 0 ? (
                  <option value="">データ未登録</option>
                ) : situations.map((item) => (
                  <option key={item} value={item}>{item}</option>
                ))}
              </select>
              <ChevronDown size={16} />
            </span>
          </label>

          <label>
            ルート内検索
            <span className="input-icon">
              <Search size={16} />
              <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="例: OD / 画面端" />
            </span>
          </label>

          <div className="range-row">
            <label>
              ドライブ
              <input type="range" min="0" max="6" value={maxDrive} onChange={(event) => setMaxDrive(Number(event.target.value))} />
            </label>
            <strong>{maxDrive}</strong>
          </div>

          <div className="range-row">
            <label>
              SA
              <input type="range" min="0" max="3" value={maxSuper} onChange={(event) => setMaxSuper(Number(event.target.value))} />
            </label>
            <strong>{maxSuper}</strong>
          </div>

          <div className="mode-grid" aria-label="並び替え">
            <button className={mode === 'balanced' ? 'active' : ''} onClick={() => setMode('balanced')}><Sparkles size={16} />バランス</button>
            <button className={mode === 'damage' ? 'active' : ''} onClick={() => setMode('damage')}><Trophy size={16} />火力</button>
            <button className={mode === 'cheap' ? 'active' : ''} onClick={() => setMode('cheap')}><BatteryCharging size={16} />省ゲージ</button>
          </div>
        </aside>

        <section className="results">
          <div className="summary">
            <div>
              <p className="eyebrow">現在の条件</p>
              <h2>{selectedCharacter?.name} / {controlLabels[controlStyle]} / {starter || '未登録'} / {situation || '未登録'}</h2>
            </div>
            <div className="summary-metrics">
              <span><Swords size={17} />{filtered.length}件</span>
              <span><Zap size={17} />最大 {maxDamage ? getComboDamage(maxDamage, controlStyle) : '-'} </span>
              <span><Gauge size={17} />推奨 {bestValue ? `${bestValue.drive}D / SA${bestValue.superArt}` : '-'}</span>
            </div>
          </div>

          {filtered.length === 0 ? (
            <div className="empty-state">
              <Activity size={34} />
              <h3>この条件の検証済みコンボはありません</h3>
              <p>未検証ルートを確認する場合は左の「未検証も表示」をオンにしてください。実測済みコンボを追加すると標準表示に入ります。</p>
            </div>
          ) : (
            <div className="combo-list">
              {filtered.map((combo) => {
                const selectedDamage = getComboDamage(combo, controlStyle);
                const classicDamage = getComboDamage(combo, 'classic');
                const modernDamage = getComboDamage(combo, 'modern');

                return (
                  <article className={`combo-card ${combo.isVerified ? '' : 'unverified'}`} key={combo.id}>
                    <div className="combo-card-head">
                      <span className={`badge ${!combo.isVerified ? 'warn' : combo.id === maxDamage?.id ? 'hot' : combo.id === bestValue?.id ? 'good' : ''}`}>
                        {combo.isVerified ? <ShieldCheck size={15} /> : <ShieldAlert size={15} />}
                        {labelFor(combo, maxDamage?.id, bestValue?.id)}
                      </span>
                      <span className="difficulty">難度 {combo.difficulty}/5</span>
                    </div>
                    <div className="route">
                      {combo.route.map((move, index) => (
                        <React.Fragment key={`${combo.id}-${move}-${index}`}>
                          <span>{move}</span>
                          {index < combo.route.length - 1 && <b>→</b>}
                        </React.Fragment>
                      ))}
                    </div>
                    <div className="damage-board">
                      <div className="damage-main">
                        <small>{controlLabels[controlStyle]}選択中</small>
                        <strong>{selectedDamage}</strong>
                        <span>dmg</span>
                      </div>
                      <div className="damage-sub">
                        <span>クラシック {classicDamage}</span>
                        <span>モダン {modernDamage}</span>
                        <em>{getDamageNote(combo, controlStyle)}</em>
                      </div>
                    </div>
                    <div className="metrics">
                      <span><Zap size={16} />{combo.drive} Drive</span>
                      <span><Gauge size={16} />SA {combo.superArt}</span>
                      <span><Sparkles size={16} />評価 {scoreCombo(combo, controlStyle)}</span>
                    </div>
                    <p>{combo.notes}</p>
                    <div className="tags">
                      {combo.tags?.map((tag) => <span key={tag}>{tag}</span>)}
                    </div>
                  </article>
                );
              })}
            </div>
          )}
        </section>
      </section>
    </main>
  );
}

createRoot(document.getElementById('root')).render(<App />);
