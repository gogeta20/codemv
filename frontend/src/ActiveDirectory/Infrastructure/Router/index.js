export default [
  {
    path: '/ad/organizations',
    component: () => import('@/ActiveDirectory/Infrastructure/View/OrganizationsListView.vue'),
    meta: { view: 'OrganizationsListView.vue', endpoint: 'GET /api/ad/organizations' },
  },
  {
    path: '/ad/organizations/:code',
    component: () => import('@/ActiveDirectory/Infrastructure/View/OrganizationDetailView.vue'),
    meta: { view: 'OrganizationDetailView.vue', endpoint: 'GET /api/ad/organizations/{code}' },
  },
]
