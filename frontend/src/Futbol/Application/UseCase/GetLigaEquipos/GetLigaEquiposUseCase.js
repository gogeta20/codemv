import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(500)
  return Mock
}

async function Api(codigo) {
  const response = await httpClient.get(`/api/futbol/ligas/${codigo}/equipos`)
  return response.data.data
}

async function GetLigaEquiposUseCase(codigo) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(codigo)
}

export { GetLigaEquiposUseCase }
