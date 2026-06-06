import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(400)
  return Mock.data
}

async function Api(uuid) {
  const response = await httpClient.get(`/api/futbol/partidos/${uuid}`)
  return response.data.data
}

async function GetPartidoDetalleUseCase(uuid) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid)
}

export { GetPartidoDetalleUseCase }
