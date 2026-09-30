<?php $page_script = 'replay'; ?>
<div class="glass panel mb">
  <div class="flex-between">
    <div><h3 style="margin:0">Attack Simulation Replay</h3>
      <div class="note">Sélectionnez un scénario pédagogique et lancez la simulation pas à pas.</div></div>
    <div class="flex">
      <select class="inp" id="scenarioSel">
        <option value="rogue_cell">Scénario 1 — Rogue Cell</option>
        <option value="downgrade">Scénario 2 — Downgrade 4G → 2G</option>
        <option value="identity_exposure">Scénario 3 — Identity Exposure</option>
        <option value="location_tracking">Scénario 4 — Location Tracking</option>
        <option value="cell_spoofing">Scénario 5 — Cell Spoofing</option>
      </select>
      <button class="btn btn-primary" id="btnStart">▶ START SIMULATION</button>
    </div>
  </div>
</div>

<div class="grid grid-2">
  <div class="glass panel">
    <h3>Déroulement</h3>
    <ul class="timeline" id="timeline"><li class="note">En attente du lancement…</li></ul>
  </div>
  <div class="glass panel">
    <div class="flex-between mb"><h3 style="margin:0">Résultat de détection</h3><span id="riskBadge"></span></div>
    <div id="analysis" class="note">Aucune analyse pour le moment.</div>
    <div class="mt">
      <h3>Réponse défensive</h3>
      <div class="flex" style="flex-wrap:wrap">
        <button class="btn" data-act="block_cell" disabled>Bloquer la cellule</button>
        <button class="btn" data-act="notify_soc" disabled>Notifier le SOC</button>
        <button class="btn" data-act="open_incident" disabled>Ouvrir incident</button>
        <button class="btn" data-act="investigate" disabled>Investiguer</button>
        <button class="btn btn-danger" data-act="close" disabled>Clôturer</button>
      </div>
    </div>
  </div>
</div>
