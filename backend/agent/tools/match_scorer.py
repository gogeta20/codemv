"""
Algoritmo de puntuación de analizabilidad de un partido.
Cuanto mayor el score, más fácil y claro resulta el análisis.
"""
from datetime import date


def _implied_prob(moneyline: float) -> float:
    """Convierte moneyline americano a probabilidad implícita (0-1)."""
    if moneyline < 0:
        return abs(moneyline) / (abs(moneyline) + 100)
    else:
        return 100 / (moneyline + 100)


def _is_american(odds_l: float, odds_v: float) -> bool:
    """True si las odds están en formato moneyline americano."""
    return abs(odds_l) > 10 or abs(odds_v) > 10


def _fav_implied_prob(odds_l: float, odds_v: float) -> float:
    """Probabilidad implícita del favorito (el más alto de los dos)."""
    if _is_american(odds_l, odds_v):
        prob_l = _implied_prob(odds_l)
        prob_v = _implied_prob(odds_v)
    else:
        # Decimal europeo: prob = 1 / odds
        prob_l = 1 / odds_l if odds_l > 0 else 0
        prob_v = 1 / odds_v if odds_v > 0 else 0
    return max(prob_l, prob_v)


def _get_zona(pos: int, total: int) -> str:
    """Clasifica la posición en zona motivacional usando % relativo de la tabla."""
    pct = pos / total
    if pct <= 0.20:
        return "champions"
    if pct <= 0.33:
        return "europa"
    if pct >= 0.85:
        return "descenso"
    if pct >= 0.70:
        return "burbuja_descenso"
    if pct <= 0.45:
        return "burbuja_arriba"
    return "media_tabla"


def _empates_recientes_h2h(h2h_detalle: list, max_years: int = 3) -> int:
    """Cuenta empates en H2H de los últimos N años."""
    cutoff = date.today().year - max_years
    return sum(
        1 for ev in (h2h_detalle or [])
        if int((ev.get("fecha") or "0000")[:4]) >= cutoff and ev.get("ganador") == "D"
    )


def _pick_favorite_side(partido: dict) -> str | None:
    prob_l = partido.get("prob_local")
    prob_v = partido.get("prob_visitante")
    if prob_l is not None and prob_v is not None and prob_l != prob_v:
        return "local" if prob_l > prob_v else "visitante"

    odds_l = partido.get("odds_local")
    odds_v = partido.get("odds_visitante")
    if odds_l is not None and odds_v is not None and odds_l != odds_v:
        return "local" if odds_l < odds_v else "visitante"

    pos_l = partido.get("pos_local")
    pos_v = partido.get("pos_visitante")
    if pos_l is not None and pos_v is not None and pos_l != pos_v:
        return "local" if pos_l < pos_v else "visitante"

    return None


def _squad_context_penalty(partido: dict) -> dict | None:
    favorito = _pick_favorite_side(partido)
    if favorito is None:
        return None

    ctx = partido.get(f"squad_context_{favorito}") or {}
    if not ctx:
        return None

    points = int(ctx.get("points") or 0)
    if points == 0:
        return None

    return {
        "favorito": favorito,
        "team": ctx.get("team"),
        "kind": ctx.get("kind"),
        "source": ctx.get("source"),
        "active_until": ctx.get("active_until"),
        "puntos": points,
        "razones": [ctx.get("reason")],
    }


def _fatigue_penalty(partido: dict) -> dict | None:
    favorito = _pick_favorite_side(partido)
    if favorito is None:
        return None

    rival = "visitante" if favorito == "local" else "local"
    ctx_fav = partido.get(f"fixture_context_{favorito}") or {}
    ctx_riv = partido.get(f"fixture_context_{rival}") or {}

    points = 0
    reasons = []

    prev_fav = ctx_fav.get("prev") or {}
    next_fav = ctx_fav.get("next") or {}
    days_prev_fav = ctx_fav.get("days_since_prev")
    days_next_fav = ctx_fav.get("days_until_next")
    days_prev_riv = ctx_riv.get("days_since_prev")

    if days_prev_fav is not None and days_prev_fav <= 3.0:
        points -= 1
        reasons.append(f"favorito con solo {days_prev_fav} días desde su último partido")

    if prev_fav.get("es_europeo") and days_prev_fav is not None and days_prev_fav <= 4.0:
        points -= 1
        reasons.append(
            f"venía de {prev_fav.get('competition') or 'competición europea'} hace {days_prev_fav} días"
        )

    if next_fav.get("es_europeo") and days_next_fav is not None and days_next_fav <= 4.0:
        points -= 1
        reasons.append(
            f"tenía {next_fav.get('competition') or 'Europa'} en {days_next_fav} días, posible rotación"
        )

    if days_prev_fav is not None and days_prev_riv is not None and days_prev_fav + 2 <= days_prev_riv:
        points -= 1
        reasons.append(
            f"descanso desigual: favorito {days_prev_fav} días vs rival {days_prev_riv}"
        )

    if not reasons:
        return None

    return {
        "favorito": favorito,
        "puntos": max(points, -3),
        "razones": reasons,
        "prev_favorito": prev_fav or None,
        "next_favorito": next_fav or None,
    }


def score_match(partido: dict, total_equipos: int = 20, ignorar_temporada: bool = False) -> tuple[int, dict]:
    """
    Recibe el dict enriquecido de un partido (con standings, odds, h2h).
    Devuelve (score_total, detalle_breakdown).
    Puntuación: positiva = partido fácil de analizar (favorito claro).
    Penalizaciones: partidos equilibrados, temporada muy joven.
    """
    score   = 0
    detalle = {}

    pos_l = partido.get("pos_local")
    pos_v = partido.get("pos_visitante")
    pts_l = partido.get("pts_local")
    pts_v = partido.get("pts_visitante")
    pj_l  = partido.get("pj_local")
    pj_v  = partido.get("pj_visitante")

    # --- Penalización: temporada muy joven (tabla no fiable) ---
    pj_min = min(pj_l or 99, pj_v or 99)
    if not ignorar_temporada:
        if pj_min < 5:
            score -= 3
            detalle["temporada_joven"] = {"pj_min": pj_min, "puntos": -3, "desc": "Menos de 5 PJ — tabla no fiable"}
        elif pj_min < 8:
            score -= 1
            detalle["temporada_joven"] = {"pj_min": pj_min, "puntos": -1, "desc": "Menos de 8 PJ — tabla con margen"}

    # --- Diferencia de posición en tabla ---
    if pos_l is not None and pos_v is not None:
        gap = abs(pos_l - pos_v)
        if gap >= 12:
            puntos = 4
        elif gap >= 8:
            puntos = 3
        elif gap >= 5:
            puntos = 2
        elif gap >= 3:
            puntos = 1
        else:
            puntos = 0
        score += puntos
        detalle["gap_tabla"] = {"gap": gap, "puntos": puntos}

    # --- Top 3 vs Bottom 3 (partido extremo) ---
    if pos_l is not None and pos_v is not None:
        top3_local    = pos_l <= 3
        bottom3_visit = pos_v >= total_equipos - 2
        top3_visit    = pos_v <= 3
        bottom3_local = pos_l >= total_equipos - 2

        if (top3_local and bottom3_visit) or (top3_visit and bottom3_local):
            score += 3
            detalle["extremo_tabla"] = {"puntos": 3, "desc": "Top3 vs Bottom3"}

    # --- Diferencia de puntos (brecha real en la tabla) ---
    if pts_l is not None and pts_v is not None:
        pts_gap = abs(pts_l - pts_v)
        if pts_gap >= 15:
            puntos = 2
        elif pts_gap >= 8:
            puntos = 1
        else:
            puntos = 0
        if puntos:
            score += puntos
            detalle["brecha_puntos"] = {"gap_pts": pts_gap, "puntos": puntos}

    # --- Odds: probabilidad implícita del favorito ---
    odds_l = partido.get("odds_local")
    odds_v = partido.get("odds_visitante")
    if odds_l is not None and odds_v is not None:
        fav_prob = _fav_implied_prob(odds_l, odds_v)

        if fav_prob >= 0.88:
            puntos = 4    # tipo Bolívar -1800 (~95%)
        elif fav_prob >= 0.75:
            puntos = 3
        elif fav_prob >= 0.65:
            puntos = 2
        elif fav_prob >= 0.58:
            puntos = 1
        elif fav_prob < 0.54:
            puntos = -2   # partido casi de moneda
        else:
            puntos = 0

        score += puntos
        detalle["odds_claridad"] = {
            "odds_local": odds_l,
            "odds_visitante": odds_v,
            "fav_prob_pct": round(fav_prob * 100, 1),
            "puntos": puntos,
        }

    # --- Probabilidad del pickcenter (más fiable que las odds) ---
    prob_l = partido.get("prob_local")
    prob_v = partido.get("prob_visitante")
    prob_e = partido.get("prob_empate")
    if prob_l is not None and prob_v is not None:
        max_prob = max(prob_l, prob_v)

        if max_prob >= 0.80:
            puntos = 3
        elif max_prob >= 0.70:
            puntos = 2
        elif max_prob >= 0.60:
            puntos = 1
        elif max_prob < 0.45:
            puntos = -2   # nadie destaca
        else:
            puntos = 0

        score += puntos
        detalle["prob_pickcenter"] = {"max_prob": round(max_prob * 100, 1), "puntos": puntos}

        # Penalización extra: empate muy probable
        if prob_e is not None and prob_e >= 0.35:
            score -= 2
            detalle["empate_probable"] = {"prob_empate_pct": round(prob_e * 100, 1), "puntos": -2}
        elif prob_e is not None and prob_e >= 0.28:
            score -= 1
            detalle["empate_probable"] = {"prob_empate_pct": round(prob_e * 100, 1), "puntos": -1}

    # --- Dominio H2H ---
    h2h_l     = partido.get("h2h_ganados_local", 0) or 0
    h2h_v     = partido.get("h2h_ganados_visitante", 0) or 0
    h2h_e     = partido.get("h2h_empates", 0) or 0
    total_h2h = h2h_l + h2h_v + h2h_e

    if total_h2h >= 3:
        max_wins = max(h2h_l, h2h_v)
        dominio  = max_wins / total_h2h

        if dominio >= 0.80:
            puntos = 2
        elif dominio >= 0.60:
            puntos = 1
        else:
            puntos = 0

        if puntos:
            score += puntos
            detalle["h2h_dominio"] = {
                "ganados_local": h2h_l, "ganados_visitante": h2h_v, "empates": h2h_e,
                "dominio_pct": round(dominio * 100, 1), "puntos": puntos,
            }

        # Penalización: historial con muchos empates
        if total_h2h >= 4 and h2h_e / total_h2h >= 0.50:
            score -= 1
            detalle["h2h_empates_alto"] = {"empates_pct": round(h2h_e / total_h2h * 100, 1), "puntos": -1}

    # --- Forma reciente ---
    forma_l = partido.get("forma_local", "") or ""
    forma_v = partido.get("forma_visitante", "") or ""
    if forma_l and forma_v:
        wins_l    = forma_l.upper().count("W")
        wins_v    = forma_v.upper().count("W")
        forma_gap = abs(wins_l - wins_v)

        if forma_gap >= 4:
            puntos = 2
        elif forma_gap >= 3:
            puntos = 1
        else:
            puntos = 0

        if puntos:
            score += puntos
            detalle["forma_gap"] = {
                "forma_local": forma_l, "forma_visitante": forma_v,
                "gap_wins": forma_gap, "puntos": puntos,
            }

        # Bonus: racha perfecta (WWWWW o LLLLL del otro)
        if "WWWWW" in forma_l.upper() or "WWWWW" in forma_v.upper():
            score += 2
            detalle["racha_perfecta"] = {"puntos": 2, "desc": "Un equipo con 5W consecutivas"}

    # --- El favorito juega de local ---
    if pos_l is not None and pos_v is not None and pos_l < pos_v:
        score += 1
        detalle["local_favorito"] = {"puntos": 1, "desc": f"Local pos {pos_l} vs visitante pos {pos_v}"}

    # --- Zonas motivacionales ---
    if pos_l is not None and pos_v is not None:
        zona_l = _get_zona(pos_l, total_equipos)
        zona_v = _get_zona(pos_v, total_equipos)
        detalle["zonas"] = {"local": zona_l, "visitante": zona_v}

        # Bonus: ambos equipos con algo importante en juego
        criticas = {"champions", "europa", "descenso", "burbuja_descenso"}
        if zona_l in criticas and zona_v in criticas:
            score += 1
            detalle["zonas"]["puntos"] = 1
            detalle["zonas"]["desc"] = "Ambos con motivación alta"

    # --- Contexto de plantilla / salidas recientes ---
    plantilla = _squad_context_penalty(partido)
    if plantilla:
        score += plantilla["puntos"]
        detalle["contexto_plantilla"] = plantilla

    # --- Carga de calendario / Europa ---
    fatiga = _fatigue_penalty(partido)
    if fatiga:
        score += fatiga["puntos"]
        detalle["fatiga_calendario"] = fatiga

    # --- Trampa de empate ---
    # Dos señales independientes que se combinan:
    # 1) H2H reciente con ≥2 empates (aplica siempre, independiente de quién es local)
    # 2) Favorito visitante + gap grande + local desesperado o sin motivación
    if pos_l is not None and pos_v is not None:
        gap                  = abs(pos_l - pos_v)
        favorito_es_visitante = pos_v < pos_l
        zona_local           = _get_zona(pos_l, total_equipos)
        h2h_detalle_raw      = partido.get("h2h_detalle") or []
        emp_recientes        = _empates_recientes_h2h(h2h_detalle_raw, max_years=3)

        razones = []
        nivel   = None

        if emp_recientes >= 2:
            nivel = "alto"
            razones.append(f"{emp_recientes} empates H2H en últimos 3 años")

        if favorito_es_visitante and gap >= 8:
            if zona_local in ("descenso", "burbuja_descenso"):
                nivel = nivel or "medio"
                razones.append(f"Local en zona {zona_local} — equipo desesperado en casa")
            elif zona_local == "media_tabla":
                nivel = nivel or "bajo"
                razones.append("Local en media tabla — sin presión, partido abierto")

        if nivel and razones:
            penalizacion = {"alto": -2, "medio": -1, "bajo": 0}[nivel]
            score += penalizacion
            detalle["trampa_empate"] = {
                "nivel":   nivel,
                "razones": razones,
                "puntos":  penalizacion,
            }

    return score, detalle


def rank_partidos(partidos: list[dict], top_n: int = 8, ignorar_temporada: bool = False) -> list[dict]:
    """Puntúa, ordena y devuelve los top_n con score y detalle añadidos."""
    import copy
    ranked = copy.deepcopy(partidos)
    for p in ranked:
        total_equipos = p.get("total_equipos", 20)
        score, detalle = score_match(p, total_equipos, ignorar_temporada=ignorar_temporada)
        p["score_analisis"] = score
        p["score_detalle"]  = detalle

    ranked.sort(key=lambda x: x["score_analisis"], reverse=True)
    return ranked[:top_n]


def _total_goles_h2h(resultado: str) -> int:
    """Total de goles de un resultado tipo '1-0', '2-2'. Devuelve 99 si no parseable."""
    try:
        a, b = resultado.split("-")
        return int(a) + int(b)
    except Exception:
        return 99


def score_under(partido: dict) -> tuple[int, dict]:
    """
    Probabilidad de que el partido tenga pocos goles (enfoque under).
    Positivo = más señales de partido cerrado / bajo marcador.
    Útil para buscar partidos donde es fácil predecir que no habrá 5+ goles.
    """
    score   = 0
    detalle = {}

    pj_l = partido.get("pj_local")  or 1
    pj_v = partido.get("pj_visitante") or 1
    gf_l = partido.get("gf_local")
    gc_l = partido.get("gc_local")
    gf_v = partido.get("gf_visitante")
    gc_v = partido.get("gc_visitante")

    # --- xG estimado: (ataque_local + defensa_visit) / 2 + (ataque_visit + defensa_local) / 2 ---
    if all(v is not None for v in [gf_l, gc_l, gf_v, gc_v]):
        xg_l     = (gf_l / pj_l + gc_v / pj_v) / 2
        xg_v     = (gf_v / pj_v + gc_l / pj_l) / 2
        total_xg = xg_l + xg_v

        if total_xg < 1.5:
            puntos = 4
        elif total_xg < 2.0:
            puntos = 3
        elif total_xg < 2.5:
            puntos = 2
        elif total_xg < 3.0:
            puntos = 1
        elif total_xg >= 3.5:
            puntos = -2
        else:
            puntos = 0

        score += puntos
        detalle["xg_estimado"] = {
            "total_xg":  round(total_xg, 2),
            "local_xg":  round(xg_l, 2),
            "visit_xg":  round(xg_v, 2),
            "puntos":    puntos,
        }

    # --- Over/Under del bookmaker ---
    ou = partido.get("over_under")
    if ou is not None:
        if ou <= 2.0:
            puntos = 4
        elif ou <= 2.5:
            puntos = 3
        elif ou <= 3.0:
            puntos = 1
        elif ou >= 3.5:
            puntos = -1
        else:
            puntos = 0
        score += puntos
        detalle["over_under"] = {"linea": ou, "puntos": puntos}

    # --- Probabilidad de empate: alta = partido cerrado = pocos goles ---
    prob_e = partido.get("prob_empate")
    if prob_e is not None:
        if prob_e >= 0.35:
            puntos = 2
        elif prob_e >= 0.28:
            puntos = 1
        else:
            puntos = 0
        if puntos:
            score += puntos
            detalle["empate_probable"] = {"prob_pct": round(prob_e * 100, 1), "puntos": puntos}

    # --- H2H: historial de partidos con menos de 3 goles totales ---
    h2h_detalle = partido.get("h2h_detalle") or []
    if len(h2h_detalle) >= 3:
        bajo = sum(1 for ev in h2h_detalle if _total_goles_h2h(ev.get("resultado", "")) < 3)
        ratio = bajo / len(h2h_detalle)
        if ratio >= 0.75:
            puntos = 2
        elif ratio >= 0.50:
            puntos = 1
        else:
            puntos = 0
        if puntos:
            score += puntos
            detalle["h2h_bajo_marcador"] = {"ratio_pct": round(ratio * 100), "partidos": len(h2h_detalle), "puntos": puntos}

    return score, detalle


def score_mundial_match(partido: dict, rankings: dict, fase: str = "group_stage") -> tuple[int, dict]:
    """
    Scoring para partidos del Mundial. Usa ranking FIFA como señal principal
    en lugar de posición en tabla de temporada.
    Devuelve (score_total, detalle).
    """
    score   = 0
    detalle = {}

    local = partido.get("equipo_local", "")
    visit = partido.get("equipo_visitante", "")
    rank_l = rankings.get(local, 60)
    rank_v = rankings.get(visit, 60)
    rank_diff = abs(rank_l - rank_v)

    # --- Ranking FIFA: brecha entre selecciones ---
    if rank_diff >= 30:
        pts = 4
    elif rank_diff >= 20:
        pts = 3
    elif rank_diff >= 10:
        pts = 2
    elif rank_diff >= 5:
        pts = 1
    else:
        pts = 0
    score += pts
    detalle["ranking_fifa"] = {"local": rank_l, "visitante": rank_v, "diff": rank_diff, "puntos": pts}

    # --- Elite vs débil: top 10 contra rank 30+ ---
    if min(rank_l, rank_v) <= 10 and max(rank_l, rank_v) >= 30:
        score += 3
        detalle["elite_vs_debil"] = {"puntos": 3, "desc": f"Top10 vs Rank{max(rank_l,rank_v)}+"}

    # --- Posición en grupo (solo fase de grupos) ---
    if fase == "group_stage":
        pos_l = partido.get("pos_local")
        pos_v = partido.get("pos_visitante")
        if pos_l is not None and pos_v is not None:
            gap = abs(pos_l - pos_v)
            pts = 2 if gap >= 2 else (1 if gap >= 1 else 0)
            score += pts
            detalle["pos_grupo"] = {"local": pos_l, "visitante": pos_v, "gap": gap, "puntos": pts}

        # Última jornada: clasificación en juego = más caos
        pj_l = partido.get("pj_local") or 0
        if pj_l >= 2:
            score -= 1
            detalle["ultima_jornada"] = {"puntos": -1, "desc": "3ª jornada de grupos — resultados más imprevisibles"}

    # --- Fase eliminatoria: sin empate posible ---
    if fase != "group_stage":
        score += 1
        detalle["eliminatoria"] = {"puntos": 1, "desc": "Sin empate en tiempo reglamentario"}

    # --- Probabilidad del pickcenter ---
    prob_l = partido.get("prob_local")
    prob_v = partido.get("prob_visitante")
    prob_e = partido.get("prob_empate")
    if prob_l is not None and prob_v is not None:
        max_prob = max(prob_l, prob_v)
        if max_prob >= 0.80:
            pts = 3
        elif max_prob >= 0.70:
            pts = 2
        elif max_prob >= 0.60:
            pts = 1
        elif max_prob < 0.45:
            pts = -2
        else:
            pts = 0
        score += pts
        detalle["prob_espn"] = {"max_prob_pct": round(max_prob * 100, 1), "puntos": pts}

        if prob_e is not None and prob_e >= 0.35:
            score -= 2
            detalle["empate_probable"] = {"prob_pct": round(prob_e * 100, 1), "puntos": -2}

    # --- Odds ---
    odds_l = partido.get("odds_local")
    odds_v = partido.get("odds_visitante")
    if odds_l is not None and odds_v is not None:
        fav_prob = _fav_implied_prob(odds_l, odds_v)
        if fav_prob >= 0.75:
            pts = 3
        elif fav_prob >= 0.65:
            pts = 2
        elif fav_prob >= 0.58:
            pts = 1
        elif fav_prob < 0.50:
            pts = -1
        else:
            pts = 0
        score += pts
        detalle["odds"] = {"fav_prob_pct": round(fav_prob * 100, 1), "puntos": pts}

    # --- H2H ---
    h2h_l = partido.get("h2h_ganados_local", 0) or 0
    h2h_v = partido.get("h2h_ganados_visitante", 0) or 0
    h2h_e = partido.get("h2h_empates", 0) or 0
    total_h2h = h2h_l + h2h_v + h2h_e
    if total_h2h >= 3:
        dominio = max(h2h_l, h2h_v) / total_h2h
        if dominio >= 0.80:
            pts = 2
        elif dominio >= 0.60:
            pts = 1
        else:
            pts = 0
        if pts:
            score += pts
            detalle["h2h"] = {
                "local": h2h_l, "visitante": h2h_v, "empates": h2h_e,
                "dominio_pct": round(dominio * 100, 1), "puntos": pts,
            }

    return score, detalle


def rank_mundial(partidos: list[dict], rankings: dict, top_n: int = 4) -> list[dict]:
    """Puntúa y rankea partidos del Mundial. Devuelve top_n."""
    import copy
    ranked = copy.deepcopy(partidos)
    for p in ranked:
        fase = p.get("fase") or "group_stage"
        score, detalle = score_mundial_match(p, rankings, fase)
        p["score_analisis"] = score
        p["score_detalle"]  = detalle
    ranked.sort(key=lambda x: x["score_analisis"], reverse=True)
    return ranked[:top_n]


def rank_partidos_under(partidos: list[dict], top_n: int = 8) -> list[dict]:
    """Ordena por score_under y devuelve los top_n más defensivos."""
    import copy
    ranked = copy.deepcopy(partidos)
    for p in ranked:
        score, detalle = score_under(p)
        p["score_under"]        = score
        p["score_under_detalle"] = detalle

    ranked.sort(key=lambda x: x["score_under"], reverse=True)
    return ranked[:top_n]
