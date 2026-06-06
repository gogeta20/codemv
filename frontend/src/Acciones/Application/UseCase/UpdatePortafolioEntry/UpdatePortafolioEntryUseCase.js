import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function InMemory(uuid, payload) {
  await UtilHelper.wait(300)
  return { uuid, ...payload }
}

// Usado para editar el portafolio contenedor (nombre/descripcion)
// y también para editar una entrada (status/notas) via portafolio/:uuid/acciones/:entryUuid
async function Api(uuid, payload) {
  const response = await httpClient.patch(`/api/portafolio/${uuid}`, payload)
  return response.data
}

async function UpdatePortafolioEntryUseCase(uuid, payload) {
  return UtilHelper.checkEnvironment() ? await InMemory(uuid, payload) : await Api(uuid, payload)
}

export { UpdatePortafolioEntryUseCase }
