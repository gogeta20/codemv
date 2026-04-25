import identityRoutes from '@/Pruebas/Identity/Infrastructure/Router'

export default [
  {
    path: '/pruebas',
    component: () => import('@/Pruebas/Infrastructure/View/PruebasHomeView.vue'),
    meta: { view: 'PruebasHomeView.vue', endpoint: 'Contextos de Pruebas' },
  },
  {
    path: '/pruebas/report',
    component: () => import('@/Pruebas/Infrastructure/View/TestReportView.vue'),
    meta: { view: 'TestReportView.vue', endpoint: 'Informe de pruebas' },
  },
  ...identityRoutes,
]
