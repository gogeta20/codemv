import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/studies'
  },
  {
    path: '/studies',
    component: () => import('@/modules/studies/StudiesListView.vue')
  },
  {
    path: '/studies/:uuid',
    component: () => import('@/modules/studies/StudyDetailView.vue')
  },
  {
    path: '/studies/:uuid/edit',
    component: () => import('@/modules/studies/StudyEditView.vue')
  }
]

export default createRouter({
  history: createWebHistory(),
  routes
})
