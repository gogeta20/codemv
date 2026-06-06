import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory() {
  await UtilHelper.wait(400)
  return { uuid: '', nombre: '', descripcion: '', acciones: [] }
}

async function Api(uuid) {
  const response = await httpClient.get(`/api/portafolio/${uuid}`)
  return response.data
}

async function GetPortafolioUseCase(uuid) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid)
}

export { GetPortafolioUseCase }
