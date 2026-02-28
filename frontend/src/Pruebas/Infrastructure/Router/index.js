import identityRoutes from '@/Pruebas/Identity/Infrastructure/Router'

export default [
  {
    path: '/pruebas',
    component: () => import('@/Pruebas/Infrastructure/View/PruebasHomeView.vue'),
    meta: { view: 'PruebasHomeView.vue', endpoint: 'Contextos de Pruebas' },
  },
  ...identityRoutes,
]
