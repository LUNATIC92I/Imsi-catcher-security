<?php use function App\Core\e; ?>
<div style="min-height:70vh;display:grid;place-items:center;text-align:center">
  <div class="glass panel" style="max-width:420px">
    <div style="font-size:52px;font-weight:800;color:var(--electric)"><?= (int)($code ?? 500) ?></div>
    <p class="note"><?= e($message ?? 'Erreur') ?></p>
    <a class="btn btn-primary" href="/dashboard">Retour au dashboard</a>
  </div>
</div>
