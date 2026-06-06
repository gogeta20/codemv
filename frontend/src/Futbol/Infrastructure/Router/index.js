export default [
  {
    path: '/futbol',
    redirect: '/futbol/seleccion',
  },
  {
    path: '/futbol/seleccion',
    component: () => import('@/Futbol/Infrastructure/View/FutbolSeleccionView.vue'),
    meta: { view: 'FutbolSeleccionView.vue', endpoint: 'GET /api/futbol/seleccion' },
  },
  {
    path: '/futbol/partidos',
    component: () => import('@/Futbol/Infrastructure/View/FutbolPartidosView.vue'),
    meta: { view: 'FutbolPartidosView.vue', endpoint: 'GET /api/futbol/partidos' },
  },
  {
    path: '/futbol/partidos/:uuid',
    component: () => import('@/Futbol/Infrastructure/View/FutbolPartidoDetalleView.vue'),
    meta: { view: 'FutbolPartidoDetalleView.vue', endpoint: 'GET /api/futbol/partidos/{uuid}' },
  },
  {
    path: '/futbol/ligas/gpm',
    component: () => import('@/Futbol/Infrastructure/View/FutbolLigasGpmView.vue'),
    meta: { view: 'FutbolLigasGpmView.vue', endpoint: 'GET /api/futbol/ligas/gpm' },
  },
  {
    path: '/futbol/ligas/:codigo/equipos',
    component: () => import('@/Futbol/Infrastructure/View/FutbolLigaEquiposView.vue'),
    meta: { view: 'FutbolLigaEquiposView.vue', endpoint: 'GET /api/futbol/ligas/{codigo}/equipos' },
  },
]
