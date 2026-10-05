"""
Mundial 2026 — análisis diario de partidos.

Diferencias respecto a football_daily.py:
- Usa ranking FIFA como señal principal (no posición en tabla de temporada)
- Detecta fase automáticamente: group_stage / round_of_16 / quarter_final / semi_final / final
- En fase de grupos: enriquece con standings por grupo (A-L)
- En eliminatorias: sin penalización por "temporada joven"
- Scorer propio: score_mundial_match()
"""
import sys
import os
import argparse
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

import requests
from datetime import date, timedelta
from tools.espn import (
    get_scoreboard, get_match_summary, get_world_cup_group_standings, get_fifa_rankings
)
from tools.match_scorer import rank_mundial, rank_partidos_under, score_mundial_match, score_under
from tools.telegram import send as telegram_send
from db import (
    get_active_ligas, save_partido_futbol, save_seleccion_diaria,
    migrate_mundial_columns, get_or_create_mundial_liga,
)
from llm import OLLAMA_CHAT_URL, OLLAMA_MODEL

LIGA_CODE  = os.getenv("MUNDIAL_ESPN_CODE", "fifa.world")

SYSTEM_PROMPT = """Eres un analista experto en fútbol internacional y Mundiales.
Recibirás partidos seleccionados del Mundial 2026 con ranking FIFA, probabilidades y H2H.
Para cada partido:
- Indica el favorito claro y por qué (ranking FIFA, forma en el torneo, H2H)
- Predicción concreta: resultado más probable
- Una apuesta de valor si la hay (goles, corners, jugador)
Sé directo y usa los datos. No inventes estadísticas."""

FASE_LABELS = {
    "group_stage":  "Fase de Grupos",
    "round_of_16":  "Octavos de Final",
    "quarter_final": "Cuartos de Final",
    "semi_final":   "Semifinales",
    "final":        "Gran Final",
}


def run(fecha: date | None = None):
    if fecha is None:
        fecha = date.today()

    print("\n" + "="*50)
    print(f"[mundial_daily] Iniciando — {fecha}")
    print("="*50)

    # 1. Asegurar columnas y liga en DB
    migrate_mundial_columns()
    liga_uuid = get_or_create_mundial_liga(LIGA_CODE)

    # 2. Fetch FIFA rankings (una sola llamada)
    print("\n[mundial_daily] Cargando rankings FIFA...")
    rankings = get_fifa_rankings(LIGA_CODE)
    print(f"  → {len(rankings)} selecciones rankeadas" if rankings else "  → Sin datos de ranking (se usará rank 60 por defecto)")

    # 3. Fetch scoreboard del día
    print(f"\n[mundial_daily] Scoreboard {LIGA_CODE} — {fecha}...")
    partidos_raw = get_scoreboard(LIGA_CODE, fecha)

    if not partidos_raw:
        print("[mundial_daily] Sin partidos hoy. Fin.")
        return

    partidos = [p for p in partidos_raw if p["estado"] != "finalizado"]
    print(f"  → {len(partidos_raw)} partidos | {len(partidos)} pendientes")

    if not partidos:
        print("[mundial_daily] Todos los partidos ya finalizaron.")
        return

    # 4. Detectar fases presentes hoy
    fases_hoy = set(p.get("fase") or "group_stage" for p in partidos)
    grupos_hoy = set(p.get("grupo") for p in partidos if p.get("grupo"))
    print(f"  → Fases: {', '.join(FASE_LABELS.get(f, f) for f in fases_hoy)}")
    if grupos_hoy:
        print(f"  → Grupos: {', '.join(sorted(grupos_hoy))}")

    # 5. Standings por grupo (solo si hay fase de grupos hoy)
    group_standings = {}
    if "group_stage" in fases_hoy:
        print("\n[mundial_daily] Cargando standings de grupos...")
        group_standings = get_world_cup_group_standings(LIGA_CODE)
        for letter, tabla in group_standings.items():
            print(f"  → Grupo {letter}: {', '.join(tabla.keys())}")

    # 6. Enriquecer cada partido con standings de su grupo
    for p in partidos:
        p["liga_uuid"] = liga_uuid
        fase  = p.get("fase") or "group_stage"
        grupo = p.get("grupo")

        if fase == "group_stage" and grupo and grupo in group_standings:
            tabla = group_standings[grupo]
            info_l = tabla.get(p["equipo_local"], {})
            info_v = tabla.get(p["equipo_visitante"], {})
            p["pos_local"]       = info_l.get("pos")
            p["pos_visitante"]   = info_v.get("pos")
            p["pts_local"]       = info_l.get("pts")
            p["pts_visitante"]   = info_v.get("pts")
            p["pj_local"]        = info_l.get("pj")
            p["pj_visitante"]    = info_v.get("pj")
            p["gf_local"]        = info_l.get("gf")
            p["gc_local"]        = info_l.get("gc")
            p["gf_visitante"]    = info_v.get("gf")
            p["gc_visitante"]    = info_v.get("gc")
        else:
            for key in ("pos_local", "pos_visitante", "pts_local", "pts_visitante",
                        "pj_local", "pj_visitante", "gf_local", "gc_local",
                        "gf_visitante", "gc_visitante"):
                p.setdefault(key, None)

        p.setdefault("forma_local", None)
        p.setdefault("forma_visitante", None)

    # 7. Enriquecer con odds + H2H
    print("\n[mundial_daily] Enriqueciendo con odds y H2H...")
    for p in partidos:
        event_id = p["espn_event_id"]
        print(f"  → {p['equipo_local']} vs {p['equipo_visitante']}...", end=" ", flush=True)
        summary = get_match_summary(LIGA_CODE, event_id)

        if summary:
            odds  = summary.get("odds", {})
            probs = summary.get("probabilidades", {})
            h2h   = summary.get("h2h", {})
            p["odds_local"]            = odds.get("local")
            p["odds_empate"]           = odds.get("empate")
            p["odds_visitante"]        = odds.get("visitante")
            p["spread"]                = odds.get("spread")
            p["over_under"]            = odds.get("over_under")
            p["prob_local"]            = probs.get("local")
            p["prob_empate"]           = probs.get("empate")
            p["prob_visitante"]        = probs.get("visitante")
            p["h2h_ganados_local"]     = h2h.get("ganados_local")
            p["h2h_ganados_visitante"] = h2h.get("ganados_visitante")
            p["h2h_empates"]           = h2h.get("empates")
            p["h2h_detalle"]           = h2h.get("detalle")
            p["lideres"]               = summary.get("lideres")
            print("OK")
        else:
            for key in ("odds_local", "odds_empate", "odds_visitante", "spread", "over_under",
                        "prob_local", "prob_empate", "prob_visitante",
                        "h2h_ganados_local", "h2h_ganados_visitante", "h2h_empates",
                        "h2h_detalle", "lideres"):
                p.setdefault(key, None)
            print("sin datos")

        p.setdefault("corners_local", None)
        p.setdefault("corners_visitante", None)

    # 8. Scoring
    print("\n[mundial_daily] Puntuando partidos...")
    for p in partidos:
        fase = p.get("fase") or "group_stage"
        score, detalle = score_mundial_match(p, rankings, fase)
        p["score_analisis"] = score
        p["score_detalle"]  = detalle

    seleccionados    = rank_mundial(partidos, rankings, top_n=4)
    seleccionados_under = rank_partidos_under(partidos, top_n=4)

    print(f"\n{'='*50}")
    print("TOP 4 — Mundial (por analizabilidad):")
    for i, p in enumerate(seleccionados, 1):
        fase_label = FASE_LABELS.get(p.get("fase") or "", "")
        grupo_str  = f" [Grupo {p['grupo']}]" if p.get("grupo") else ""
        print(f"  {i}. {p['equipo_local']} vs {p['equipo_visitante']}{grupo_str} — {fase_label} — score: {p['score_analisis']}")

    print("\nTOP 4 — Under (menos goles esperados):")
    for i, p in enumerate(seleccionados_under, 1):
        print(f"  {i}. {p['equipo_local']} vs {p['equipo_visitante']} — under_score: {p['score_under']}")
    print(f"{'='*50}\n")

    # 9. Guardar en DB
    print("[mundial_daily] Guardando partidos en DB...")
    for p in partidos:
        save_partido_futbol(p)
        print(f"  ✓ {p['equipo_local']} vs {p['equipo_visitante']}")

    save_seleccion_diaria(fecha, seleccionados, tipo="mundial_top")
    save_seleccion_diaria(fecha, seleccionados_under, tipo="mundial_under", razones_key="score_under_detalle")
    print(f"[mundial_daily] Selecciones guardadas")

    # 10. Análisis Ollama
    print("\n[mundial_daily] Generando análisis con Ollama...")
    analisis = _generar_analisis_ollama(seleccionados, rankings)

    # 11. Telegram
    _enviar_telegram(fecha, seleccionados, seleccionados_under, rankings, analisis)

    print("\n[mundial_daily] Completado.\n")


def _generar_analisis_ollama(partidos: list[dict], rankings: dict) -> str:
    lineas = []
    for p in partidos:
        local = p["equipo_local"]
        visit = p["equipo_visitante"]
        rank_l = rankings.get(local, "?")
        rank_v = rankings.get(visit, "?")
        prob_l = f"{p['prob_local']*100:.0f}%" if p.get("prob_local") else "N/D"
        ou     = p.get("over_under", "N/D")
        h2h_l  = p.get("h2h_ganados_local", 0) or 0
        h2h_v  = p.get("h2h_ganados_visitante", 0) or 0
        fase   = FASE_LABELS.get(p.get("fase") or "", "")
        grupo  = f" [Grupo {p['grupo']}]" if p.get("grupo") else ""

        lineas.append(
            f"- {local} (FIFA #{rank_l}) vs {visit} (FIFA #{rank_v}){grupo} | "
            f"{fase} | Prob local: {prob_l} | O/U: {ou} | H2H: {h2h_l}-{h2h_v} | "
            f"Score: {p['score_analisis']}"
        )

    try:
        res = requests.post(
            OLLAMA_CHAT_URL,
            json={
                "model": OLLAMA_MODEL,
                "messages": [
                    {"role": "system", "content": SYSTEM_PROMPT},
                    {"role": "user",   "content": f"Partidos del Mundial de hoy:\n\n" + "\n".join(lineas) + "\n\nDa el análisis."},
                ],
                "stream": False,
            },
            timeout=60,
        )
        res.raise_for_status()
        return res.json()["message"]["content"]
    except Exception as e:
        print(f"[mundial_daily] Ollama error: {e}")
        return ""


def _enviar_telegram(
    fecha: date,
    seleccionados: list[dict],
    seleccionados_under: list[dict],
    rankings: dict,
    analisis: str,
):
    fecha_str = fecha.strftime("%d/%m/%Y")
    lineas = [f"🌍 *Mundial 2026 — {fecha_str}*"]

    def _hora(p: dict) -> str:
        if p.get("hora_utc"):
            try:
                return f" ({p['hora_utc'].astimezone().strftime('%H:%M')})"
            except Exception:
                return ""
        return ""

    def _bloque(i: int, p: dict) -> str:
        local  = p["equipo_local"]
        visit  = p["equipo_visitante"]
        rank_l = rankings.get(local, "?")
        rank_v = rankings.get(visit, "?")
        fase   = FASE_LABELS.get(p.get("fase") or "", "")
        grupo  = f"Grupo {p['grupo']} — " if p.get("grupo") else ""
        prob_l = f" | Prob: {p['prob_local']*100:.0f}%" if p.get("prob_local") else ""
        ou     = f" | O/U {p['over_under']}" if p.get("over_under") else ""
        return (
            f"*{i}. {local} vs {visit}*{_hora(p)}\n"
            f"  _{grupo}{fase}_ | #{rank_l} vs #{rank_v}{prob_l}{ou} | {p['score_analisis']}pts"
        )

    def _bloque_under(i: int, p: dict) -> str:
        ou     = f"O/U {p['over_under']}" if p.get("over_under") else "O/U N/D"
        prob_e = f" | Empate {p['prob_empate']*100:.0f}%" if p.get("prob_empate") else ""
        xg     = p.get("score_under_detalle", {}).get("xg_estimado", {})
        xg_str = f" | xG≈{xg['total_xg']}" if xg else ""
        return (
            f"*{i}. {p['equipo_local']} vs {p['equipo_visitante']}*{_hora(p)}\n"
            f"  _{FASE_LABELS.get(p.get('fase') or '', '')}_ | {ou}{xg_str}{prob_e} | {p['score_under']}pts"
        )

    lineas.append("\n*🎯 Top partidos para analizar:*")
    for i, p in enumerate(seleccionados, 1):
        lineas.append(_bloque(i, p))

    lineas.append("\n*🔒 Partidos Under (menos goles esperados):*")
    for i, p in enumerate(seleccionados_under, 1):
        lineas.append(_bloque_under(i, p))

    if analisis:
        lineas.append(f"\n🤖 *Análisis:*\n_{analisis[:600]}_")

    telegram_send("\n".join(lineas))
    print("[mundial_daily] Telegram enviado")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--manana", action="store_true", help="Partidos de mañana")
    parser.add_argument("--fecha",  type=str,            help="Fecha concreta YYYY-MM-DD")
    args = parser.parse_args()

    if args.fecha:
        target = date.fromisoformat(args.fecha)
    elif args.manana:
        target = date.today() + timedelta(days=1)
    else:
        target = date.today()

    run(target)
