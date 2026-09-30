<div class="glass panel mb">
  <div class="flex-between">
    <div><h3 style="margin:0">Training Mode — Mobile Network Security</h3>
      <div class="note">Théorie, schémas, simulation, quiz. Score max par scénario : 100.</div></div>
    <div class="flex"><span class="chip">Score total : <strong id="totalScore">—</strong>/100</span></div>
  </div>
</div>

<div class="grid grid-3" id="modules"></div>

<div class="glass panel mt">
  <h3>Classement (leaderboard)</h3>
  <table class="lab"><thead><tr><th>#</th><th>Étudiant</th><th>Score</th></tr></thead>
  <tbody id="lb"><tr><td colspan="3" class="note">Chargement…</td></tr></tbody></table>
</div>

<script>
const GLOSSARY = [
  ['IMSI', 'International Mobile Subscriber Identity — identifiant unique de l\'abonné, stocké sur la SIM. Dans ce lab il est toujours fictif.'],
  ['IMEI', 'International Mobile Equipment Identity — identifiant matériel du terminal. Fictif ici.'],
  ['TMSI', 'Temporary Mobile Subscriber Identity — identité temporaire limitant l\'exposition de l\'IMSI.'],
  ['BTS', 'Base Transceiver Station — station de base radio du réseau GSM.'],
  ['BSC', 'Base Station Controller — pilote plusieurs BTS.'],
  ['MSC', 'Mobile Switching Center — commutation des appels/mobilité.'],
  ['HLR', 'Home Location Register — base de données abonnés de référence.'],
  ['VLR', 'Visitor Location Register — abonnés présents dans une zone.'],
  ['LTE', 'Long Term Evolution (4G) — chiffrement mutuel renforcé.'],
  ['GSM', '2G — chiffrement faible/absent, vulnérable au downgrade.'],
  ['2G Downgrade', 'Forcer un terminal à revenir en 2G pour affaiblir la sécurité.'],
  ['Rogue Base Station', 'Fausse station de base (IMSI catcher) attirant les terminaux.'],
  ['SS7', 'Protocole de signalisation historique, sujet à abus de localisation.'],
  ['Diameter', 'Signalisation LTE, successeur de SS7.'],
  ['SIM Authentication', 'Défi-réponse (Ki) entre SIM et réseau. Ici : jamais de vraie clé.'],
];

const QUIZ = {
  IMSI: { q: "L'IMSI identifie :", opts: ["Le terminal", "L'abonné (SIM)", "La cellule"], a: 1 },
  IMEI: { q: "L'IMEI identifie :", opts: ["L'abonné", "Le terminal matériel", "L'opérateur"], a: 1 },
  '2G Downgrade': { q: "Le downgrade 2G est dangereux car :", opts: ["Plus rapide", "Chiffrement faible/absent", "Meilleure batterie"], a: 1 },
  'Rogue Base Station': { q: "Une rogue BTS attire les terminaux via :", opts: ["Une puissance élevée", "Un SMS", "Le Wi-Fi"], a: 0 },
};

function render() {
  const wrap = document.getElementById('modules');
  wrap.innerHTML = GLOSSARY.map(([term, def]) => {
    const quiz = QUIZ[term];
    return `<div class="glass panel">
      <h3>${esc(term)}</h3>
      <div class="note mb">${esc(def)}</div>
      <div class="chip mb">📘 Théorie · 🖼 Schéma · 🧪 Simulation</div>
      ${quiz ? `<div class="mt"><strong style="font-size:13px">Quiz : ${esc(quiz.q)}</strong>
        <div class="mt" data-quiz="${esc(term)}">
          ${quiz.opts.map((o, i) => `<button class="btn btn-ghost" style="display:block;width:100%;text-align:left;margin-bottom:6px" data-i="${i}">${esc(o)}</button>`).join('')}
        </div></div>` : '<div class="note mt">Module théorique.</div>'}
    </div>`;
  }).join('');

  document.querySelectorAll('[data-quiz]').forEach(box => {
    const term = box.dataset.quiz;
    box.querySelectorAll('button').forEach(b => b.addEventListener('click', async () => {
      const correct = QUIZ[term].a === +b.dataset.i;
      b.style.borderColor = correct ? 'var(--green)' : 'var(--red)';
      try {
        await Api.post('/api/v1/training/quiz', { module: term, score: correct ? 1 : 0, total: 1 });
        toast(correct ? 'Bonne réponse ! +1' : 'Réponse incorrecte', correct ? 'ok' : 'err');
        if (correct) loadScore();
      } catch (e) { toast(e.message, 'err'); }
    }));
  });
}

async function loadScore() {
  try {
    const s = await Api.get('/api/v1/scoring/me');
    document.getElementById('totalScore').textContent = s.total;
    const lb = await Api.get('/api/v1/scoring/leaderboard');
    document.getElementById('lb').innerHTML = lb.leaderboard.map((r, i) =>
      `<tr><td>${i + 1}</td><td>${esc(r.username)}</td><td class="mono">${r.total}</td></tr>`).join('')
      || '<tr><td colspan="3" class="note">—</td></tr>';
  } catch (e) {}
}

render();
loadScore();
</script>
