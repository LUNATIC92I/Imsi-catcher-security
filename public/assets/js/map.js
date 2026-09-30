/* LUNATIC — Leaflet map (artificial coordinates only) */
const map = L.map('map', { zoomControl: true }).setView([48.85, 2.35], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 18, attribution: '© OpenStreetMap · Coordonnées de laboratoire fictives'
}).addTo(map);

let layerGroup = L.layerGroup().addTo(map);

function dot(color) {
  return L.divIcon({
    className: '',
    html: `<div style="width:14px;height:14px;border-radius:50%;background:${color};box-shadow:0 0 10px ${color};border:2px solid #04060b"></div>`,
    iconSize: [14, 14]
  });
}

async function loadMap() {
  layerGroup.clearLayers();
  const d = await Api.get('/api/v1/map');
  const bounds = [];

  d.bts.forEach(b => {
    const color = b.is_legitimate ? '#3d8bff' : '#f43f5e';
    L.marker([b.lat, b.lng], { icon: dot(color) })
      .bindPopup(`<b>${esc(b.code)}</b><br>BTS ${b.is_legitimate ? 'légitime' : 'suspecte'} (simulée)`)
      .addTo(layerGroup);
    L.circle([b.lat, b.lng], { radius: b.coverage_m, color: '#22d3ee', weight: 1, fillOpacity: 0.04 }).addTo(layerGroup);
    bounds.push([b.lat, b.lng]);
  });

  d.devices.forEach(v => {
    L.marker([v.lat, v.lng], { icon: dot('#34d399') })
      .bindPopup(`<b>${esc(v.code)}</b><br>Appareil virtuel<br>Signal: ${esc(v.signal_dbm)} dBm<br>État: ${esc(v.connection_state)}`)
      .addTo(layerGroup);
  });

  d.rogues.forEach(r => {
    if (r.lat == null) return;
    L.marker([r.lat, r.lng], { icon: dot('#f43f5e') })
      .bindPopup(`<b>${esc(r.code)}</b><br>⚠ Station suspecte simulée<br>Tech: ${esc(r.technology)}<br>Puissance: ${esc(r.power_dbm)} dBm`)
      .addTo(layerGroup);
    L.circle([r.lat, r.lng], { radius: 800, color: '#f43f5e', weight: 1, fillOpacity: 0.08 }).addTo(layerGroup);
  });

  if (bounds.length) map.fitBounds(bounds, { padding: [40, 40] });
  toast(`${d.bts.length} BTS · ${d.devices.length} appareils · ${d.rogues.length} rogue`, 'info');
}

document.getElementById('btnReload').addEventListener('click', loadMap);
document.getElementById('btnSpawn').addEventListener('click', async () => {
  const btn = document.getElementById('btnSpawn');
  btn.disabled = true;
  try {
    const r = await Api.post('/api/v1/simulation/rogue/spawn');
    toast('Rogue cell simulée créée : ' + r.rogue_cell.code, 'warn');
    // Attract a few virtual devices so detection events are generated.
    const lured = await Api.post('/api/v1/simulation/rogue/lure', { cell_id: r.rogue_cell.id, count: 5 });
    const n = lured.result?.devices_lured ?? 0;
    const crit = (lured.result?.results ?? []).filter(x => x.analysis?.risk_level === 'CRITICAL').length;
    toast(`${n} appareils virtuels attirés · ${crit} détections CRITICAL`, 'err');
    await loadMap();
  } catch (e) { toast(e.message, 'err'); }
  finally { btn.disabled = false; }
});

loadMap();
