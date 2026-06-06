import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function Api() {
  const response = await httpClient.get('/api/futbol/favoritos')
  return response.data.data
}

async function ListFavoritosUseCase() {
  return UtilHelper.checkEnvironment() ? [] : await Api()
}

export { ListFavoritosUseCase }
