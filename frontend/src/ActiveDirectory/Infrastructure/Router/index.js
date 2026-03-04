export default [
  {
    path: '/ad',
    redirect: '/ad/organizations',
    component: () => import('@/ActiveDirectory/Infrastructure/View/ActiveDirectoryLayout.vue'),
    children: [
      {
        path: 'organizations',
        component: () => import('@/ActiveDirectory/Infrastructure/View/OrganizationsListView.vue'),
        meta: { view: 'OrganizationsListView.vue', endpoint: 'GET /api/ad/organizations' },
      },
      {
        path: 'users',
        component: () => import('@/ActiveDirectory/Infrastructure/View/UsersListView.vue'),
        meta: { view: 'UsersListView.vue', endpoint: 'GET /api/ad/users' },
      },
    ],
  },
  {
    path: '/ad/organizations/:code',
    component: () => import('@/ActiveDirectory/Infrastructure/View/OrganizationDetailView.vue'),
    meta: { view: 'OrganizationDetailView.vue', endpoint: 'GET /api/ad/organizations/{code}' },
  },
  {
    path: '/ad/users/:samaccountname',
    component: () => import('@/ActiveDirectory/Infrastructure/View/UserDetailView.vue'),
    meta: { view: 'UserDetailView.vue', endpoint: 'GET /api/ad/users/{samAccountName}' },
  },
]
