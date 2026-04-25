import { IdentityHttpClient } from '@/Pruebas/Identity/Domain/IdentityHttpClient'

/**
 * Deletes an organization via Identity API.
 *
 * @param {string} uuid - Organization UUID
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function DeleteOrganizationUseCase(uuid) {
  const start = performance.now()

  try {
    const client = IdentityHttpClient.createClient()

    await client.delete(`/internal/api/identity/v1/organization/${uuid}`)

    const duration = Math.round(performance.now() - start)

    return {
      success: true,
      data: { message: `Organization ${uuid} deleted` },
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

export { DeleteOrganizationUseCase }
