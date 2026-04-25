import { IdentityHttpClient } from '@/Pruebas/Identity/Domain/IdentityHttpClient'

/**
 * Sets a new password for a user via Identity API (admin action).
 *
 * @param {string} uuid
 * @param {string} newPassword
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function SetPasswordUseCase(uuid, newPassword) {
  const start = performance.now()

  try {
    const client = IdentityHttpClient.createClient()

    await client.patch(`/internal/api/identity/v1/user/${uuid}`, {
      data: { password: newPassword },
    })

    const duration = Math.round(performance.now() - start)

    return {
      success: true,
      data: { message: 'Password changed (204)' },
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

export { SetPasswordUseCase }
