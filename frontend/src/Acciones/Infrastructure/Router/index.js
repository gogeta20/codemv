export default [
  {
    path: '/acciones',
    component: () => import('@/Acciones/Infrastructure/View/AccionesListView.vue'),
    meta: { view: 'AccionesListView.vue', endpoint: 'GET /api/acciones' },
  },
  {
    path: '/acciones/:uuid',
    component: () => import('@/Acciones/Infrastructure/View/AccionDetailView.vue'),
    meta: { view: 'AccionDetailView.vue', endpoint: 'GET /api/acciones/:uuid/noticias' },
  },
  {
    path: '/acciones/:uuid/analisis',
    component: () => import('@/Acciones/Infrastructure/View/AccionAnalisisView.vue'),
    meta: { view: 'AccionAnalisisView.vue', endpoint: 'GET /api/acciones/:uuid/analisis' },
  },
  {
    path: '/acciones/:uuid/earnings',
    component: () => import('@/Acciones/Infrastructure/View/AccionEarningsAnalysisView.vue'),
    meta: { view: 'AccionEarningsAnalysisView.vue', endpoint: 'GET /api/acciones/:uuid/earnings-analysis' },
  },
  {
    path: '/portafolio',
    component: () => import('@/Acciones/Infrastructure/View/PortafolioListView.vue'),
    meta: { view: 'PortafolioListView.vue', endpoint: 'GET /api/portafolio' },
  },
  {
    path: '/portafolio/:uuid',
    component: () => import('@/Acciones/Infrastructure/View/PortafolioDetailView.vue'),
    meta: { view: 'PortafolioDetailView.vue', endpoint: 'GET /api/portafolio/:uuid' },
  },
]
