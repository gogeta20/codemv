import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function Api(uuid) {
  await httpClient.delete(`/api/futbol/favoritos/${uuid}`)
}

async function RemoveFavoritoUseCase(uuid) {
  if (UtilHelper.checkEnvironment()) return
  return await Api(uuid)
}

export { RemoveFavoritoUseCase }
