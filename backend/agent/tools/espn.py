import requests
from datetime import date, datetime, timezone

BASE_SITE = "https://site.api.espn.com/apis/site/v2/sports/soccer"
BASE_V2   = "https://site.api.espn.com/apis/v2/sports/soccer"

HEADERS = {"User-Agent": "Mozilla/5.0"}
TIMEOUT = 15


def get_scoreboard(liga: str, fecha: date = None) -> list[dict]:
    """Partidos de una liga para una fecha (default: hoy)."""
    url    = f"{BASE_SITE}/{liga}/scoreboard"
    params = {}
    if fecha:
        params["dates"] = fecha.strftime("%Y%m%d")

    try:
        res = requests.get(url, params=params, headers=HEADERS, timeout=TIMEOUT)
        res.raise_for_status()
        data   = res.json()
        events = data.get("events", [])
    except Exception as e:
        print(f"[espn] scoreboard {liga} ERROR: {e}")
        return []

    partidos = []
    for ev in events:
        comp = ev.get("competitions", [{}])[0]
        competitors = comp.get("competitors", [])

        home = next((c for c in competitors if c.get("homeAway") == "home"), None)
        away = next((c for c in competitors if c.get("homeAway") == "away"), None)
        if not home or not away:
            continue

        status_type = ev.get("status", {}).get("type", {})
        estado = _map_estado(status_type.get("name", ""))

        # Score si ya terminó o está en juego
        goles_local     = _safe_int(home.get("score"))
        goles_visitante = _safe_int(away.get("score"))

        # Odds del scoreboard (básicas — el summary tiene más detalle)
        raw_odds = comp.get("odds", [{}])
        odds_sb  = raw_odds[0] if raw_odds else {}

        venue    = comp.get("venue", {})
        raw_date = ev.get("date", "")
        hora_utc = None
        if raw_date:
            try:
                hora_utc = datetime.fromisoformat(raw_date.replace("Z", "+00:00"))
            except ValueError:
                pass

        partidos.append({
            "espn_event_id":       str(ev.get("id", "")),
            "nombre":              ev.get("name", ""),
            "fecha":               hora_utc.date() if hora_utc else fecha or date.today(),
            "hora_utc":            hora_utc,
            "equipo_local":        home.get("team", {}).get("displayName", ""),
            "equipo_visitante":    away.get("team", {}).get("displayName", ""),
            "espn_team_id_local":  str(home.get("team", {}).get("id", "")),
            "espn_team_id_visit":  str(away.get("team", {}).get("id", "")),
            "estadio":             venue.get("fullName"),
            "estado":              estado,
            "goles_local":         goles_local,
            "goles_visitante":     goles_visitante,
            "odds_sb":             odds_sb,
        })

    return partidos


def get_standings(liga: str) -> dict:
    """Tabla de posiciones: {nombre_equipo: {pos, pts, pj, ...}}"""
    url = f"{BASE_V2}/{liga}/standings"
    try:
        res = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
        res.raise_for_status()
        data = res.json()
    except Exception as e:
        print(f"[espn] standings {liga} ERROR: {e}")
        return {}

    tabla = {}
    groups = data.get("standings", {}).get("entries", [])

    # ESPN a veces anida en groups
    if not groups:
        for g in data.get("children", []):
            groups += g.get("standings", {}).get("entries", [])

    for i, entry in enumerate(groups):
        team = entry.get("team", {})
        nombre = team.get("displayName", "")
        stats  = {s["name"]: s.get("value") for s in entry.get("stats", [])}

        tabla[nombre] = {
            "pos": i + 1,
            "pts": _safe_int(stats.get("points")),
            "pj":  _safe_int(stats.get("gamesPlayed")),
            "pg":  _safe_int(stats.get("wins")),
            "pe":  _safe_int(stats.get("ties")),
            "pp":  _safe_int(stats.get("losses")),
            "gf":  _safe_int(stats.get("pointsFor")),
            "gc":  _safe_int(stats.get("pointsAgainst")),
            "forma": stats.get("form"),
        }

    return tabla


def get_match_summary(liga: str, event_id: str) -> dict:
    """Odds detalladas, pickcenter y H2H de un partido específico."""
    url = f"{BASE_SITE}/{liga}/summary"
    try:
        res = requests.get(url, params={"event": event_id}, headers=HEADERS, timeout=TIMEOUT)
        res.raise_for_status()
        data = res.json()
    except Exception as e:
        print(f"[espn] summary {liga}/{event_id} ERROR: {e}")
        return {}

    resultado = {}

    # --- Odds ---
    odds_list = data.get("odds", [])
    if odds_list:
        o = odds_list[0]
        home_odds  = o.get("homeTeamOdds", {})
        away_odds  = o.get("awayTeamOdds", {})
        resultado["odds"] = {
            "local":     _safe_float(home_odds.get("moneyLine") or home_odds.get("current", {}).get("moneyLine")),
            "visitante": _safe_float(away_odds.get("moneyLine") or away_odds.get("current", {}).get("moneyLine")),
            "empate":    _safe_float(o.get("drawOdds", {}).get("moneyLine") if isinstance(o.get("drawOdds"), dict) else o.get("drawOdds")),
            "spread":    _safe_float(o.get("spread")),
            "over_under":_safe_float(o.get("overUnder")),
        }

    # --- Pickcenter ---
    pc = data.get("pickcenter", [])
    if pc:
        p = pc[0]
        resultado["probabilidades"] = {
            "local":     _safe_float(p.get("homeWinPercentage")),
            "visitante": _safe_float(p.get("awayWinPercentage")),
            "empate":    _safe_float(p.get("drawPercentage")),
        }

    # --- H2H ---
    # ESPN returns [{team, events[{gameDate,score,homeTeamId,awayTeamId,gameResult,...}]}]
    # gameResult is from the perspective of `team` (W/L/D/T)
    h2h_entries = data.get("headToHeadGames", [])
    if h2h_entries:
        # Use the first team entry (home team of current match)
        entry  = h2h_entries[0]
        events = entry.get("events", [])

        # Resolve home team id from header competitors
        home_id = away_id = None
        for comp in data.get("header", {}).get("competitions", [{}]):
            for c in comp.get("competitors", []):
                if c.get("homeAway") == "home":
                    home_id = str(c.get("team", {}).get("id", ""))
                elif c.get("homeAway") == "away":
                    away_id = str(c.get("team", {}).get("id", ""))

        ref_team_id = str(entry.get("team", {}).get("id", ""))
        # Is the ref team today's home team? (determines how to map W/L to wins_home/wins_away)
        ref_is_current_home = ref_team_id == home_id

        detalle = []
        wins_home = wins_away = empates = 0

        for ev in events[:5]:
            g_home_id   = str(ev.get("homeTeamId", ""))
            home_score  = ev.get("homeTeamScore", "?")
            away_score  = ev.get("awayTeamScore", "?")
            game_result = ev.get("gameResult", "")     # W/L/D/T from ref_team_id perspective
            game_date   = (ev.get("gameDate") or "")[:10]
            opponent    = ev.get("opponent", {})

            # Build local/visitante display names using who was home in the historical match
            ref_name = entry.get("team", {}).get("displayName", "")
            opp_name = opponent.get("displayName", "")
            if g_home_id == ref_team_id:
                local_name, visit_name = ref_name, opp_name
            else:
                local_name, visit_name = opp_name, ref_name

            # Score is always homeTeamScore-awayTeamScore (matches local-visitante display)
            score_str = f"{home_score}-{away_score}"

            # Map game_result (from ref's perspective) to wins_home / wins_away for today's match
            if game_result in ("T", "D"):
                result_str = "D"
                empates += 1
            elif game_result == "W":
                if ref_is_current_home:
                    result_str = "W"
                    wins_home += 1
                else:
                    result_str = "L"
                    wins_away += 1
            else:
                if ref_is_current_home:
                    result_str = "L"
                    wins_away += 1
                else:
                    result_str = "W"
                    wins_home += 1

            detalle.append({
                "fecha":     game_date,
                "local":     local_name,
                "visitante": visit_name,
                "resultado": score_str,
                "ganador":   result_str,
            })

        resultado["h2h"] = {
            "ganados_local":     wins_home,
            "ganados_visitante": wins_away,
            "empates":           empates,
            "detalle":           detalle,
        }

    # --- Líderes por equipo ---
    leaders = data.get("leaders", [])
    if leaders:
        # Resolve home/away names from header
        home_name = away_name = None
        for comp in data.get("header", {}).get("competitions", [{}]):
            for c in comp.get("competitors", []):
                if c.get("homeAway") == "home":
                    home_name = c.get("team", {}).get("displayName")
                elif c.get("homeAway") == "away":
                    away_name = c.get("team", {}).get("displayName")
        resultado["lideres"] = _extract_leaders_by_team(leaders, home_name, away_name)

    return resultado


def _map_estado(espn_status: str) -> str:
    mapping = {
        "STATUS_SCHEDULED":   "programado",
        "STATUS_IN_PROGRESS": "en_juego",
        "STATUS_FINAL":       "finalizado",
        "STATUS_HALFTIME":    "en_juego",
        "STATUS_POSTPONED":   "postponado",
        "STATUS_CANCELED":    "cancelado",
    }
    return mapping.get(espn_status, "programado")


def _extract_leaders_by_team(leaders: list, home_name: str | None, away_name: str | None) -> dict:
    """Returns {local: {...}, visitante: {...}} with top players per category."""
    sides = {}
    for team_data in leaders:
        if not isinstance(team_data, dict):
            continue
        team      = team_data.get("team", {})
        team_name = team.get("displayName", "")
        if home_name and team_name == home_name:
            key = "local"
        elif away_name and team_name == away_name:
            key = "visitante"
        else:
            continue

        cats = {}
        for cat in team_data.get("leaders", []):
            cat_key = cat.get("name", "")
            top = []
            for l in cat.get("leaders", [])[:3]:
                ath = l.get("athlete", {})
                if not ath.get("displayName"):
                    continue
                top.append({
                    "jugador": ath.get("displayName", ""),
                    "valor":   l.get("displayValue", ""),
                })
            if top:
                cats[cat_key] = top
        sides[key] = {
            "equipo": team_name,
            "logo":   team.get("logo", ""),
            "stats":  cats,
        }
    return sides


def get_team_corners_avg(liga: str, team_id: str, n_partidos: int = 10) -> dict | None:
    """Promedio de corners del equipo en los últimos N partidos finalizados de la temporada."""
    url = f"{BASE_SITE}/{liga}/teams/{team_id}/schedule"
    try:
        res = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
        res.raise_for_status()
        events = res.json().get("events", [])
    except Exception as e:
        print(f"[espn] schedule {liga}/{team_id} ERROR: {e}")
        return None

    finalizados = []
    for ev in events:
        comp = ev.get("competitions", [{}])[0]
        status = comp.get("status", {}).get("type", {}).get("name", "")
        if "FINAL" in status or "FULL" in status:
            finalizados.append(ev["id"])

    if not finalizados:
        return None

    corners_list = []
    for eid in finalizados[:n_partidos]:
        try:
            r = requests.get(
                f"{BASE_SITE}/{liga}/summary",
                params={"event": eid}, headers=HEADERS, timeout=TIMEOUT
            )
            r.raise_for_status()
            bs = r.json().get("boxscore", {})
            for t in bs.get("teams", []):
                if str(t.get("team", {}).get("id")) == str(team_id):
                    stats = {s["name"]: s.get("displayValue") for s in t.get("statistics", [])}
                    c = _safe_int(stats.get("wonCorners"))
                    if c is not None:
                        corners_list.append(c)
        except Exception:
            continue

    if not corners_list:
        return None

    return {
        "promedio":   round(sum(corners_list) / len(corners_list), 1),
        "total":      sum(corners_list),
        "partidos":   len(corners_list),
        "detalle":    corners_list,
    }


def get_team_id_from_scoreboard(partido: dict, side: str) -> str | None:
    """Extrae el ESPN team ID del partido ya enriquecido (side: 'local' o 'visitante')."""
    return partido.get(f"espn_team_id_{side}")


def _safe_int(v) -> int | None:
    try:
        return int(v) if v is not None else None
    except (ValueError, TypeError):
        return None


def _safe_float(v) -> float | None:
    try:
        return float(v) if v is not None else None
    except (ValueError, TypeError):
        return None
