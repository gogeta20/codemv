import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory() {
  await UtilHelper.wait(400)
  return []
}

async function Api() {
  const response = await httpClient.get('/api/portafolio')
  return response.data.data
}

async function ListPortafolioUseCase() {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api()
}

export { ListPortafolioUseCase }
