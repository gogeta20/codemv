export default [
  {
    path: '/studies',
    component: () => import('@/Study/Infrastructure/View/StudiesListView.vue'),
  },
  {
    path: '/studies/:uuid',
    component: () => import('@/Study/Infrastructure/View/StudyDetailView.vue'),
  },
  {
    path: '/studies/:uuid/edit',
    component: () => import('@/Study/Infrastructure/View/StudyEditView.vue'),
  },
]
