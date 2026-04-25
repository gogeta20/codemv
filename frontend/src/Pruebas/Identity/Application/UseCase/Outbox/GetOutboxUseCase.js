import httpClient from '@/core/http/HttpClient'

/**
 * Fetches recent Identity outbox messages via codemv backend proxy.
 *
 * @param {number} limit
 * @returns {Promise<{success: boolean, data?: any[], error?: string}>}
 */
async function GetOutboxUseCase(limit = 50) {
  try {
    const response = await httpClient.get('/api/identity/outbox', { params: { limit } })
    return {
      success: true,
      data: response.data.data,
    }
  } catch (err) {
    const message = err.response?.data?.detail || err.response?.data?.message || err.message
    return {
      success: false,
      error: `${err.response?.status || 'ERR'}: ${message}`,
      data: [],
    }
  }
}

export { GetOutboxUseCase }
