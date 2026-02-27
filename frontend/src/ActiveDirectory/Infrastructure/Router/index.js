export default [
  {
    path: '/ad/organizations',
    component: () => import('@/ActiveDirectory/Infrastructure/View/OrganizationsListView.vue'),
  },
  {
    path: '/ad/organizations/:code',
    component: () => import('@/ActiveDirectory/Infrastructure/View/OrganizationDetailView.vue'),
  },
]
