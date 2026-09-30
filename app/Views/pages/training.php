<?php $page_script = 'training'; ?>
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
