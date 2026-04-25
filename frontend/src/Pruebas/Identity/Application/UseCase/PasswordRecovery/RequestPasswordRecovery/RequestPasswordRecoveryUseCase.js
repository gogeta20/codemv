import httpClient from '@/core/http/HttpClient'

/**
 * Request password recovery (generates token)
 *
 * @param {string} email - User email
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function RequestPasswordRecoveryUseCase(email) {
  const start = performance.now()

  try {
    const response = await httpClient.post('/api/identity/password-recovery/request', {
      data: { email },
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

export { RequestPasswordRecoveryUseCase }
