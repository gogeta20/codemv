import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(300)
  return Mock
}

async function Api(code) {
  const response = await httpClient.get(`/api/ad/organizations/${encodeURIComponent(code)}`)
  return response.data.data
}

async function GetOrganizationUseCase(code) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(code)
}

export { GetOrganizationUseCase }
