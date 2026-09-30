<?php
use function App\Core\e;
$active = $active ?? '';
$appcfg = $appcfg ?? ['name' => 'LUNATIC', 'tagline' => ''];
$csrf = $csrf ?? '';
$auth = $auth ?? null;
$nav = [
    'dashboard' => ['Dashboard', '▤', '/dashboard'],
    'inventory' => ['Inventaire', '🗄', '/inventory'],
    'map'       => ['Carte', '🗺', '/map'],
    'graph'     => ['Graph Analysis', '⌗', '/graph'],
    'replay'    => ['Attack Replay', '▶', '/replay'],
    'soc'       => ['SOC Integration', '⚑', '/soc'],
    'training'  => ['Training', '🎓', '/training'],
    'audit'     => ['Audit', '📜', '/audit'],
];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Lab') ?> — <?= e($appcfg['name']) ?></title>
<meta name="csrf-token" content="<?= e($csrf) ?>">
<link rel="stylesheet" href="/assets/vendor/bootstrap/bootstrap.min.css">
<link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-logo">L</div>
      <div><div class="brand-name">LUNATIC<br>SECURITY LAB</div></div>
    </div>
    <div class="brand-tag"><?= e($appcfg['tagline']) ?></div>
    <nav>
      <?php foreach ($nav as $key => [$label, $icon, $href]): ?>
        <a class="nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= e($href) ?>">
          <span class="ic"><?= $icon ?></span><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div style="margin-top:24px;border-top:1px solid var(--panel-border);padding-top:16px;">
      <div class="note mb">Connecté : <strong><?= e($auth['username'] ?? '—') ?></strong><br>
        <span class="chip"><?= e($auth['role'] ?? '') ?></span></div>
      <form method="post" action="/logout">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <button class="btn btn-ghost" style="width:100%">Déconnexion</button>
      </form>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div>
        <h1 class="page-title"><?= e($title ?? '') ?></h1>
        <div class="page-sub"><?= e($appcfg['tagline']) ?></div>
      </div>
      <span class="badge-sim">● SIMULATION — DONNÉES FICTIVES</span>
    </div>
    <?= $content ?? '' ?>
  </main>
</div>

<script src="/assets/vendor/chartjs/chart.umd.min.js"></script>
<script src="/assets/vendor/leaflet/leaflet.js"></script>
<script src="/assets/vendor/cytoscape/cytoscape.min.js"></script>
<script src="/assets/js/app.js"></script>
<?php if (!empty($page_script)): ?>
<script src="/assets/js/<?= e($page_script) ?>.js"></script>
<?php endif; ?>
</body>
</html>
