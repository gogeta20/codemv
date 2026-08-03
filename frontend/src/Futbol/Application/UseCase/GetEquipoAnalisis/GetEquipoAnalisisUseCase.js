import httpClient from '@/core/http/HttpClient'
import { UtilHelper } from '@/core/utilities/UtilHelper'

async function Api({ liga, teamId, season }) {
  const response = await httpClient.get(`/api/futbol/ligas/${liga}/equipos/${teamId}/analisis`, {
    params: season ? { season } : {},
  })

  return response.data.data
}

async function GetEquipoAnalisisUseCase({ liga, teamId, season = null }) {
  if (UtilHelper.checkEnvironment()) {
    return {
      team: { id: String(teamId), name: 'Equipo', short_name: 'Equipo', abbreviation: 'EQP', logo: null, color: null, record_summary: null, standing_summary: null },
      league: { code: liga, name: liga, available_competitions: [] },
      season: { requested: season, used: season, label: season ? String(season) : null, fallback_applied: false, available: [] },
      coverage: { available_metrics: [], unavailable_metrics: [] },
      available_views: [],
      leaders: {
        top_scorer: null,
        top_assister: null,
        best_goals_per_match: null,
        most_yellow_cards: null,
        most_red_cards: null,
        discipline_points: null,
      },
      tables: { top_scorers: [], top_assists: [], discipline: [], performance: {} },
    }
  }

  return Api({ liga, teamId, season })
}

export { GetEquipoAnalisisUseCase }
