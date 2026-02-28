import httpClient from '@/core/http/HttpClient'

/**
 * Verifies organization/user existence in Active Directory via codemv API.
 *
 * @param {string} path - API path (e.g. /api/ad/organizations/WCODE or /api/ad/users/samAccountName)
 * @param {boolean} shouldExist - true = expect 200, false = expect 404
 * @returns {Promise<{success: boolean, exists: boolean, data?: any, error?: string, duration: number}>}
 */
async function VerifyInAdUseCase(path, shouldExist = true) {
  const start = performance.now()

  try {
    const response = await httpClient.get(path)
    const duration = Math.round(performance.now() - start)
    const exists = true

    return {
      success: shouldExist === exists,
      exists,
      data: response.data,
      duration,
    }
  } catch (err) {
    const duration = Math.round(performance.now() - start)
    const status = err.response?.status

    if (status === 404) {
      const exists = false
      return {
        success: shouldExist === exists,
        exists,
        data: { message: 'Not found in AD (404)' },
        duration,
      }
    }

    return {
      success: false,
      exists: false,
      error: `${status || 'ERR'}: ${err.message}`,
      duration,
    }
  }
}

export { VerifyInAdUseCase }
