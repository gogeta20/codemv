import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(300)
  return Mock
}

async function Api(fecha) {
  const params = fecha ? { fecha } : {}
  const response = await httpClient.get('/api/futbol/copas', { params })
  return response.data.data
}

async function GetCopasPartidosUseCase(fecha = null) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(fecha)
}

export { GetCopasPartidosUseCase }
