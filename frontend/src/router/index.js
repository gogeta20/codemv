import { createRouter, createWebHistory } from 'vue-router'
import studyRoutes from '@/Study/Infrastructure/Router'
import activeDirectoryRoutes from '@/ActiveDirectory/Infrastructure/Router'

const routes = [
  { path: '/', redirect: '/studies' },
  ...studyRoutes,
  ...activeDirectoryRoutes,
]

export default createRouter({
  history: createWebHistory(),
  routes,
})
