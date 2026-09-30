# LUNATIC MOBILE SECURITY LAB

**Mobile Network Security Simulation Platform**

Plateforme web pédagogique pour la formation en sécurité des réseaux mobiles
(SOC, Telecom Security). Elle enseigne les mécanismes d'identification mobile
(IMSI/IMEI/TMSI), les risques des réseaux 2G/3G/4G, le fonctionnement conceptuel
d'un IMSI catcher et surtout **les techniques de détection et de réponse**.

> ⚠️ **100 % SIMULATION.** Cette plateforme ne se connecte à **aucun réseau
> cellulaire réel**, n'utilise **aucun SDR / matériel radio**, ne capture
> **aucun IMSI/IMEI/SMS/appel réel** et ne suit **aucun téléphone réel**.
> Tous les identifiants sont **fictifs** (préfixés `FAKE-`, plage MCC `9xx` de
> test) et marqués `is_simulated = 1`. Chaque alerte SOC porte le champ
> obligatoire `"simulation": true`. Aucun identifiant télécom réel n'est jamais
> traité ni enregistré.

## Fonctionnalités

| Module | Description |
|--------|-------------|
| **Dashboard SOC** | Appareils, cellules, anomalies, alertes, graphiques temps réel simulés (Chart.js) |
| **Inventaire réseau** | Consultation filtrable des appareils et cellules (recherche, techno, rogue) |
| **Simulation réseau** | BTS, cellules, opérateurs, téléphones et SIM virtuels |
| **Rogue Base Station** | Création d'une fausse station simulée et attraction d'appareils virtuels |
| **IMSI Exposure Simulator** | Génération d'événements d'exposition d'identifiants fictifs |
| **Detection Engine** | Moteur de règles → score LOW / MEDIUM / HIGH / CRITICAL |
| **Attack Replay** | 5 scénarios rejoués étape par étape |
| **Defensive Response** | Blocage, notification SOC, incident, investigation, clôture |
| **SOC / SIEM** | Export JSON format SIEM (`simulation: true`) |
| **Carte** | Leaflet — coordonnées entièrement artificielles |
| **Graph Analysis** | Cytoscape — PHONE → SIM → CELL → BTS → OPERATOR |
| **Training** | Modules théoriques + quiz notés |
| **Lab Scoring** | 5 objectifs × 20 = 100 points, leaderboard |
| **Audit** | Journalisation sans aucun identifiant réel |

## Stack technique

- **Frontend** : HTML5, CSS3, JS, Bootstrap 5, Chart.js, Leaflet.js, Cytoscape.js
- **Backend** : PHP 8.3 (MVC maison), API REST, PDO
- **Base** : MySQL / MariaDB
- **Infra** : Docker Compose, Nginx, PHP-FPM

## Démarrage rapide

```bash
cp .env.example .env
docker compose up -d --build
# Attendre que MariaDB soit "healthy", puis générer les données :
docker compose exec php php database/seed.php
```

Ouvrir http://localhost:8080 — se connecter avec :

| Compte | Mot de passe | Rôle |
|--------|--------------|------|
| `admin` | `admin1234` | Administrateur |
| `analyst` | `analyst1234` | Analyste SOC |
| `student` | `student1234` | Étudiant |

### Démarrage sans Docker (SQLite, zéro infra)

Pour une démo rapide ou un poste hors-ligne, aucune base MySQL n'est requise :

```bash
php database/seed_sqlite.php
DB_CONNECTION=sqlite php -S 127.0.0.1:8080 -t public
# → http://127.0.0.1:8080  (admin / admin1234)
```

Toutes les librairies front (Bootstrap, Chart.js, Leaflet, Cytoscape) sont
**vendorisées** dans `public/assets/vendor/` : la plateforme fonctionne en
environnement **air-gapped** (seules les tuiles de carte restent en ligne).

Détails complets dans [INSTALLATION.md](INSTALLATION.md).

## Documentation

- [INSTALLATION.md](INSTALLATION.md) — installation & exploitation
- [ARCHITECTURE.md](ARCHITECTURE.md) — architecture, modèle de données, API
- [SECURITY.md](SECURITY.md) — RBAC, CSRF, XSS, injection, rate limiting
- [TRAINING.md](TRAINING.md) — parcours pédagogique & scénarios

## Cadre éthique

Cet outil est destiné exclusivement à la **formation défensive** et à la
**sensibilisation**. Il n'implémente aucune capacité d'interception réelle. Son
objectif est d'apprendre à **détecter et répondre** aux attaques sur réseaux
mobiles, jamais à les mener.

## Licence

Usage pédagogique. Voir en-tête des fichiers.
