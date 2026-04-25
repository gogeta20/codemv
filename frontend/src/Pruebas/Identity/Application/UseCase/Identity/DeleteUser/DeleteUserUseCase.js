import { IdentityHttpClient } from '@/Pruebas/Identity/Domain/IdentityHttpClient'

/**
 * Deletes a user (remote LDAP + local DB) via Identity API.
 *
 * @param {string} uuid
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function DeleteUserUseCase(uuid) {
  const start = performance.now()

  try {
    const client = IdentityHttpClient.createClient()

    await client.delete(`/internal/api/identity/v1/user/${uuid}`)

    const duration = Math.round(performance.now() - start)

    return {
      success: true,
      data: { message: 'User deleted (204)' },
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

export { DeleteUserUseCase }
