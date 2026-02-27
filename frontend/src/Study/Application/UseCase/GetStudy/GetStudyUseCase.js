import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(300)
  return Mock
}

async function Api(uuid) {
  const response = await httpClient.get(`/api/studies/${uuid}`)
  return response.data.data
}

async function GetStudyUseCase(uuid) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid)
}

export { GetStudyUseCase }
