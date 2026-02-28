export default [
  {
    path: '/pruebas/identity',
    component: () => import('@/Pruebas/Identity/Infrastructure/View/PruebasIdentityView.vue'),
    meta: { view: 'PruebasIdentityView.vue', endpoint: 'Identity API Tests' },
  },
]
