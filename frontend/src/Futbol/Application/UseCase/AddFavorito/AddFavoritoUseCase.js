import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function Api(data) {
  const response = await httpClient.post('/api/futbol/favoritos', data)
  return response.data
}

async function AddFavoritoUseCase(data) {
  return UtilHelper.checkEnvironment() ? data : await Api(data)
}

export { AddFavoritoUseCase }
