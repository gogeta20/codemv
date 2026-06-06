import { createRouter, createWebHistory } from 'vue-router'
import studyRoutes from '@/Study/Infrastructure/Router'
import activeDirectoryRoutes from '@/ActiveDirectory/Infrastructure/Router'
import pruebasRoutes from '@/Pruebas/Infrastructure/Router'
import accionesRoutes from '@/Acciones/Infrastructure/Router'
import futbolRoutes from '@/Futbol/Infrastructure/Router'

const routes = [
  { path: '/', redirect: '/studies' },
  ...studyRoutes,
  ...activeDirectoryRoutes,
  ...pruebasRoutes,
  ...accionesRoutes,
  ...futbolRoutes,
]

export default createRouter({
  history: createWebHistory(),
  routes,
})
