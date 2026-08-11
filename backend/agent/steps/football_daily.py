import sys
import os
import copy
import argparse
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

import requests
from datetime import date, datetime, timedelta, timezone
from tools.espn import get_scoreboard, get_standings
from tools.enrich import enrich_partido
from tools.match_scorer import rank_partidos, rank_partidos_under, score_match
from tools.telegram import send_football as telegram_send
from db import get_active_ligas, get_all_favoritos, get_or_create_liga, save_partido_futbol, save_seleccion_diaria, get_ligas_config

OLLAMA_URL = "http://localhost:11434/api/chat"
MODEL      = "llama3.2:3b"

SYSTEM_PROMPT = """Eres un analista de fútbol experto. Recibirás datos de partidos seleccionados hoy.
Para cada partido, genera un análisis breve (2-3 líneas) que incluya:
- El favorito claro y por qué (posición tabla, forma, historial)
- Una predicción concreta: resultado más probable
- Una apuesta de valor si la hay (corners, goles, jugador)
Sé directo y usa los datos. No inventes información."""


def run(fecha: date | None = None):
    if fecha is None:
        fecha = date.today()

    print("\n" + "="*50)
    print(f"[football_daily] Iniciando selección de partidos — {fecha}")
    print("="*50)

    hoy    = fecha
    ligas  = get_active_ligas()

    if not ligas:
        print("[football_daily] No hay ligas activas en DB. Seed las ligas primero.")
        return

    print(f"[football_daily] {len(ligas)} ligas activas: {', '.join(l['codigo_espn'] for l in ligas)}")

    # 1. Fetch standings de todas las ligas (una sola vez por liga)
    print("\n[football_daily] Cargando standings...")
    standings_cache = {}
    for liga in ligas:
        codigo = liga["codigo_espn"]
        print(f"  → standings {codigo}...", end=" ", flush=True)
        standings_cache[codigo] = get_standings(codigo)
        print(f"{len(standings_cache[codigo])} equipos")

    # 1b. Calcular ranking under por liga (rank 1 = equipo con menos goles totales/partido)
    gpm_rank_cache = {}
    for codigo, tabla in standings_cache.items():
        if not tabla:
            continue
        team_gpms = []
        for nombre, stats in tabla.items():
            pj = stats.get("pj") or 1
            gf = stats.get("gf") or 0
            gc = stats.get("gc") or 0
            team_gpms.append((nombre, (gf + gc) / pj))
        team_gpms.sort(key=lambda x: x[1])
        gpm_rank_cache[codigo] = {nombre: i + 1 for i, (nombre, _) in enumerate(team_gpms)}

    # 2. Fetch scoreboard del día para cada liga
    print("\n[football_daily] Buscando partidos de hoy...")
    todos_los_partidos = []

    for liga in ligas:
        codigo         = liga["codigo_espn"]
        tabla          = standings_cache.get(codigo, {})
        total_equipos  = len(tabla) if tabla else 20

        print(f"  → {liga['nombre']} ({codigo})...", end=" ", flush=True)
        partidos_liga = get_scoreboard(codigo, hoy)

        if not partidos_liga:
            print("sin partidos hoy")
            continue

        print(f"{len(partidos_liga)} partidos")

        for p in partidos_liga:
            # Solo programados o en juego (no finalizados)
            if p["estado"] == "finalizado":
                continue

            nombre_local    = p["equipo_local"]
            nombre_visit    = p["equipo_visitante"]
            info_local      = tabla.get(nombre_local, {})
            info_visit      = tabla.get(nombre_visit, {})

            p["liga_uuid"]       = liga["uuid"]
            p["liga_codigo"]     = codigo
            p["liga_nombre"]     = liga["nombre"]
            p["total_equipos"]   = total_equipos
            p["pos_local"]       = info_local.get("pos")
            p["pos_visitante"]   = info_visit.get("pos")
            p["pts_local"]       = info_local.get("pts")
            p["pts_visitante"]   = info_visit.get("pts")
            p["pj_local"]        = info_local.get("pj")
            p["pj_visitante"]    = info_visit.get("pj")
            p["gf_local"]        = info_local.get("gf")
            p["gc_local"]        = info_local.get("gc")
            p["gf_visitante"]    = info_visit.get("gf")
            p["gc_visitante"]    = info_visit.get("gc")
            p["forma_local"]     = info_local.get("forma")
            p["forma_visitante"] = info_visit.get("forma")

            ranks = gpm_rank_cache.get(codigo, {})
            p["gpm_rank_local"]     = ranks.get(nombre_local)
            p["gpm_rank_visitante"] = ranks.get(nombre_visit)
            p["gpm_equipos_liga"]   = len(ranks) if ranks else total_equipos

            todos_los_partidos.append(p)

    if not todos_los_partidos:
        print("\n[football_daily] Sin partidos programados hoy. Fin.")
        return

    print(f"\n[football_daily] Total partidos hoy: {len(todos_los_partidos)}")

    # 3. Enriquecer con odds + H2H (summary)
    print("[football_daily] Enriqueciendo con odds y H2H...")
    for p in todos_los_partidos:
        print(f"  → {p['equipo_local']} vs {p['equipo_visitante']}...", end=" ", flush=True)
        enrich_partido(p, p["liga_codigo"])
        print("OK" if p.get("odds_local") is not None or p.get("prob_local") is not None else "sin datos")

    # 4. Puntuar todos en sitio (con_temporada como score principal en DB)
    print("\n[football_daily] Puntuando partidos...")
    for p in todos_los_partidos:
        score, detalle = score_match(p, p.get("total_equipos", 20), ignorar_temporada=False)
        p["score_analisis"] = score
        p["score_detalle"]  = detalle

    # Elegir top 8 en tres variantes (deepcopy interno en rank_partidos)
    seleccionados_a     = rank_partidos(todos_los_partidos, top_n=8, ignorar_temporada=False)
    seleccionados_b     = rank_partidos(todos_los_partidos, top_n=8, ignorar_temporada=True)
    seleccionados_under = rank_partidos_under(todos_los_partidos, top_n=8)

    # Inyectar ranking under de cada equipo en el detalle (se guarda en DB como razones)
    for p in seleccionados_under:
        p["score_under_detalle"]["rank_equipos"] = {
            "local":        p.get("gpm_rank_local"),
            "visitante":    p.get("gpm_rank_visitante"),
            "total_equipos": p.get("gpm_equipos_liga"),
        }

    # 4b. Priorizar favoritos: si un equipo favorito juega hoy pero no entró al Top 8 por score,
    # se agrega igual al final de Tabla A y B — independiente de si el partido es fácil o difícil de analizar.
    print("\n[football_daily] Agregando partidos de equipos favoritos...")
    favoritos_extra = _favoritos_pendientes(hoy, todos_los_partidos)
    ids_a = {p["espn_event_id"] for p in seleccionados_a}
    ids_b = {p["espn_event_id"] for p in seleccionados_b}
    if not favoritos_extra:
        print("  (ningún favorito fuera del Top 8 hoy)")
    for fav_p in favoritos_extra:
        todos_los_partidos.append(fav_p)
        if fav_p["espn_event_id"] not in ids_a:
            extra_a = copy.deepcopy(fav_p)
            extra_a["score_analisis"], extra_a["score_detalle"] = score_match(extra_a, extra_a.get("total_equipos", 20), ignorar_temporada=False)
            seleccionados_a.append(extra_a)
            print(f"  ⭐ Tabla A: {fav_p['equipo_local']} vs {fav_p['equipo_visitante']}")
        if fav_p["espn_event_id"] not in ids_b:
            extra_b = copy.deepcopy(fav_p)
            extra_b["score_analisis"], extra_b["score_detalle"] = score_match(extra_b, extra_b.get("total_equipos", 20), ignorar_temporada=True)
            seleccionados_b.append(extra_b)
            print(f"  ⭐ Tabla B: {fav_p['equipo_local']} vs {fav_p['equipo_visitante']}")

    print(f"\n{'='*50}")
    print("TABLA A — con penalización temporada (Top 8 + favoritos):")
    for i, p in enumerate(seleccionados_a, 1):
        print(f"  {i}. [{p['liga_nombre']}] {p['equipo_local']} vs {p['equipo_visitante']} — score: {p['score_analisis']}")

    print("\nTABLA B — sin penalización temporada (Top 8 + favoritos):")
    for i, p in enumerate(seleccionados_b, 1):
        print(f"  {i}. [{p['liga_nombre']}] {p['equipo_local']} vs {p['equipo_visitante']} — score: {p['score_analisis']}")

    print("\nTOP 8 — UNDER (menos goles esperados):")
    for i, p in enumerate(seleccionados_under, 1):
        print(f"  {i}. [{p['liga_nombre']}] {p['equipo_local']} vs {p['equipo_visitante']} — under_score: {p['score_under']}")
    print(f"{'='*50}\n")

    # 5. Guardar todos los partidos del día en DB
    print("[football_daily] Guardando partidos en DB...")
    for p in todos_los_partidos:
        save_partido_futbol(p)
        print(f"  ✓ {p['equipo_local']} vs {p['equipo_visitante']}")

    # 6. Guardar las tres selecciones
    save_seleccion_diaria(hoy, seleccionados_a,     tipo='con_temporada')
    save_seleccion_diaria(hoy, seleccionados_b,     tipo='sin_temporada')
    save_seleccion_diaria(hoy, seleccionados_under, tipo='under', razones_key='score_under_detalle')
    print(f"[football_daily] Selecciones guardadas — con_temporada: {len(seleccionados_a)}, sin_temporada: {len(seleccionados_b)}, under: {len(seleccionados_under)}")

    # 7. Análisis con Ollama (usamos la lista sin penalización como base para análisis)
    print("\n[football_daily] Generando análisis con Ollama...")
    analisis_texto = _generar_analisis_ollama(seleccionados_b)

    # 8. Notificación Telegram con las tres listas
    _enviar_telegram(hoy, seleccionados_a, seleccionados_b, seleccionados_under, analisis_texto)

    print("\n[football_daily] Completado.\n")


def _favoritos_pendientes(fecha: date, todos_los_partidos: list[dict]) -> list[dict]:
    """Partidos de hoy de equipos favoritos que no están en todos_los_partidos (liga no activa)."""
    favoritos = get_all_favoritos()
    if not favoritos:
        return []

    ligas_ya_cargadas = {p["liga_codigo"] for p in todos_los_partidos}
    ids_ya_cargados    = {p["espn_event_id"] for p in todos_los_partidos}

    pendientes_por_liga: dict[str, list[dict]] = {}
    for fav in favoritos:
        if fav["espn_liga_code"] not in ligas_ya_cargadas:
            pendientes_por_liga.setdefault(fav["espn_liga_code"], []).append(fav)

    resultado = []
    for liga_code, favs_liga in pendientes_por_liga.items():
        team_ids = {f["espn_team_id"] for f in favs_liga}
        fav_ref  = favs_liga[0]

        partidos_liga = get_scoreboard(liga_code, fecha)
        tabla         = get_standings(liga_code)
        total_equipos = len(tabla) if tabla else 20

        for p in partidos_liga:
            if p["estado"] == "finalizado" or p["espn_event_id"] in ids_ya_cargados:
                continue
            tid_l = p.get("espn_team_id_local", "")
            tid_v = p.get("espn_team_id_visit", "")
            if tid_l not in team_ids and tid_v not in team_ids:
                continue

            info_l = tabla.get(p["equipo_local"], {})
            info_v = tabla.get(p["equipo_visitante"], {})
            p.update({
                "liga_uuid":          get_or_create_liga(liga_code, fav_ref["liga_nombre"], fav_ref["pais"]),
                "liga_codigo":        liga_code,
                "liga_nombre":        fav_ref["liga_nombre"],
                "total_equipos":      total_equipos,
                "pos_local":          info_l.get("pos"),
                "pos_visitante":      info_v.get("pos"),
                "pts_local":          info_l.get("pts"),
                "pts_visitante":      info_v.get("pts"),
                "pj_local":           info_l.get("pj"),
                "pj_visitante":       info_v.get("pj"),
                "gf_local":           info_l.get("gf"),
                "gc_local":           info_l.get("gc"),
                "gf_visitante":       info_v.get("gf"),
                "gc_visitante":       info_v.get("gc"),
                "forma_local":        info_l.get("forma"),
                "forma_visitante":    info_v.get("forma"),
                "gpm_rank_local":     None,
                "gpm_rank_visitante": None,
                "gpm_equipos_liga":   total_equipos,
            })
            enrich_partido(p, liga_code)
            resultado.append(p)
            ids_ya_cargados.add(p["espn_event_id"])

    return resultado


def _generar_analisis_ollama(partidos: list[dict]) -> str:
    lineas = []
    for p in partidos:
        pos_l = p.get("pos_local", "?")
        pos_v = p.get("pos_visitante", "?")
        prob_l = f"{p['prob_local']*100:.0f}%" if p.get("prob_local") else "N/D"
        ou = p.get("over_under", "N/D")
        h2h_l = p.get("h2h_ganados_local", 0) or 0
        h2h_v = p.get("h2h_ganados_visitante", 0) or 0

        lineas.append(
            f"- {p['equipo_local']} (pos {pos_l}) vs {p['equipo_visitante']} (pos {pos_v}) | "
            f"Liga: {p['liga_nombre']} | "
            f"Prob local: {prob_l} | "
            f"O/U: {ou} | "
            f"H2H: {h2h_l}-{h2h_v} | "
            f"Forma local: {p.get('forma_local','?')} | Forma visit: {p.get('forma_visitante','?')} | "
            f"Score analizabilidad: {p['score_analisis']}"
        )

    contexto = "\n".join(lineas)
    prompt   = f"Partidos seleccionados para hoy:\n\n{contexto}\n\nDa el análisis de cada uno."

    try:
        res = requests.post(
            OLLAMA_URL,
            json={
                "model": MODEL,
                "messages": [
                    {"role": "system", "content": SYSTEM_PROMPT},
                    {"role": "user",   "content": prompt},
                ],
                "stream": False,
            },
            timeout=60,
        )
        res.raise_for_status()
        return res.json()["message"]["content"]
    except Exception as e:
        print(f"[football_daily] Ollama error: {e}")
        return ""


def _enviar_telegram(hoy: date, partidos_a: list[dict], partidos_b: list[dict], partidos_under: list[dict], analisis: str):
    fecha_str = hoy.strftime("%d/%m/%Y")
    lineas = [f"⚽ *Partidos del día — {fecha_str}*"]

    def _hora(p: dict) -> str:
        if p.get("hora_utc"):
            try:
                return f" ({p['hora_utc'].astimezone().strftime('%H:%M')})"
            except Exception:
                pass
        return ""

    def _bloque_partido(i: int, p: dict) -> str:
        pos_l  = p.get("pos_local", "?")
        pos_v  = p.get("pos_visitante", "?")
        prob_l = f" | Prob: {p['prob_local']*100:.0f}%" if p.get("prob_local") else ""
        ou     = f" | O/U {p['over_under']}" if p.get("over_under") else ""
        return (
            f"*{i}. {p['equipo_local']} vs {p['equipo_visitante']}*{_hora(p)}\n"
            f"  _{p['liga_nombre']}_ | Pos: {pos_l}° vs {pos_v}°{prob_l}{ou} | {p['score_analisis']}pts"
        )

    def _bloque_under(i: int, p: dict) -> str:
        ou      = f"O/U {p['over_under']}" if p.get("over_under") else "O/U N/D"
        xg      = p.get("score_under_detalle", {}).get("xg_estimado", {})
        xg_str  = f" | xG≈{xg['total_xg']}" if xg else ""
        prob_e  = f" | Empate {p['prob_empate']*100:.0f}%" if p.get("prob_empate") else ""
        return (
            f"*{i}. {p['equipo_local']} vs {p['equipo_visitante']}*{_hora(p)}\n"
            f"  _{p['liga_nombre']}_ | {ou}{xg_str}{prob_e} | {p['score_under']}pts"
        )

    lineas.append("\n*Tabla A — con contexto de temporada:*")
    for i, p in enumerate(partidos_a, 1):
        lineas.append(_bloque_partido(i, p))

    lineas.append("\n*Tabla B — sin penalización temporada joven:*")
    for i, p in enumerate(partidos_b, 1):
        lineas.append(_bloque_partido(i, p))

    lineas.append("\n*🔒 Under — partidos con menos goles esperados:*")
    for i, p in enumerate(partidos_under, 1):
        lineas.append(_bloque_under(i, p))

    if analisis:
        lineas.append(f"\n🤖 *Análisis:*\n_{analisis[:600]}_")

    telegram_send("\n".join(lineas))
    print("[football_daily] Telegram enviado")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--manana", action="store_true", help="Seleccionar partidos del día siguiente")
    parser.add_argument("--fecha", type=str, help="Fecha concreta YYYY-MM-DD")
    args = parser.parse_args()

    if args.fecha:
        target = date.fromisoformat(args.fecha)
    elif args.manana:
        target = date.today() + timedelta(days=1)
    else:
        target = date.today()

    run(target)
