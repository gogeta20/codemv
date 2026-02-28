export default [
  {
    path: '/studies',
    component: () => import('@/Study/Infrastructure/View/StudiesListView.vue'),
    meta: { view: 'StudiesListView.vue', endpoint: 'GET /api/studies' },
  },
  {
    path: '/studies/:uuid',
    component: () => import('@/Study/Infrastructure/View/StudyDetailView.vue'),
    meta: { view: 'StudyDetailView.vue', endpoint: 'GET /api/studies/{uuid}' },
  },
  {
    path: '/studies/:uuid/edit',
    component: () => import('@/Study/Infrastructure/View/StudyEditView.vue'),
    meta: { view: 'StudyEditView.vue', endpoint: 'PUT /api/studies/{uuid}' },
  },
]
