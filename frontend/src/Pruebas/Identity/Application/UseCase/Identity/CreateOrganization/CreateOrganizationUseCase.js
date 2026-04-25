import { IdentityHttpClient } from '@/Pruebas/Identity/Domain/IdentityHttpClient'

/**
 * Creates an organization via Identity API.
 *
 * @param {Object} params
 * @param {string} params.organizationCode
 * @param {string} params.organizationName
 * @param {string} params.email
 * @param {string} [params.provider='ldap']
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function CreateOrganizationUseCase({ uuid, organizationCode, organizationName, email, provider }) {
  const start = performance.now()

  try {
    const client = IdentityHttpClient.createClient()

    const payload = { uuid, organizationCode, organizationName, email }
    if (provider) payload.provider = provider

    const response = await client.post('/internal/api/identity/v1/organization', { data: payload })

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

export { CreateOrganizationUseCase }
