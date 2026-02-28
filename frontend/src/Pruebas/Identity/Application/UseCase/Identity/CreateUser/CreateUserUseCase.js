import { IdentityHttpClient } from '@/Pruebas/Identity/Domain/IdentityHttpClient'

/**
 * Creates a user via Identity API.
 *
 * @param {Object} params
 * @param {string} params.uuid
 * @param {string} params.organizationCode
 * @param {string} params.email
 * @param {string} params.name
 * @param {string} params.firstSurname
 * @param {string} params.lastName
 * @param {string} params.initials
 * @param {string} params.password
 * @param {string} [params.provider='ldap']
 * @param {string} [params.status='enabled']
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function CreateUserUseCase(params) {
  const start = performance.now()

  try {
    const client = IdentityHttpClient.createClient()

    const response = await client.post('/api/identity/v1/user', {
      data: {
        uuid: params.uuid,
        provider: params.provider || 'ldap',
        organizationCode: params.organizationCode,
        email: params.email,
        name: params.name,
        firstSurname: params.firstSurname,
        lastName: params.lastName,
        initials: params.initials,
        password: params.password,
      },
    })

    const duration = Math.round(performance.now() - start)

    return {
      success: true,
      data: response.data,
      duration,
    }
  } catch (err) {
    const duration = Math.round(performance.now() - start)
    const message = err.response?.data?.detail || err.response?.data?.message || err.message

    return {
      success: false,
      error: `${err.response?.status || 'ERR'}: ${message}`,
      duration,
    }
  }
}

export { CreateUserUseCase }
