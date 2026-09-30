/* LUNATIC — network inventory browser (filterable devices & cells) */
let currentTab = 'devices';
let cache = { devices: null, cells: null };

const COLUMNS = {
  devices: [
    ['code', 'Code'], ['model', 'Modèle'], ['imsi_fake', 'IMSI (fictif)'],
    ['imei_fake', 'IMEI (fictif)'], ['cell_code', 'Cellule'], ['technology', 'Tech'],
    ['signal_dbm', 'Signal'], ['connection_state', 'État'],
  ],
  cells: [
    ['code', 'Code'], ['operator_name', 'Opérateur'], ['mcc', 'MCC'], ['mnc', 'MNC'],
    ['technology', 'Tech'], ['power_dbm', 'Puissance'], ['cell_id_num', 'Cell ID'],
    ['lac', 'LAC'], ['is_rogue', 'Rogue'],
  ],
};

async function ensureData(tab) {
  if (cache[tab]) return cache[tab];
  const url = tab === 'devices' ? '/api/v1/devices' : '/api/v1/cells';
  const d = await Api.get(url);
  cache[tab] = d.data || [];
  return cache[tab];
}

function fmtCell(tab, row, key) {
  let v = row[key];
  if (key === 'is_rogue') {
    return v == 1
      ? '<span class="pill risk-HIGH">ROGUE</span>'
      : '<span class="pill risk-LOW">OK</span>';
  }
  if (key === 'signal_dbm' || key === 'power_dbm') return `<span class="mono">${esc(v ?? '—')} dBm</span>`;
  if (key === 'technology') return `<span class="chip">${esc(v ?? '—')}</span>`;
  if (v === null || v === undefined || v === '') return '—';
  return esc(v);
}

function passesFilters(tab, row) {
  const q = document.getElementById('search').value.trim().toLowerCase();
  const tech = document.getElementById('techFilter').value;
  const rogueOnly = document.getElementById('rogueOnly').checked;

  if (tech && String(row.technology) !== tech) return false;
  if (tab === 'cells' && rogueOnly && row.is_rogue != 1) return false;
  if (q) {
    const hay = COLUMNS[tab].map(([k]) => String(row[k] ?? '')).join(' ').toLowerCase();
    if (!hay.includes(q)) return false;
  }
  return true;
}

async function render() {
  const tab = currentTab;
  const rows = await ensureData(tab);
  const cols = COLUMNS[tab];

  document.getElementById('thead').innerHTML =
    '<tr>' + cols.map(([, label]) => `<th>${esc(label)}</th>`).join('') + '</tr>';

  const filtered = rows.filter(r => passesFilters(tab, r));
  document.getElementById('count').textContent = `${filtered.length} / ${rows.length}`;

  const tbody = document.getElementById('tbody');
  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="${cols.length}" class="note">Aucun résultat. Lancez le seed si la base est vide.</td></tr>`;
    return;
  }
  tbody.innerHTML = filtered.slice(0, 500).map(row => {
    const cls = (tab === 'cells' && row.is_rogue == 1) ? ' style="background:rgba(244,63,94,.06)"' : '';
    return `<tr${cls}>` + cols.map(([k]) => `<td>${fmtCell(tab, row, k)}</td>`).join('') + '</tr>';
  }).join('');
}

// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentTab = btn.dataset.tab;
    // rogue filter only meaningful for cells
    document.getElementById('rogueOnly').closest('label').style.display =
      currentTab === 'cells' ? '' : 'none';
    render();
  });
});

['search', 'techFilter', 'rogueOnly'].forEach(id =>
  document.getElementById(id).addEventListener('input', render));

document.getElementById('rogueOnly').closest('label').style.display = 'none';
render();
