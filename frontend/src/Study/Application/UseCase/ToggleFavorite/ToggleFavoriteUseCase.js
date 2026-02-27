import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory() {
  await UtilHelper.wait(200)
  return { status: 200 }
}

async function Api(uuid) {
  const response = await httpClient.post(`/api/studies/${uuid}/favorite`)
  return { status: response.status }
}

async function ToggleFavoriteUseCase(uuid) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid)
}

export { ToggleFavoriteUseCase }
