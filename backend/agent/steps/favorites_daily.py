import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from datetime import date, datetime, timezone
from db import get_all_favoritos, get_or_create_liga, save_partido_futbol, save_seleccion_diaria
from tools.espn import get_scoreboard, get_standings, get_match_summary, get_team_corners_avg
from tools.match_scorer import score_match
from tools.telegram import send as telegram_send


def run(fecha: date | None = None):
    if fecha is None:
        fecha = date.today()

    favoritos = get_all_favoritos()
    if not favoritos:
        print("[favorites] Sin favoritos configurados.")
        return

    print(f"\n[favorites] Analizando partidos de favoritos — {fecha}")

    # Agrupar favoritos por liga para minimizar llamadas ESPN
    por_liga: dict[str, list[dict]] = {}
    for fav in favoritos:
        por_liga.setdefault(fav["espn_liga_code"], []).append(fav)

    partidos_favoritos = []

    for liga_code, favs_liga in por_liga.items():
        team_ids = {f["espn_team_id"] for f in favs_liga}
        fav_by_tid = {f["espn_team_id"]: f for f in favs_liga}
        fav_ref = favs_liga[0]

        print(f"  → {liga_code}: buscando partidos de hoy...")
        partidos_liga = get_scoreboard(liga_code, fecha)

        # Standings y scoring require estos datos
        tabla = get_standings(liga_code)
        total_equipos = len(tabla) if tabla else 20

        for p in partidos_liga:
            if p["estado"] == "finalizado":
                continue

            tid_local = p.get("espn_team_id_local", "")
            tid_visit = p.get("espn_team_id_visit", "")
            matched_tid = None
            if tid_local in team_ids:
                matched_tid = tid_local
            elif tid_visit in team_ids:
                matched_tid = tid_visit

            if not matched_tid:
                continue

            fav = fav_by_tid[matched_tid]
            print(f"    ✓ {fav['team_name']} juega: {p['equipo_local']} vs {p['equipo_visitante']}")

            # Enriquecer con standings
            info_l = tabla.get(p["equipo_local"], {})
            info_v = tabla.get(p["equipo_visitante"], {})
            p.update({
                "liga_uuid":       get_or_create_liga(liga_code, fav["liga_nombre"], fav["pais"]),
                "liga_codigo":     liga_code,
                "liga_nombre":     fav["liga_nombre"],
                "total_equipos":   total_equipos,
                "pos_local":       info_l.get("pos"),
                "pos_visitante":   info_v.get("pos"),
                "pts_local":       info_l.get("pts"),
                "pts_visitante":   info_v.get("pts"),
                "pj_local":        info_l.get("pj"),
                "pj_visitante":    info_v.get("pj"),
                "gf_local":        info_l.get("gf"),
                "gc_local":        info_l.get("gc"),
                "gf_visitante":    info_v.get("gf"),
                "gc_visitante":    info_v.get("gc"),
                "forma_local":     info_l.get("forma"),
                "forma_visitante": info_v.get("forma"),
                "gpm_rank_local":  None,
                "gpm_rank_visitante": None,
                "gpm_equipos_liga": total_equipos,
            })

            # Odds + H2H
            print(f"    → odds/H2H...", end=" ", flush=True)
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
                print("OK")
            else:
                p.update({k: None for k in ["odds_local","odds_empate","odds_visitante","spread",
                    "over_under","prob_local","prob_empate","prob_visitante",
                    "h2h_ganados_local","h2h_ganados_visitante","h2h_empates","h2h_detalle","lideres"]})
                print("sin datos")

            # Corners
            tid_l = p.get("espn_team_id_local")
            tid_v = p.get("espn_team_id_visit")
            if tid_l and tid_v:
                p["corners_local"]     = get_team_corners_avg(liga_code, tid_l, n_partidos=10)
                p["corners_visitante"] = get_team_corners_avg(liga_code, tid_v, n_partidos=10)
            else:
                p["corners_local"] = p["corners_visitante"] = None

            # Score
            score, detalle = score_match(p, total_equipos, ignorar_temporada=False)
            p["score_analisis"] = score
            p["score_detalle"]  = detalle
            p["_fav_team"]      = fav["team_name"]

            partidos_favoritos.append(p)

    if not partidos_favoritos:
        print("[favorites] Ningún favorito juega hoy.")
        return

    # Guardar partidos en DB
    print(f"\n[favorites] Guardando {len(partidos_favoritos)} partido(s) de favoritos...")
    for p in partidos_favoritos:
        save_partido_futbol(p)

    # Guardar selección con tipo='favorito'
    save_seleccion_diaria(fecha, partidos_favoritos, tipo="favorito", razones_key="score_detalle")

    # Telegram
    _send_telegram(partidos_favoritos, fecha)
    print(f"[favorites] Completado — {len(partidos_favoritos)} partido(s) guardados.")


def _send_telegram(partidos: list[dict], fecha: date):
    from datetime import timedelta
    lines = [f"⭐ *Favoritos — {fecha.strftime('%d/%m/%Y')}*\n"]
    for p in partidos:
        score = p["score_analisis"]
        icon  = "🟢" if score >= 8 else "🟡" if score >= 5 else "⚪"
        ou    = f' | O/U {p["over_under"]}' if p.get("over_under") else ""
        hora  = p["hora_utc"].strftime("%H:%M UTC") if p.get("hora_utc") else "—"
        lines.append(
            f'{icon} ⭐ *{p["_fav_team"]}*\n'
            f'   {p["equipo_local"]} vs {p["equipo_visitante"]}\n'
            f'   {p["liga_nombre"]} · {hora}{ou} · *{score} pts*'
        )
    telegram_send("\n\n".join(lines))
