import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory() {
  await UtilHelper.wait(300)
  return { status: 200 }
}

async function Api(uuid, category, tags) {
  const response = await httpClient.put(`/api/studies/${uuid}`, { category, tags })
  return { status: response.status }
}

async function UpdateStudyUseCase(uuid, category, tags) {
  return UtilHelper.checkEnvironment() ? await InMemory() : await Api(uuid, category, tags)
}

export { UpdateStudyUseCase }
