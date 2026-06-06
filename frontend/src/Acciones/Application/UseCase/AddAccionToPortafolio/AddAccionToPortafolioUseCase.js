import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory(portafolioUuid, payload) {
  await UtilHelper.wait(300)
  return { uuid: UtilHelper.generateUUID(), portafolio_uuid: portafolioUuid, ...payload }
}

async function Api(portafolioUuid, payload) {
  const response = await httpClient.post(`/api/portafolio/${portafolioUuid}/acciones`, payload)
  return response.data
}

async function AddAccionToPortafolioUseCase(portafolioUuid, payload) {
  return UtilHelper.checkEnvironment() ? await InMemory(portafolioUuid, payload) : await Api(portafolioUuid, payload)
}

export { AddAccionToPortafolioUseCase }
