import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'
import mock from './mock.json'

async function Api() {
  const response = await httpClient.get('/api/futbol/ligas')
  return response.data.data
}

async function InMemory() {
  await UtilHelper.wait(200)
  return mock
}

async function ListLigasUseCase() {
  return UtilHelper.checkEnvironment() ? InMemory() : Api()
}

export { ListLigasUseCase }
