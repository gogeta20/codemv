import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(300)
  return Mock
}

async function Api(samAccountName) {
  const response = await httpClient.get(`/api/ad/users/${encodeURIComponent(samAccountName)}`)
  return response.data.data
}

async function GetUserUseCase(samAccountName) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(samAccountName)
}

export { GetUserUseCase }
