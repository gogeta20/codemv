import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import mockData from './mock.json'

async function InMemory() {
  await UtilHelper.wait(400)
  return mockData
}

async function Api(uuid) {
  const response = await httpClient.get(`/api/acciones/${uuid}/earnings-analysis`)
  return response.data
}

async function GetEarningsAnalysisUseCase(uuid) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid)
}

export { GetEarningsAnalysisUseCase }
