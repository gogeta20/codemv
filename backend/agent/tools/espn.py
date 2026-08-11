import requests
from datetime import date, datetime, timezone

BASE_SITE = "https://site.api.espn.com/apis/site/v2/sports/soccer"
BASE_V2   = "https://site.api.espn.com/apis/v2/sports/soccer"

HEADERS = {"User-Agent": "curl/8.5.0"}
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

        # Score solo si ya terminó o está en juego — ESPN devuelve "0" también para partidos programados
        if estado in ("en_juego", "finalizado"):
            goles_local     = _safe_int(home.get("score"))
            goles_visitante = _safe_int(away.get("score"))
        else:
            goles_local     = None
            goles_visitante = None

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

        # Fase y grupo (World Cup / torneo con grupos)
        notes = comp.get("notes", [])
        note_headline = notes[0].get("headline", "") if notes else ""
        season_obj = ev.get("season", {})
        season_slug = season_obj.get("slug", "") if isinstance(season_obj, dict) else ""
        combined = (note_headline + " " + season_slug).lower()

        grupo = None
        fase = None
        if "group" in combined or "grupo" in combined:
            fase = "group_stage"
            # Extract single letter: "Group A — Matchday 1" → "A"
            import re as _re
            m = _re.search(r'group\s+([A-L])', note_headline, _re.IGNORECASE)
            if m:
                grupo = m.group(1).upper()
        elif "round of 16" in combined or "octavo" in combined:
            fase = "round_of_16"
        elif "quarter" in combined or "cuarto" in combined:
            fase = "quarter_final"
        elif "semi" in combined:
            fase = "semi_final"
        elif "final" in combined:
            fase = "final"

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
            "grupo":               grupo,
            "fase":                fase,
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
    ml_local = ml_visit = ml_draw = None
    if odds_list:
        o = odds_list[0]
        home_odds  = o.get("homeTeamOdds", {})
        away_odds  = o.get("awayTeamOdds", {})
        draw_odds  = o.get("drawOdds", {}) if isinstance(o.get("drawOdds"), dict) else {}
        ml_local = _safe_float(home_odds.get("moneyLine"))
        ml_visit = _safe_float(away_odds.get("moneyLine"))
        ml_draw  = _safe_float(draw_odds.get("moneyLine"))
        resultado["odds"] = {
            "local":      _ml_to_decimal(ml_local),
            "visitante":  _ml_to_decimal(ml_visit),
            "empate":     _ml_to_decimal(ml_draw),
            "spread":     _safe_float(o.get("spread")),
            "over_under": _safe_float(o.get("overUnder")),
        }

    # --- Pickcenter probabilities ---
    pc = data.get("pickcenter", [])
    prob_local = prob_visit = prob_empate = None
    if pc:
        p = pc[0]
        home_odds_pc = p.get("homeTeamOdds", {})
        away_odds_pc = p.get("awayTeamOdds", {})
        # Some ESPN endpoints provide winPercentage; others only moneyLine
        prob_local = _safe_float(home_odds_pc.get("winPercentage") or p.get("homeWinPercentage"))
        prob_visit = _safe_float(away_odds_pc.get("winPercentage") or p.get("awayWinPercentage"))
        prob_empate = _safe_float(p.get("drawPercentage"))

    # Fallback: derive normalized probabilities from moneyLines
    if prob_local is None and ml_local is not None and ml_visit is not None:
        raw_l = _ml_implied_prob(ml_local)
        raw_v = _ml_implied_prob(ml_visit)
        raw_e = _ml_implied_prob(ml_draw) if ml_draw is not None else 0.0
        total = raw_l + raw_v + raw_e or 1.0
        prob_local  = round(raw_l / total, 3)
        prob_visit  = round(raw_v / total, 3)
        prob_empate = round(raw_e / total, 3) if raw_e else None

    resultado["probabilidades"] = {
        "local":     prob_local,
        "visitante": prob_visit,
        "empate":    prob_empate,
    }

    # --- H2H ---
    # ESPN expone esto bajo "seasonseries" (type: "head-to-head"). La clave "headToHeadGames"
    # que se usaba antes ya no existe en la respuesta actual del endpoint /summary.
    home_id = away_id = None
    for comp in data.get("header", {}).get("competitions", [{}]):
        for c in comp.get("competitors", []):
            if c.get("homeAway") == "home":
                home_id = str(c.get("team", {}).get("id", ""))
            elif c.get("homeAway") == "away":
                away_id = str(c.get("team", {}).get("id", ""))

    h2h_series = next((s for s in data.get("seasonseries", []) if s.get("type") == "head-to-head"), None)
    if h2h_series and h2h_series.get("events"):
        detalle = []
        wins_home = wins_away = empates = 0

        for ev in h2h_series["events"][:5]:
            competitors = ev.get("competitors", [])
            hist_home = next((c for c in competitors if c.get("homeAway") == "home"), None)
            hist_away = next((c for c in competitors if c.get("homeAway") == "away"), None)
            if not hist_home or not hist_away:
                continue

            local_name = hist_home.get("team", {}).get("displayName", "")
            visit_name = hist_away.get("team", {}).get("displayName", "")
            score_str  = f"{hist_home.get('score', '?')}-{hist_away.get('score', '?')}"
            game_date  = (ev.get("date") or "")[:10]

            home_won = bool(hist_home.get("winner"))
            away_won = bool(hist_away.get("winner"))
            hist_home_id = str(hist_home.get("team", {}).get("id", ""))
            hist_away_id = str(hist_away.get("team", {}).get("id", ""))

            # ¿Ganó ese partido histórico el equipo que HOY juega de local? (por id, no por lado histórico)
            if hist_home_id == home_id:
                today_home_won = home_won
            elif hist_away_id == home_id:
                today_home_won = away_won
            else:
                today_home_won = None

            if not home_won and not away_won:
                result_str = "D"
                empates += 1
            elif today_home_won:
                result_str = "W"
                wins_home += 1
            else:
                result_str = "L"
                wins_away += 1

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


def get_world_cup_group_standings(liga: str = "fifa.world") -> dict:
    """Returns {group_letter: {team_name: {pos, pts, pj, pg, pe, pp, gf, gc}}}."""
    url = f"{BASE_V2}/{liga}/standings"
    try:
        res = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
        res.raise_for_status()
        data = res.json()
    except Exception as e:
        print(f"[espn] world cup standings ERROR: {e}")
        return {}

    groups = {}
    for child in data.get("children", []):
        group_name = child.get("name", "")          # "Group A", "Group B"...
        import re as _re
        m = _re.search(r'group\s+([A-L])', group_name, _re.IGNORECASE)
        letter = m.group(1).upper() if m else group_name

        entries = child.get("standings", {}).get("entries", [])
        grupo = {}
        for i, entry in enumerate(entries):
            team  = entry.get("team", {})
            nombre = team.get("displayName", "")
            stats  = {s["name"]: s.get("value") for s in entry.get("stats", [])}
            grupo[nombre] = {
                "pos": i + 1,
                "pts": _safe_int(stats.get("points")),
                "pj":  _safe_int(stats.get("gamesPlayed")),
                "pg":  _safe_int(stats.get("wins")),
                "pe":  _safe_int(stats.get("ties")),
                "pp":  _safe_int(stats.get("losses")),
                "gf":  _safe_int(stats.get("pointsFor")),
                "gc":  _safe_int(stats.get("pointsAgainst")),
            }
        if letter:
            groups[letter] = grupo

    return groups


def get_fifa_rankings(liga: str = "fifa.world") -> dict:
    """Returns {team_display_name: rank_number} from ESPN FIFA rankings."""
    url = f"{BASE_SITE}/{liga}/rankings"
    try:
        res = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
        res.raise_for_status()
        data = res.json()
    except Exception as e:
        print(f"[espn] FIFA rankings ERROR: {e}")
        return {}

    rankings = {}
    entries = data.get("rankings", data.get("athletes", []))
    for entry in entries:
        team = entry.get("team", entry.get("athlete", {}))
        name = team.get("displayName", "")
        rank = entry.get("current", entry.get("rank", 0))
        if name and rank:
            try:
                rankings[name] = int(rank)
            except (ValueError, TypeError):
                pass

    return rankings


def _ml_implied_prob(ml: float) -> float:
    """American moneyLine → implied probability (0-1, with vig)."""
    if ml is None:
        return 0.0
    if ml < 0:
        return abs(ml) / (abs(ml) + 100)
    return 100 / (ml + 100)


def _ml_to_decimal(ml: float) -> float | None:
    """American moneyLine → European decimal odds."""
    if ml is None:
        return None
    if ml < 0:
        return round((100 / abs(ml)) + 1, 2)
    return round((ml / 100) + 1, 2)


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
