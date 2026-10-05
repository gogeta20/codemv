import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(200)
  return Mock
}

async function Api(competition, eventId) {
  const response = await httpClient.get(`/api/futbol/copas/${competition}/${eventId}`)
  return response.data.data
}

async function GetCopaPartidoDetalleUseCase(competition, eventId) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(competition, eventId)
}

export { GetCopaPartidoDetalleUseCase }
