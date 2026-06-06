import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory(uuid, payload) {
  await UtilHelper.wait(300)
  return { uuid, ...payload }
}

async function Api(uuid, payload) {
  const response = await httpClient.patch(`/api/acciones/${uuid}`, payload)
  return response.data
}

async function UpdateAccionUseCase(uuid, payload) {
  return UtilHelper.checkEnvironment() ? await InMemory(uuid, payload) : await Api(uuid, payload)
}

export { UpdateAccionUseCase }
