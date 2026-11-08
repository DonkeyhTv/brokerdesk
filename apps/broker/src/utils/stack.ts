// Shown to people evaluating the application. The client portal keeps the same list in src/lib/stack.ts.

export const SOURCE_URL = 'https://github.com/DonkeyhTv/brokerdesk'

export type StackLayerId = 'broker' | 'portal' | 'api' | 'database' | 'infrastructure' | 'quality'

export interface StackLayer {
  id: StackLayerId
  name: string
  tools: string[]
  role: string
}

export const STACK: StackLayer[] = [
  {
    id: 'broker',
    name: 'Espace courtier',
    tools: ['Vue 3', 'TypeScript', 'Pinia', 'Vue Router', 'Tailwind CSS', 'Vite'],
    role: 'Une application monopage pour un outil utilisé toute la journée : tableaux, filtres et formulaires.',
  },
  {
    id: 'portal',
    name: 'Portail client',
    tools: ['Next.js 16', 'React 19', 'TypeScript', 'Tailwind CSS'],
    role: 'Des pages rendues sur le serveur : la clé de connexion du client ne quitte jamais le serveur.',
  },
  {
    id: 'api',
    name: 'API',
    tools: ['Laravel 13', 'PHP 8.4', 'Sanctum'],
    role: 'Les règles métier, les droits d’accès, les alertes planifiées et les envois de mails.',
  },
  {
    id: 'database',
    name: 'Base de données',
    tools: ['PostgreSQL 17'],
    role: 'Des contraintes et des clés étrangères composites qui empêchent un cabinet de voir les données d’un autre.',
  },
  {
    id: 'infrastructure',
    name: 'Infrastructure',
    tools: ['Docker', 'Caddy', 'nginx'],
    role: 'Une commande pour tout lancer, et le HTTPS en production.',
  },
  {
    id: 'quality',
    name: 'Qualité',
    tools: ['Pest', 'Vitest', 'Playwright', 'Larastan', 'GitHub Actions'],
    role: 'Des tests à chaque niveau, jusqu’au vrai navigateur, rejoués à chaque modification.',
  },
]
