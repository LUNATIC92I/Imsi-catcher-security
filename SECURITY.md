# Sécurité — LUNATIC MOBILE SECURITY LAB

## Principe fondamental : simulation uniquement

La plateforme **ne peut pas** interagir avec un réseau réel : aucun code SDR,
aucune interface radio, aucun traitement d'identifiant télécom réel. Tous les
identifiants sont fictifs (`FAKE-*`, MCC `9xx`) et marqués `is_simulated = 1`.
Chaque alerte exportée porte `"simulation": true`. Le journal d'audit
**n'enregistre jamais** d'identifiant réel.

## Contrôles applicatifs

### RBAC (contrôle d'accès basé sur les rôles)
- Rôles : `admin` (`*`), `analyst` (`simulation.run`, `soc.push`,
  `soc.respond`, `audit.view`), `student` (`simulation.run`).
- Permissions stockées en JSON sur le rôle ; vérifiées via
  `Auth::can()` et `Controller::requirePermission()`.

### CSRF
- Jeton par session (`Csrf::token()`), exposé en `<meta name="csrf-token">`.
- Toutes les requêtes non-GET passent par `CsrfMiddleware` (en-tête
  `X-CSRF-Token` ou champ `csrf_token`). Comparaison `hash_equals`.

### Protection XSS
- Échappement systématique en sortie via `View::e()` / `esc()`
  (`htmlspecialchars`, `ENT_QUOTES`).
- **Content-Security-Policy** stricte (voir `config/config.php`) restreignant
  scripts/styles/connexions aux origines attendues + CDN whitelisté.
- En-têtes `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`.

### Injection SQL
- **100 % requêtes préparées** via PDO (`Database::run/all/first/insert`),
  `PDO::ATTR_EMULATE_PREPARES = false`. Aucune concaténation de valeurs
  utilisateur dans le SQL.

### Rate limiting
- Fenêtre glissante en base (`rate_limits`). API : 120 req/60 s par IP.
  Connexion : 10 tentatives/300 s par IP. Réponse `429` au dépassement.

### Sessions sécurisées
- Cookie `HttpOnly`, `SameSite=Lax`, `Secure` si HTTPS,
  `session.use_strict_mode=1`. Régénération de l'ID à la connexion
  (`session_regenerate_id(true)`) contre la fixation de session.

### Hachage des mots de passe
- **Argon2id** (`password_hash` / `password_verify`), re-hachage automatique
  si les paramètres changent.

### Journalisation d'audit
- Utilisateur, action, scénario, IP, résultat, métadonnées, horodatage —
  jamais d'identifiant télécom réel. Champ `simulation = 1`.

## Durcissement en production

- Mettre `APP_DEBUG=false` (masque les traces d'erreur).
- Changer tous les mots de passe par défaut (`.env`) **et** les comptes de démo
  créés par le seed.
- Servir en HTTPS (active le flag `Secure` des cookies).
- Restreindre l'accès réseau au port `3307` (MariaDB) hors développement.
- `expose_php=0`, `display_errors=Off` (déjà appliqués dans l'image PHP).

## Signalement

Cet environnement est pédagogique et isolé. Pour toute question de sécurité liée
au code, ouvrir une issue interne — ne jamais y déposer de données réelles.
