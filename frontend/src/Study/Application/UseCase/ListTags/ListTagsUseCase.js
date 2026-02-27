import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(200)
  return Mock
}

async function Api() {
  const response = await httpClient.get('/api/tags')
  return response.data.data
}

async function ListTagsUseCase() {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api()
}

export { ListTagsUseCase }
