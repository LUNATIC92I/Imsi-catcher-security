# Architecture — LUNATIC MOBILE SECURITY LAB

## 1. Vue d'ensemble

```
Navigateur (Bootstrap 5 / Chart.js / Leaflet / Cytoscape)
        │  HTTP + API REST JSON
        ▼
   Nginx  ──►  PHP-FPM (front controller public/index.php)
                    │
        ┌───────────┼─────────────────────────────┐
        ▼           ▼                             ▼
   Router      Middleware                      Controllers
             (CSRF, RateLimit, ApiAuth)     (Pages + Api\*)
                                                  │
                          ┌───────────────────────┼──────────┐
                          ▼                        ▼          ▼
                       Services                 Models      Views
              (DetectionEngine, ScenarioRunner, (PDO)     (templates)
               RogueCellSimulator, SocExporter,
               ScoringService)
                          │
                          ▼
                    MySQL / MariaDB  (toutes entités is_simulated = 1)
```

MVC maison, sans framework lourd : autoloader PSR-4 (`App\`), routeur regex,
couche PDO en requêtes préparées, moteur de vues avec échappement XSS.

## 2. Modèle de données

Entités principales (toutes fictives, `is_simulated = 1`) :

- **operators** — opérateurs fictifs (MCC `9xx`, MNC).
- **bts** — stations de base virtuelles (coordonnées artificielles).
- **cells** — cellules ; `is_rogue`, `technology`, `power_dbm`, `frequency_mhz`.
- **sims** — SIM virtuelles ; `imsi_fake` (préfixe `FAKE-`), pas de clé Ki.
- **devices** — téléphones virtuels ; `imei_fake`, cellule courante, signal.
- **device_mobility** — historique de mobilité simulé.
- **connection_events** — attach/detach/handover/downgrade/identity_request…
- **handovers** — changements de cellule.
- **anomalies** — sorties du moteur de détection (type, score, risque).
- **alerts** — alertes SOC ; `simulation = 1` obligatoire ; statut.
- **scenarios / scenario_steps** — scénarios pédagogiques et étapes de replay.
- **users / roles** — RBAC.
- **lab_scores / quiz_results** — scoring pédagogique.
- **audit_logs** — journal (jamais d'identifiant réel).
- **rate_limits** — fenêtre glissante de limitation de débit.

Relations : `operators 1─n bts 1─n cells`, `sims 1─1 devices`,
`devices n─1 cells`, `events/anomalies/alerts n─1 devices/cells`.

## 3. Schéma SQL

Voir [`database/schema.sql`](database/schema.sql) — InnoDB, `utf8mb4`, clés
étrangères et index sur les colonnes de filtrage (risque, type, temps, PLMN).

## 4. API REST (`/api/v1`)

Toutes les réponses sont en JSON et incluent `"simulation": true`.
Authentification par session ; écritures protégées par CSRF + RBAC + rate limit.

| Méthode | Endpoint | Rôle requis |
|---------|----------|-------------|
| GET  | `/api/v1/stats/summary` · `/stats/timeseries` | auth |
| GET  | `/api/v1/devices` `/cells` `/bts` `/operators` | auth |
| GET  | `/api/v1/events` `/anomalies` `/alerts` | auth |
| GET  | `/api/v1/map` · `/graph` | auth |
| POST | `/api/v1/simulation/rogue/spawn` · `/rogue/lure` | `simulation.run` |
| POST | `/api/v1/simulation/scenario` | `simulation.run` |
| POST | `/api/v1/detection/analyze` | auth |
| GET  | `/api/v1/soc/export` | auth |
| POST | `/api/v1/soc/push` | `soc.push` |
| POST | `/api/v1/defensive/respond` | `soc.respond` |
| POST | `/api/v1/scoring/award` · GET `/scoring/me` `/scoring/leaderboard` | auth |
| POST | `/api/v1/training/quiz` · GET `/training/results` | auth |
| GET  | `/api/v1/audit` | `audit.view` |

## 5. Structure des dossiers

```
├── app/
│   ├── Core/          # App, Router, Database, Request, Response, View, Auth, Csrf, RateLimiter, Controller
│   ├── Middleware/    # Csrf, RateLimit, ApiAuth
│   ├── Models/        # entités (PDO)
│   ├── Services/      # DetectionEngine, RogueCellSimulator, ScenarioRunner, SocExporter, ScoringService
│   ├── Controllers/   # Pages + Api\*
│   └── Views/         # layouts/ + pages/
├── config/            # config.php, routes.php
├── database/          # schema.sql, seed.php
├── docker/            # nginx, php (Dockerfile)
├── public/            # index.php + assets (css/js)
└── storage/           # logs, cache
```

## 6. Flux de simulation

```
SimulationEngine / ScenarioRunner
   génère un contexte (appareil virtuel, cellule légitime, rogue simulée)
        │
        ▼
DetectionEngine.analyze(cell, ctx)
   règles pondérées → score 0..100 → LOW/MEDIUM/HIGH/CRITICAL
        │
        ▼
Anomaly (persistée) ──► Alert (simulation=1)
        │
        ▼
SocExporter.buildPayload()  →  JSON SIEM { ..., "simulation": true, "lab": true }
        │
        ▼
DefensiveController.respond()  →  block / notify / incident / investigate / close
        │
        ▼
ScoringService.award()  →  points pédagogiques
```

Le moteur n'observe que des **caractéristiques simulées** stockées en base ;
aucune donnée radio réelle n'est jamais lue.
