import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import mock from './mock.json'

async function Api(playerId) {
  const response = await httpClient.get(`/api/futbol/jugadores/${playerId}/analisis`)
  return response.data.data
}

async function InMemory() {
  await UtilHelper.wait(200)
  return mock
}

async function GetJugadorAnalisisUseCase(playerId) {
  return UtilHelper.checkEnvironment() ? InMemory() : Api(playerId)
}

export { GetJugadorAnalisisUseCase }
