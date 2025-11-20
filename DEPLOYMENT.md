# 📦 Guide de Déploiement

## Prérequis

- Docker Engine 25+ (ou compatible)
- Docker Compose v2+
- Make (optionnel, pour les commandes simplifiées)
- Git

## Installation Rapide

### Environnement de Développement

```bash
# Cloner le repository
git clone https://github.com/MarJC5/defi-fullstack.git
cd defi-fullstack

# Démarrer tous les services (avec Make)
make install-dev

# OU sans Make
docker compose --profile dev up -d
```

L'application sera accessible sur https://localhost

### Environnement de Production

```bash
# Démarrer en mode production
make install

# OU sans Make
docker compose --profile prod up -d
```

## Accès à l'Application

Une fois déployée, l'application est accessible sur :

- **Frontend**: https://localhost (HTTPS)
- **Backend API**: https://localhost/api/v1
- **Documentation API**: https://localhost/api/doc (Swagger UI)
- **Base de données**: localhost:5432 (PostgreSQL)

### Credentials par Défaut

- **API User**: `api_user` / `api_password`
- **Database**: `app` / `secret` / `trainrouting`

> ⚠️ **Important**: Changez ces credentials en production via les fichiers `.env`

## Commandes Disponibles

### Installation et Gestion

```bash
# Installation
make install-dev          # Démarre en mode développement
make install              # Démarre en mode production

# Gestion
make start                # Démarre tous les services (prod)
make start-dev            # Démarre tous les services (dev)
make stop                 # Arrête tous les services
make restart              # Redémarre tous les services (prod)
make restart-dev          # Redémarre tous les services (dev)
make clean                # Nettoie complètement l'environnement

# Logs
make logs                 # Affiche les logs de tous les services
make logs-backend         # Logs backend uniquement
make logs-frontend        # Logs frontend uniquement
```

### Tests

```bash
# Tests backend
make test-backend         # Lance PHPUnit
make coverage-backend     # Génère le rapport de couverture

# Tests frontend
make test-frontend        # Lance Vitest
make coverage-frontend    # Génère le rapport de couverture

# Analyse statique et linting
make lint                 # Lance tous les linters (backend + frontend)
make lint-backend         # PHPCS + PHPStan niveau 8
make lint-frontend        # ESLint
make lint-fix             # Auto-fix linting issues

# Base de données
make db-migrate           # Exécute les migrations
make db-reset             # Réinitialise la base de données
make db-shell             # Accède au shell PostgreSQL

# Utilitaires
make shell-backend        # Ouvre un shell dans le container backend
make shell-frontend       # Ouvre un shell dans le container frontend
make jwt-keys             # Génère les clés JWT
make jwt-token            # Génère un token JWT de test
```

## Structure du Projet

```
defi-fullstack/
├── backend/              # API Symfony 7 + PHP 8.4
│   ├── config/          # Configuration Symfony
│   ├── src/
│   │   ├── Domain/      # Entités, Value Objects, Services Domain
│   │   ├── Application/ # Use Cases, Handlers, DTOs
│   │   ├── Infrastructure/ # Controllers, Repositories, Services
│   │   └── Kernel.php
│   ├── tests/
│   │   ├── Unit/        # Tests unitaires
│   │   └── Integration/ # Tests d'intégration
│   └── vendor/          # Dépendances Composer
│
├── frontend/             # Vue 3 + TypeScript 5
│   ├── src/
│   │   ├── components/  # Composants Vue
│   │   ├── composables/ # Logique réutilisable
│   │   ├── services/    # API calls
│   │   ├── types/       # Types TypeScript
│   │   └── views/       # Pages
│   ├── tests/           # Tests Vitest
│   └── node_modules/    # Dépendances npm
│
├── data/                 # Fichiers JSON (stations, distances)
│   ├── distances.json
│   └── stations.json
│
├── docker/               # Configuration Docker
│   ├── nginx/           # Configuration Nginx + SSL
│   ├── postgres/        # Scripts d'initialisation DB
│   └── Dockerfile.*     # Images Docker
│
├── .github/
│   └── workflows/       # CI/CD Pipeline GitHub Actions
│       └── ci.yml
│
├── directives/          # Documentation architecture
│   ├── 1-architecture.md
│   ├── 2-infrastructure.md
│   ├── 3-database.md
│   ├── 4-backend.md
│   ├── 5-frontend.md
│   ├── 6-conventions.md
│   └── 7-authentication.md
│
├── docker-compose.yml    # Orchestration Docker
├── Makefile             # Commandes simplifiées
├── DEPLOYMENT.md        # Ce fichier
├── CHANGELOG.md         # Notes de version
└── README.md            # Documentation du défi
```

## Architecture Technique

### Backend (DDD + Clean Architecture)

**Domain Layer** (Cœur métier)
- Entités pures sans dépendances externes
- Value Objects: `StationId`, `Distance` avec validation
- Services Domain: `RouteCalculator` (Dijkstra)
- Interfaces: `IdGeneratorInterface`, `DistancesDataProviderInterface`

**Application Layer** (Use Cases)
- Command/Query Handlers: `CalculateRouteHandler`
- DTOs pour les requêtes/réponses

**Infrastructure Layer** (Détails techniques)
- Controllers REST: `RouteController`, `StatsController`
- Repositories Doctrine: `DoctrineRouteRepository`
- Services: `JsonDistancesDataProvider`, `UuidGenerator`
- Custom Doctrine Types pour Value Objects
- XML ORM mapping (pas d'annotations dans Domain)

**Tests**
- PHPUnit avec couverture >70%
- Tests unitaires pour Domain/Application
- Tests d'intégration pour Infrastructure
- DAMA Doctrine Test Bundle pour transactions

**Sécurité**
- JWT Authentication (Lexik JWT Bundle)
- PHPStan niveau 8 (analyse statique stricte)
- Validation stricte des entrées
- Security headers (Nelmio CORS)

### Frontend (Composables Architecture)

**Components** (Vue 3 SFC)
- `RouteForm`: Sélection des stations avec autocomplete (Vuetify v-autocomplete)
- `RouteResult`: Timeline de trajet avec Vuetify v-timeline
- `StatsChart`: Tableau de statistiques avec filtres
- `DistanceChart`: Graphiques interactifs Chart.js (bar, horizontal bar, pie)
- `LoginForm`: Authentification JWT

**Composables** (Logique réutilisable)
- `useRoutes`: Gestion des routes (calculate, fetch)
- `useStats`: Récupération des statistiques
- `useStations`: Chargement des stations depuis /data/stations.json
- `useAuth`: Authentification et gestion du token

**Services** (API Client)
- `api.service`: Client Axios avec intercepteurs JWT
- `route.service`: Endpoints routes
- `stats.service`: Endpoints statistiques
- `auth.service`: Login/logout

**Tests**
- Vitest avec couverture >70%
- Tests unitaires pour composables
- Tests de composants avec Vue Test Utils
- Mocks pour les API calls

**Type-safety**
- TypeScript strict mode
- Types générés depuis OpenAPI spec
- Interfaces pour tous les composables

### Infrastructure

**Docker Compose**
- `nginx`: Reverse proxy avec HTTPS/TLS (auto-signed certs)
- `backend`: PHP-FPM 8.4 + Symfony 7
- `frontend`: Nginx static server (mode prod) ou Vite dev server (mode dev)
- `db`: PostgreSQL 16 avec healthchecks
- Profiles: `dev` et `prod`

**CI/CD Pipeline** (GitHub Actions)
1. **Lint**: PHPCS + ESLint
2. **Tests**: PHPUnit + Vitest (fail si <70% coverage)
3. **Security**: PHPStan level 8 + npm audit + Trivy scan
4. **Build**: Multi-stage Docker images
5. **Release**: Tagging calendaire + auto-release notes
6. **Delivery**: Push vers ghcr.io/marjc5/defi-fullstack

## Fonctionnalités Implémentées

### ✅ Fonctionnalités Core

**Calcul de distance entre stations**
- Algorithme de Dijkstra pour trouver le chemin le plus court
- Gestion des stations connectées sur plusieurs lignes
- Validation des stations (doivent exister dans distances.json)
- API: `POST /api/v1/routes`

**Interface utilisateur**
- Formulaire de sélection: station A → station B + code analytique
- Affichage du chemin complet avec distance totale
- Gestion des erreurs (stations invalides, pas de route trouvée)

**Authentification**
- JWT avec httpOnly cookies (sécurisé)
- Login/logout avec refresh automatique
- Protection des routes

**Validation stricte**
- OpenAPI 3.1 schema validation
- Validation côté backend (Symfony Validator)
- Validation côté frontend (TypeScript + formulaires)

### ✅ Fonctionnalités Bonus

**Endpoint de statistiques**
- Agrégation par code analytique
- Filtrage par période (date range)
- Groupement: jour, mois, année, ou aucun
- Calcul automatique de periodStart/periodEnd
- API: `GET /api/v1/stats/distances`

**Visualisation des statistiques**
- Graphique des distances par code analytique
- Toggle pour afficher/masquer le graphique
- Tableau récapitulatif
- Filtres interactifs

**Persistence des trajets**
- Base de données PostgreSQL avec Doctrine ORM
- Sauvegarde automatique de chaque trajet calculé
- Historique complet pour les statistiques
- Migrations Doctrine pour versionning du schéma

**Tests TDD**
- Développement en cycles RED-GREEN
- Couverture >70% backend et frontend
- Tests d'intégration bout-en-bout
- Tests de performance (Dijkstra)

## Configuration

### Variables d'Environnement

**Backend** (`backend/.env`)
```bash
APP_ENV=prod                           # prod ou dev
APP_SECRET=change-me-to-random-string  # Clé secrète Symfony
DATABASE_URL=postgresql://app:secret@db:5432/trainrouting
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=change-me-to-secure-passphrase
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
DISTANCES_PATH=/data/distances.json
API_USER_NAME=api_user
API_USER_PASSWORD_HASH='$2y$04$...'   # Généré avec password_hash()
```

**Frontend** (`frontend/.env`)
```bash
VITE_API_BASE_URL=https://localhost/api/v1
```

### SSL/TLS Certificates

Les certificats auto-signés sont générés automatiquement au démarrage dans `docker/nginx/ssl/`.

> ⚠️ **Important**: Après un rebuild (`make clean`), les certificats SSL sont régénérés et les tokens JWT deviennent invalides.
> Les utilisateurs doivent vider les données du site dans leur navigateur (cookies, cache) et se reconnecter.
> Voir la section [Dépannage - Après un Rebuild](#%EF%B8%8F-après-un-rebuild---ssl-et-jwt-invalides) pour plus de détails.

Pour utiliser des certificats Let's Encrypt en production :

```bash
# Remplacer les certificats auto-signés
docker compose exec nginx rm /etc/nginx/ssl/*
docker compose exec nginx certbot --nginx -d votre-domaine.com
docker compose restart nginx
```

> 💡 **Astuce Production**: Avec Let's Encrypt, les certificats sont persistés dans un volume Docker et ne changent pas lors des redémarrages, évitant ainsi le problème d'invalidation des JWT.

### Base de Données

**Migrations**
```bash
# Créer une migration
docker compose exec backend php bin/console make:migration

# Exécuter les migrations
docker compose exec backend php bin/console doctrine:migrations:migrate
```

**Accès direct**
```bash
docker compose exec db psql -U app -d trainrouting

# Ou depuis l'extérieur
psql -h localhost -U app -d trainrouting
```

## Développement

### Hot Reload

En mode développement, le hot reload est activé pour :
- **Backend**: Symfony avec Doctrine cache désactivé
- **Frontend**: Vite dev server avec HMR

### Debug

**Backend**
```bash
# Logs Symfony
docker compose exec backend tail -f var/log/dev.log

# Xdebug (si configuré)
export XDEBUG_MODE=debug
docker compose up -d backend
```

**Frontend**
```bash
# Vue DevTools disponibles dans Chrome/Firefox
# Console du navigateur pour les logs
```

### Ajouter des dépendances

**Backend**
```bash
docker compose exec backend composer require nom-du-package
```

**Frontend**
```bash
docker compose exec frontend npm install nom-du-package
```

## Dépannage

### Problème de connexion HTTPS

**Symptôme**: Erreur de certificat SSL/TLS

**Solution**:
- Les certificats sont auto-signés, acceptez l'exception dans votre navigateur
- Ou configurez votre navigateur pour accepter localhost

### Base de données vide

**Symptôme**: Erreur "relation does not exist"

**Solution**:
```bash
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
```

### Port déjà utilisé

**Symptôme**: `bind: address already in use`

**Solution**:
```bash
# Vérifier les ports utilisés
netstat -an | grep LISTEN | grep -E "443|5432"

# Changer les ports dans docker-compose.yml ou arrêter les services conflictuels
```

### Build échoué

**Symptôme**: Erreur lors du `docker compose up`

**Solution**:
```bash
# Rebuild complet
make clean
docker compose build --no-cache
make install-dev
```

### ⚠️ Après un Rebuild - SSL et JWT Invalides

**Symptôme**: Après `make clean` ou rebuild complet, erreurs d'authentification ou erreurs SSL

**Cause**:
- Les certificats SSL auto-signés sont régénérés à chaque rebuild
- Les tokens JWT existants deviennent invalides (nouvelle instance)
- Les cookies JWT restent dans le navigateur mais ne sont plus valides

**Solution**:
```bash
# 1. Redémarrer nginx pour charger les nouveaux certificats SSL
docker compose restart nginx

# 2. Dans le navigateur :
#    - Chrome/Edge : Ouvrir DevTools (F12) > Application > Storage > Clear site data
#    - Firefox : Ouvrir DevTools (F12) > Storage > Cookies > Supprimer tous les cookies
#    - Safari : Développement > Vider les caches
#
# Ou en navigation privée pour tester rapidement

# 3. Accepter le nouveau certificat auto-signé dans le navigateur

# 4. Se reconnecter à l'application
```

**Note**: En production avec des certificats Let's Encrypt valides, ce problème n'existe pas car les certificats sont persistés entre les redémarrages.

### Tests qui échouent

**Symptôme**: Tests rouges après un changement

**Solution**:
```bash
# Backend: vérifier la base de test
docker compose exec backend php bin/console doctrine:migrations:migrate --env=test

# Frontend: vérifier les mocks
docker compose exec frontend npm run test -- --reporter=verbose
```

### Performances lentes

**Symptôme**: Application lente en dev

**Solution**:
```bash
# Vérifier les ressources Docker
docker stats

# Augmenter les ressources dans Docker Desktop (Settings > Resources)
# Minimum recommandé: 4 GB RAM, 2 CPUs
```

## Production

### Checklist avant déploiement

- [ ] Changer `APP_SECRET` et `JWT_PASSPHRASE`
- [ ] Changer les credentials de base de données
- [ ] Changer le mot de passe API user
- [ ] Configurer des certificats SSL valides (Let's Encrypt)
- [ ] Configurer CORS pour le domaine de production
- [ ] Désactiver le mode debug (`APP_ENV=prod`)
- [ ] Configurer les backups de base de données
- [ ] Vérifier les logs et monitoring

### Performance

**Optimisations activées en production**:
- Symfony cache APCu
- Frontend build minifié (Vite)
- Nginx gzip compression
- PostgreSQL avec indexes optimisés
- Docker multi-stage builds (images légères)

### Monitoring

**Logs**
```bash
# Tous les logs
docker compose logs -f

# Logs d'erreurs uniquement
docker compose logs -f | grep -i error
```

**Santé des services**
```bash
# Healthchecks
docker compose ps

# Métriques
docker stats
```

## Support

Pour toute question ou problème :
1. Consultez la [documentation du défi](README.md)
2. Vérifiez les [directives d'architecture](directives/)
3. Examinez les [logs de CI/CD](https://github.com/MarJC5/defi-fullstack/actions)
4. Consultez le [CHANGELOG](CHANGELOG.md) pour les dernières modifications

## Ressources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Vue.js 3 Guide](https://vuejs.org/guide/introduction.html)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
