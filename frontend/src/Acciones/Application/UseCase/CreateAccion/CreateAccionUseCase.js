import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory(payload) {
  await UtilHelper.wait(300)
  return { ...payload, uuid: UtilHelper.generateUUID(), source: 'manual', is_active: true }
}

async function Api(payload) {
  const response = await httpClient.post('/api/acciones', payload)
  return response.data
}

async function CreateAccionUseCase(payload) {
  return UtilHelper.checkEnvironment() ? await InMemory(payload) : await Api(payload)
}

export { CreateAccionUseCase }
