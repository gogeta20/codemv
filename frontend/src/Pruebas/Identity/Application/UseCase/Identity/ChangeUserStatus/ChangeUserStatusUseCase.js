import { IdentityHttpClient } from '@/Pruebas/Identity/Domain/IdentityHttpClient'

/**
 * Changes user status (enable/disable) via Identity API.
 *
 * @param {string} uuid
 * @param {'user.status.enabled'|'user.status.disabled'} status
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function ChangeUserStatusUseCase(uuid, status) {
  const start = performance.now()

  try {
    const client = IdentityHttpClient.createClient()

    const response = await client.patch(`/internal/api/identity/v1/user/${uuid}`, {
      data: { status },
    })

    const duration = Math.round(performance.now() - start)

    return {
      success: true,
      data: response.data ?? { message: 'Status changed (204)' },
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

export { ChangeUserStatusUseCase }
