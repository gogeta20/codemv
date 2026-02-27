import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import Mock from './mock.json'

async function InMemory() {
  await UtilHelper.wait(400)
  return Mock
}

async function Api() {
  const response = await httpClient.get('/api/studies')
  return response.data.data
}

async function ListStudiesUseCase() {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api()
}

export { ListStudiesUseCase }
