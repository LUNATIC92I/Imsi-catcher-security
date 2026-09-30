# Installation — LUNATIC MOBILE SECURITY LAB

## Prérequis

- Docker + Docker Compose **ou** PHP 8.3+ / MySQL 8+ (MariaDB 11+) / Nginx
- 512 Mo RAM libres, port `8080` disponible

## Option A — Docker (recommandé)

```bash
git clone <repo> lunatic-lab && cd lunatic-lab
cp .env.example .env                # ajuster les mots de passe si besoin
docker compose up -d --build
```

Services démarrés :

| Service | Image | Port hôte |
|---------|-------|-----------|
| `nginx` | nginx:1.27-alpine | 8080 → 80 |
| `php`   | php:8.3-fpm-alpine (custom) | interne 9000 |
| `mysql` | mariadb:11.4 | 3307 → 3306 |

Le schéma (`database/schema.sql`) est appliqué automatiquement au premier
démarrage de MariaDB. Ensuite, générer le jeu de données :

```bash
docker compose exec php php database/seed.php
```

Accès : http://localhost:8080

### Commandes utiles

```bash
docker compose logs -f php          # logs applicatifs
docker compose exec php php database/seed.php   # régénérer les données
docker compose down                 # arrêter
docker compose down -v              # arrêter + supprimer la base
```

## Option B — Installation manuelle

1. Créer la base et l'utilisateur :

   ```sql
   CREATE DATABASE lunatic_lab CHARACTER SET utf8mb4;
   CREATE USER 'lunatic'@'%' IDENTIFIED BY 'lunatic_secret';
   GRANT ALL ON lunatic_lab.* TO 'lunatic'@'%';
   ```

2. Importer le schéma :

   ```bash
   mysql -u lunatic -p lunatic_lab < database/schema.sql
   ```

3. Configurer les variables d'environnement (`DB_HOST`, `DB_USER`, …) puis
   pointer le serveur web sur le dossier `public/` (front controller
   `public/index.php`). Exemple Nginx : voir `docker/nginx/default.conf`.

4. Générer les données :

   ```bash
   DB_HOST=127.0.0.1 DB_USER=lunatic DB_PASS=lunatic_secret php database/seed.php
   ```

## Variables d'environnement

| Variable | Défaut | Rôle |
|----------|--------|------|
| `APP_ENV` | `local` | environnement |
| `APP_DEBUG` | `true` | affichage des erreurs (mettre `false` en prod) |
| `DB_HOST` / `DB_PORT` | `mysql` / `3306` | base |
| `DB_NAME` / `DB_USER` / `DB_PASS` | `lunatic_lab` / `lunatic` / … | connexion |

## Vérification

```bash
# Tests de logique (moteur de détection, export SOC) — sans base
php -r "require 'app/Core/App.php';"        # doit s'exécuter sans erreur
# Lint complet
find app public config database -name '*.php' -exec php -l {} \;
```

Après connexion :
1. Ouvrir **Carte** → cliquer *« + Rogue cell simulée »*.
2. Ouvrir **Attack Replay** → choisir un scénario → *START SIMULATION*.
3. Vérifier l'apparition de l'alerte dans **SOC Integration** (JSON avec
   `"simulation": true`).

## Dépannage

- **`Connection refused` sur l'API** : MariaDB n'est pas encore prêt. Attendre
  le healthcheck (`docker compose ps`) puis relancer le seed.
- **Page blanche / 500** : passer `APP_DEBUG=true`, consulter
  `storage/logs/php-error.log`.
- **419 CSRF** : la session a expiré, recharger la page (le token est dans la
  balise `<meta name="csrf-token">`).
