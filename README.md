# BrokerDesk

Mini-SaaS pour courtiers en assurance.

| Application | Rôle | Stack | URL locale |
| --- | --- | --- | --- |
| `apps/api` | API back-end | Laravel 13, PostgreSQL 17 | http://localhost:8000 |
| `apps/broker` | Espace courtier | Vue 3, TypeScript, Pinia | http://localhost:5173 |
| `apps/portal` | Portail client | Next.js, React, TypeScript | http://localhost:3000 |

## Démarrage

Seul Docker est nécessaire :

```sh
docker compose up --build
```

Le premier lancement installe les dépendances et peut prendre quelques minutes. Les deux front-ends appellent l'API via un proxy sur `/api`, et affichent son état sur leur page d'accueil.

## Tests

```sh
docker compose exec api php artisan test
docker compose exec broker npm test
docker compose exec portal npm test
```

La CI GitHub Actions (`.github/workflows/ci.yml`) lance le lint, les tests et le build des trois applications. Les tests de l'API y tournent sur PostgreSQL.
