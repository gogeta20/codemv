import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory() {
  await UtilHelper.wait(300)
}

async function Api(uuid) {
  await httpClient.delete(`/api/portafolio/${uuid}`)
}

async function DeletePortafolioEntryUseCase(uuid) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid)
}

export { DeletePortafolioEntryUseCase }
