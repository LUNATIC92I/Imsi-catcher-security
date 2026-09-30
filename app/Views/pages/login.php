<?php use function App\Core\e; ?>
<div class="login-wrap">
  <div class="login-card glass">
    <div class="brand">
      <div class="brand-logo">L</div>
      <div class="brand-name">LUNATIC MOBILE<br>SECURITY LAB</div>
    </div>
    <h1>Connexion au laboratoire</h1>
    <p class="sub">Plateforme de simulation — Mobile Network Security</p>

    <div class="alert-msg" id="err"></div>

    <form id="loginForm">
      <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>">
      <div class="field">
        <label>Nom d'utilisateur</label>
        <input type="text" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label>Mot de passe</label>
        <input type="password" name="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary" style="width:100%" type="submit">Se connecter</button>
    </form>

    <p class="note" style="margin-top:20px">
      Comptes de démo (après <code>seed.php</code>) :<br>
      <code>admin / admin1234</code> · <code>analyst / analyst1234</code> · <code>student / student1234</code>
    </p>
    <p class="note" style="margin-top:10px;color:var(--green)">
      ● Environnement 100% simulé — aucun réseau, SDR ou identifiant réel.
    </p>
  </div>
</div>

<script src="/assets/js/login.js"></script>
