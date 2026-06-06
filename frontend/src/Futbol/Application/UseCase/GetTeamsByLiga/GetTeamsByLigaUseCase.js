import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function Api(ligaCode) {
  const response = await httpClient.get('/api/futbol/teams', { params: { liga: ligaCode } })
  return response.data.data
}

async function GetTeamsByLigaUseCase(ligaCode) {
  return UtilHelper.checkEnvironment() ? [] : await Api(ligaCode)
}

export { GetTeamsByLigaUseCase }
