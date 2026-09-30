/* LUNATIC — Cytoscape graph */
const typeColor = {
  phone: '#34d399', sim: '#22d3ee', cell: '#3d8bff',
  rogue: '#f43f5e', bts: '#a78bfa', operator: '#fbbf24'
};

let cy = cytoscape({
  container: document.getElementById('cy'),
  style: [
    { selector: 'node', style: {
      'background-color': ele => typeColor[ele.data('type')] || '#8ea3c4',
      'label': 'data(label)', 'color': '#e7eefb', 'font-size': '9px',
      'text-valign': 'bottom', 'text-margin-y': 4, 'width': 22, 'height': 22,
      'border-width': 2, 'border-color': '#04060b'
    }},
    { selector: 'node[type="rogue"]', style: { 'width': 30, 'height': 30, 'border-color': '#f43f5e' } },
    { selector: 'node[type="operator"]', style: { 'width': 34, 'height': 34 } },
    { selector: 'edge', style: {
      'width': 1.5, 'line-color': 'rgba(80,130,200,.35)',
      'target-arrow-color': 'rgba(80,130,200,.35)', 'target-arrow-shape': 'triangle',
      'curve-style': 'bezier'
    }},
    { selector: '.faded', style: { 'opacity': 0.12 } },
  ],
});

let currentElements = { nodes: [], edges: [] };

async function loadGraph() {
  const d = await Api.get('/api/v1/graph?limit=40');
  currentElements = d.elements;
  render('cose', 'all');
}

function render(layout, filter) {
  cy.elements().remove();
  cy.add(currentElements.nodes);
  cy.add(currentElements.edges);
  cy.elements().removeClass('faded');

  if (filter === 'rogue') {
    const rogueNodes = cy.nodes('[type="rogue"]');
    const keep = cy.collection();
    rogueNodes.forEach(n => {
      keep.merge(n);
      keep.merge(n.predecessors());
      keep.merge(n.successors());
    });
    cy.elements().difference(keep).addClass('faded');
  }

  cy.layout({ name: layout, animate: true, padding: 30,
    fit: true, nodeDimensionsIncludeLabels: true }).run();
}

document.getElementById('layoutSel').addEventListener('change', e =>
  render(e.target.value, document.getElementById('filterSel').value));
document.getElementById('filterSel').addEventListener('change', e =>
  render(document.getElementById('layoutSel').value, e.target.value));
document.getElementById('btnFit').addEventListener('click', () => cy.fit(null, 40));
document.getElementById('btnReload').addEventListener('click', loadGraph);

cy.on('tap', 'node', evt => {
  const n = evt.target;
  toast(`${n.data('type').toUpperCase()} : ${n.data('label')}`, 'info');
});

loadGraph();
