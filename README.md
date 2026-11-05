# BrokerDesk

A mini-SaaS for Belgian insurance brokers: clients, contracts, renewals and claims, with a separate portal for policyholders.

> Work in progress, built phase by phase.

| Service | Purpose | Stack | Local URL |
| --- | --- | --- | --- |
| `apps/api` | REST API | Laravel 13, PHP 8.4, PostgreSQL 17 | http://localhost:8000 |
| `apps/broker` | Broker back office | Vue 3, TypeScript, Pinia | http://localhost:5173 |
| `apps/portal` | Client portal | Next.js, React, TypeScript | http://localhost:3000 |
| `mailpit` | Local mail catcher | Mailpit | http://localhost:8025 |

## Getting started

Docker is the only requirement:

```sh
git clone https://github.com/DonkeyhTv/brokerdesk.git
cd brokerdesk
docker compose up
```

The first start installs dependencies and can take a few minutes.

## Quality checks

```sh
docker compose exec php composer format
docker compose exec php composer analyse
docker compose exec php composer test
docker compose exec broker npm test
docker compose exec portal npm test
```

GitHub Actions runs linting, static analysis, tests and builds on every push, then boots the full stack with Docker Compose.
