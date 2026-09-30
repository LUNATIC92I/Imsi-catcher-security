# Parcours pédagogique — LUNATIC MOBILE SECURITY LAB

Ce module forme à la **détection et à la réponse** face aux menaces sur réseaux
mobiles. Tout se déroule dans un environnement **entièrement simulé**.

## Objectifs d'apprentissage

À l'issue de la formation, l'apprenant sait :
1. expliquer les identifiants mobiles (IMSI, IMEI, TMSI) et leur exposition ;
2. distinguer les technologies 2G/3G/4G/5G et le risque de *downgrade* ;
3. reconnaître le comportement d'une fausse station de base (rogue BTS) ;
4. interpréter un score de détection (LOW → CRITICAL) ;
5. conduire une réponse défensive (containment, incident, clôture).

## Glossaire couvert (Training Mode)

IMSI · IMEI · TMSI · BTS · BSC · MSC · HLR · VLR · LTE · GSM · 2G downgrade ·
rogue base station · SS7 · Diameter · SIM authentication.

Chaque module propose : **théorie**, **schéma**, **simulation**, **quiz**,
**résultat** (points ajoutés au score de l'apprenant).

## Scénarios (Attack Replay)

| # | Scénario | Ce qu'il enseigne |
|---|----------|-------------------|
| 1 | **Rogue Cell** | Une station fictive apparaît près d'une cellule légitime |
| 2 | **Downgrade 4G → 2G** | Bascule forcée vers un réseau 2G non chiffré |
| 3 | **Identity Exposure** | Exposition d'un identifiant mobile (IMSI simulé) |
| 4 | **Location Tracking** | Trajectoire virtuelle générée aléatoirement |
| 5 | **Cell Spoofing** | Cellule aux caractéristiques anormales (Cell ID/MCC) |

Déroulé du replay : téléphone virtuel → cellule légitime → apparition de la
rogue → changement de cellule → exposition → détection → alerte → réponse
défensive.

## Moteur de détection — grille de score

| Règle | Poids | Signal |
|-------|-------|--------|
| Cellule inconnue / rogue | 45 | `is_rogue` / hors base légitime |
| Puissance anormalement élevée | 25 (>-50 dBm) / 10 (>-60) | attire les terminaux |
| Downgrade technologique | 35 (→2G) / 15×niveaux | chiffrement affaibli |
| MCC inattendu | 20 | PLMN mismatch |
| MNC inattendu | 15 | PLMN mismatch |
| Changement de cellule soudain | 15 | mobilité implausible |
| Requête d'identité à l'attach | 20 | comportement IMSI catcher |
| Cell ID incohérent | 15 | spoofing |

Score cumulé → **LOW** (0–29) · **MEDIUM** (30–54) · **HIGH** (55–79) ·
**CRITICAL** (80–100).

## Lab Scoring (100 points)

| Objectif | Points |
|----------|--------|
| Détection d'une rogue cell | +20 |
| Identification du downgrade | +20 |
| Identification de l'exposition | +20 |
| Analyse du graphe | +20 |
| Réponse à l'incident | +20 |

Un **leaderboard** classe les apprenants (module Training).

## Déroulé conseillé d'une séance

1. **Dashboard** — lire l'état du réseau simulé (appareils, cellules, alertes).
2. **Carte** — créer une *rogue cell simulée* et l'observer.
3. **Attack Replay** — rejouer chaque scénario, lire le score de détection.
4. **Graph Analysis** — filtrer les chemins passant par une cellule rogue.
5. **SOC Integration** — générer l'export SIEM (`simulation: true`).
6. **Defensive Response** — bloquer, notifier, ouvrir puis clôturer l'incident.
7. **Training** — répondre aux quiz et consolider la théorie.

## Rappel éthique

La formation vise la **défense**. Aucune capacité d'interception réelle n'est
fournie ni enseignée. Ne jamais introduire de données réelles dans le lab.
