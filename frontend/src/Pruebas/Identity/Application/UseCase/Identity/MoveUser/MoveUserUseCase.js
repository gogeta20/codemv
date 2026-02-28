import { IdentityHttpClient } from '@/Pruebas/Identity/Domain/IdentityHttpClient'

/**
 * Moves a user to a different organization via Identity API.
 *
 * @param {string} userUuid
 * @param {string} targetOrganizationUuid
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function MoveUserUseCase(userUuid, targetOrganizationUuid) {
  const start = performance.now()

  try {
    const client = IdentityHttpClient.createClient()

    const response = await client.put(`/api/identity/v1/user/${userUuid}/organization`, {
      data: {
        targetOrganizationUuid,
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

export { MoveUserUseCase }
