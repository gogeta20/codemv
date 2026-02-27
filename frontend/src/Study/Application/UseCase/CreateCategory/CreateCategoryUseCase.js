import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory(slug, name) {
  await UtilHelper.wait(400)
  return { slug, name }
}

async function Api(slug, name) {
  const response = await httpClient.post('/api/categories', { slug, name })
  return response.data.data
}

async function CreateCategoryUseCase(slug, name) {
  return UtilHelper.checkEnvironment() ? await InMemory(slug, name) : await Api(slug, name)
}

export { CreateCategoryUseCase }
