import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(400)
  return Mock
}

async function Api(query) {
  const response = await httpClient.get('/api/ad/users/search', { params: { q: query } })
  return response.data.data
}

async function SearchUsersUseCase(query) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(query)
}

export { SearchUsersUseCase }
