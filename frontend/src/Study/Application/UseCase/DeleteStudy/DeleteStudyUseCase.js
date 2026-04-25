import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory() {
  await UtilHelper.wait(300)
  return { status: 204 }
}

async function Api(uuid) {
  const response = await httpClient.delete(`/api/studies/${uuid}`)
  return { status: response.status }
}

async function DeleteStudyUseCase(uuid) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid)
}

export { DeleteStudyUseCase }
