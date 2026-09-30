<?php $page_script = 'inventory'; ?>
<div class="glass panel mb">
  <div class="flex-between">
    <div><h3 style="margin:0">Inventaire du réseau virtuel</h3>
      <div class="note">Consultation filtrable des appareils et cellules simulés (identifiants fictifs).</div></div>
    <div class="flex">
      <button class="btn tab-btn active" data-tab="devices">Appareils</button>
      <button class="btn tab-btn" data-tab="cells">Cellules</button>
    </div>
  </div>
</div>

<div class="glass panel mb">
  <div class="flex" style="flex-wrap:wrap;gap:12px">
    <input class="inp" id="search" placeholder="🔎 Rechercher (code, IMSI, IMEI, opérateur…)" style="flex:1;min-width:240px">
    <select class="inp" id="techFilter">
      <option value="">Toutes technologies</option>
      <option value="2G">2G</option><option value="3G">3G</option>
      <option value="4G">4G</option><option value="5G">5G</option>
    </select>
    <label class="chip flex" style="gap:6px;cursor:pointer">
      <input type="checkbox" id="rogueOnly"> Cellules rogue uniquement
    </label>
    <span class="chip" id="count">—</span>
  </div>
</div>

<div class="glass panel">
  <div class="scroll-y">
    <table class="lab" id="table">
      <thead id="thead"></thead>
      <tbody id="tbody"><tr><td class="note">Chargement…</td></tr></tbody>
    </table>
  </div>
</div>
