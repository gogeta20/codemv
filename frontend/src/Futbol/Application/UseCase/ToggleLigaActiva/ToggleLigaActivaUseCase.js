import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function Api(codigoEspn, data) {
  const response = await httpClient.patch(`/api/futbol/ligas/${codigoEspn}`, data)
  return response.data
}

async function ToggleLigaActivaUseCase(codigoEspn, data) {
  return UtilHelper.checkEnvironment() ? { ...data, codigo_espn: codigoEspn } : await Api(codigoEspn, data)
}

export { ToggleLigaActivaUseCase }
