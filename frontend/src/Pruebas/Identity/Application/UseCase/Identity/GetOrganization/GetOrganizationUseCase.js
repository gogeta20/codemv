import { IdentityHttpClient } from '@/Pruebas/Identity/Domain/IdentityHttpClient'

/**
 * Fetches organization details by UUID from Identity API.
 * Returns merged local (PostgreSQL) + remote (LDAP) data.
 *
 * @param {string} uuid
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function GetOrganizationUseCase(uuid) {
  const start = performance.now()

  try {
    const client = IdentityHttpClient.createClient()
    const response = await client.get(`/internal/api/identity/v1/organization/${uuid}`)
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

export { GetOrganizationUseCase }
