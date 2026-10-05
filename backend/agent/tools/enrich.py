from tools.espn import get_match_summary, get_team_corners_avg, get_team_schedule_context
from tools.squad_context import get_team_squad_context

ODDS_H2H_KEYS = [
    "odds_local", "odds_empate", "odds_visitante", "spread", "over_under",
    "prob_local", "prob_empate", "prob_visitante",
    "h2h_ganados_local", "h2h_ganados_visitante", "h2h_empates", "h2h_detalle", "lideres",
]


def enrich_partido(p: dict, liga_code: str) -> dict:
    """Agrega odds, probabilidades, H2H, líderes y corners promedio a un partido (mutación in-place)."""
    summary = get_match_summary(liga_code, p["espn_event_id"])
    if summary:
        odds  = summary.get("odds", {})
        probs = summary.get("probabilidades", {})
        h2h   = summary.get("h2h", {})
        p.update({
            "odds_local":              odds.get("local"),
            "odds_empate":             odds.get("empate"),
            "odds_visitante":          odds.get("visitante"),
            "spread":                  odds.get("spread"),
            "over_under":              odds.get("over_under"),
            "prob_local":              probs.get("local"),
            "prob_empate":             probs.get("empate"),
            "prob_visitante":          probs.get("visitante"),
            "h2h_ganados_local":       h2h.get("ganados_local"),
            "h2h_ganados_visitante":   h2h.get("ganados_visitante"),
            "h2h_empates":             h2h.get("empates"),
            "h2h_detalle":             h2h.get("detalle"),
            "lideres":                 summary.get("lideres"),
        })
    else:
        p.update({k: None for k in ODDS_H2H_KEYS})

    tid_l = p.get("espn_team_id_local")
    tid_v = p.get("espn_team_id_visit")
    if tid_l and tid_v:
        p["corners_local"]     = get_team_corners_avg(liga_code, tid_l, n_partidos=10)
        p["corners_visitante"] = get_team_corners_avg(liga_code, tid_v, n_partidos=10)
        p["fixture_context_local"] = get_team_schedule_context(liga_code, tid_l, p.get("espn_event_id"), p.get("hora_utc"))
        p["fixture_context_visitante"] = get_team_schedule_context(liga_code, tid_v, p.get("espn_event_id"), p.get("hora_utc"))
    else:
        p["corners_local"] = p["corners_visitante"] = None
        p["fixture_context_local"] = p["fixture_context_visitante"] = None

    p["squad_context_local"] = get_team_squad_context(p.get("equipo_local", ""), p.get("fecha") or p.get("hora_utc"))
    p["squad_context_visitante"] = get_team_squad_context(p.get("equipo_visitante", ""), p.get("fecha") or p.get("hora_utc"))

    return p
