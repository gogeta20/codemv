import httpClient from '@/core/http/HttpClient'

/**
 * List active recovery tokens
 *
 * @param {boolean} showAll - Include expired tokens
 * @returns {Promise<{success: boolean, data?: any, error?: string, duration: number}>}
 */
async function ListTokensUseCase(showAll = false) {
  const start = performance.now()

  try {
    const params = showAll ? { all: true } : {}
    const response = await httpClient.get('/api/identity/password-recovery/tokens', { params })

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

export { ListTokensUseCase }
