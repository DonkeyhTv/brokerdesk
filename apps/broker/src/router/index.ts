import { createRouter, createWebHistory } from 'vue-router'
import { authGuard } from './guard'

declare module 'vue-router' {
  interface RouteMeta {
    public?: boolean
    admin?: boolean
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { public: true },
    },
    { path: '/', name: 'dashboard', component: () => import('@/views/DashboardView.vue') },
    { path: '/clients', name: 'clients', component: () => import('@/views/ClientsView.vue') },
    {
      path: '/clients/new',
      name: 'client-new',
      component: () => import('@/views/ClientFormView.vue'),
    },
    {
      path: '/clients/:id',
      name: 'client',
      component: () => import('@/views/ClientDetailView.vue'),
      props: true,
    },
    {
      path: '/clients/:id/edit',
      name: 'client-edit',
      component: () => import('@/views/ClientFormView.vue'),
      props: true,
    },
    { path: '/contracts', name: 'contracts', component: () => import('@/views/ContractsView.vue') },
    {
      path: '/contracts/new',
      name: 'contract-new',
      component: () => import('@/views/ContractFormView.vue'),
    },
    {
      path: '/contracts/:id',
      name: 'contract',
      component: () => import('@/views/ContractDetailView.vue'),
      props: true,
    },
    {
      path: '/contracts/:id/edit',
      name: 'contract-edit',
      component: () => import('@/views/ContractFormView.vue'),
      props: true,
    },
    { path: '/claims', name: 'claims', component: () => import('@/views/ClaimsView.vue') },
    {
      path: '/claims/new',
      name: 'claim-new',
      component: () => import('@/views/ClaimFormView.vue'),
    },
    {
      path: '/claims/:id',
      name: 'claim',
      component: () => import('@/views/ClaimDetailView.vue'),
      props: true,
    },
    {
      path: '/notifications',
      name: 'notifications',
      component: () => import('@/views/NotificationsView.vue'),
    },
    {
      path: '/activity',
      name: 'activity',
      component: () => import('@/views/ActivityView.vue'),
      meta: { admin: true },
    },
    {
      path: '/users',
      name: 'users',
      component: () => import('@/views/UsersView.vue'),
      meta: { admin: true },
    },
    {
      path: '/a-propos',
      name: 'about',
      component: () => import('@/views/AboutView.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
})

router.beforeEach(authGuard)

export default router
