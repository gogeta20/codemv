import httpClient from '@/core/http/HttpClient'

/**
 * Triggers the full event pipeline:
 *   1. identity outbox → RabbitMQ
 *   2. RabbitMQ → panel inbox
 *   3. panel inbox → handlers
 *
 * @returns {Promise<{success: boolean, data?: object, error?: string}>}
 */
async function RunPipelineUseCase() {
  try {
    const response = await httpClient.post('/api/identity/pipeline/publish')
    return {
      success: true,
      data: response.data.data,
    }
  } catch (err) {
    const message = err.response?.data?.detail || err.response?.data?.message || err.message
    return {
      success: false,
      error: `${err.response?.status || 'ERR'}: ${message}`,
    }
  }
}

export { RunPipelineUseCase }
