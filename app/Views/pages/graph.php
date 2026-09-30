<?php $page_script = 'graph'; ?>
<div class="glass panel mb">
  <div class="flex-between">
    <div><h3 style="margin:0">Graph Analysis</h3>
      <div class="note">Relations PHONE → SIM → CELL → BTS → OPERATOR (données virtuelles).</div></div>
    <div class="flex">
      <select class="inp" id="layoutSel">
        <option value="cose">Layout: force</option>
        <option value="concentric">Layout: concentrique</option>
        <option value="breadthfirst">Layout: hiérarchique</option>
        <option value="grid">Layout: grille</option>
      </select>
      <select class="inp" id="filterSel">
        <option value="all">Tout afficher</option>
        <option value="rogue">Chemins avec rogue</option>
      </select>
      <button class="btn" id="btnFit">Recentrer</button>
      <button class="btn" id="btnReload">Recharger</button>
    </div>
  </div>
</div>
<div class="glass panel"><div id="cy"></div></div>
<div class="glass panel mt">
  <div class="flex" style="flex-wrap:wrap;gap:16px">
    <span class="chip" style="color:#34d399">● Phone</span>
    <span class="chip" style="color:#22d3ee">● SIM</span>
    <span class="chip" style="color:#3d8bff">● Cell</span>
    <span class="chip" style="color:#f43f5e">● Rogue cell</span>
    <span class="chip" style="color:#a78bfa">● BTS</span>
    <span class="chip" style="color:#fbbf24">● Operator</span>
  </div>
</div>
