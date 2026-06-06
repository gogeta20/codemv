import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory() {
  await UtilHelper.wait(400)
  return { accion: {}, noticias: [] }
}

async function Api(uuid) {
  const response = await httpClient.get(`/api/acciones/${uuid}/noticias`)
  return response.data
}

async function GetAccionNoticiasUseCase(uuid) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid)
}

export { GetAccionNoticiasUseCase }
