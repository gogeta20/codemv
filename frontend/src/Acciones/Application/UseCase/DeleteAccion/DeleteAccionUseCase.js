import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory() {
  await UtilHelper.wait(300)
}

async function Api(uuid) {
  await httpClient.delete(`/api/acciones/${uuid}`)
}

async function DeleteAccionUseCase(uuid) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid)
}

export { DeleteAccionUseCase }
