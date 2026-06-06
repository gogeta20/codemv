import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory(payload) {
  await UtilHelper.wait(300)
  return { ...payload, uuid: UtilHelper.generateUUID(), total: 0 }
}

async function Api(payload) {
  const response = await httpClient.post('/api/portafolio', payload)
  return response.data
}

async function CreatePortafolioEntryUseCase(payload) {
  return UtilHelper.checkEnvironment() ? await InMemory(payload) : await Api(payload)
}

export { CreatePortafolioEntryUseCase }
