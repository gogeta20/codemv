import httpClient from '@/core/http/HttpClient'

/**
 * Reset password using recovery token
 *
 * @param {string} token - Recovery token (64 chars hex)
 * @param {string} newPassword - New password
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function ResetPasswordUseCase(token, newPassword) {
  const start = performance.now()

  try {
    const response = await httpClient.post('/api/identity/password-recovery/reset', {
      data: { token, newPassword },
    })

    const duration = Math.round(performance.now() - start)

    return {
      success: true,
      data: response.data.data,
      duration,
    }
  } catch (err) {
    const duration = Math.round(performance.now() - start)
    const message = err.response?.data?.error || err.response?.data?.message || err.message

    return {
      success: false,
      error: `${err.response?.status || 'ERR'}: ${message}`,
      duration,
    }
  }
}

export { ResetPasswordUseCase }
