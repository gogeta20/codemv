import psycopg2
import json
import os
from datetime import date, datetime, timezone

DB_CONFIG = {
    "host":     os.getenv("DB_HOST", "localhost"),
    "port":     int(os.getenv("DB_PORT", 25432)),
    "dbname":   os.getenv("DB_NAME", "codemv"),
    "user":     os.getenv("DB_USER", "codemv"),
    "password": os.getenv("DB_PASSWORD", "codemv_pass"),
}

def get_connection():
    return psycopg2.connect(**DB_CONFIG)

def get_active_acciones() -> list[dict]:
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT uuid, symbol, name, alert_threshold_pct FROM acciones WHERE is_active = true ORDER BY symbol")
            rows = cur.fetchall()
            return [{"uuid": r[0], "symbol": r[1], "name": r[2], "threshold": float(r[3])} for r in rows]

def save_precio(accion_uuid: str, precio: dict) -> None:
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id FROM acciones WHERE uuid = %s", (accion_uuid,))
            row = cur.fetchone()
            if not row:
                return
            accion_id = row[0]
            cur.execute("""
                INSERT INTO acciones_precios
                    (uuid, accion_id, date, price_close, price_open, price_high, price_low, volume, prev_close, change_pct, change_amount, created_at)
                VALUES
                    (gen_random_uuid(), %s, CURRENT_DATE, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
                ON CONFLICT (accion_id, date) DO UPDATE SET
                    price_close   = EXCLUDED.price_close,
                    price_open    = EXCLUDED.price_open,
                    price_high    = EXCLUDED.price_high,
                    price_low     = EXCLUDED.price_low,
                    volume        = EXCLUDED.volume,
                    prev_close    = EXCLUDED.prev_close,
                    change_pct    = EXCLUDED.change_pct,
                    change_amount = EXCLUDED.change_amount
            """, (
                accion_id,
                precio.get("price"),
                precio.get("open"),
                precio.get("high"),
                precio.get("low"),
                precio.get("volume"),
                precio.get("prev_close"),
                precio.get("change_pct"),
                precio.get("change_amount"),
            ))
            conn.commit()

# ─────────────────────────────────────────────
#  FÚTBOL
# ─────────────────────────────────────────────

def get_active_ligas() -> list[dict]:
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT uuid, nombre, codigo_espn, pais, division
                FROM futbol_ligas
                WHERE activa = true
                ORDER BY pais, division
            """)
            rows = cur.fetchall()
            return [
                {"uuid": r[0], "nombre": r[1], "codigo_espn": r[2], "pais": r[3], "division": r[4]}
                for r in rows
            ]


def get_ligas_config() -> dict:
    """Devuelve {codigo_espn: uuid} para lookup rápido."""
    ligas = get_active_ligas()
    return {l["codigo_espn"]: l["uuid"] for l in ligas}


def save_partido_futbol(p: dict) -> None:
    """Upsert de un partido por espn_event_id."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id FROM futbol_ligas WHERE uuid = %s", (p["liga_uuid"],))
            row = cur.fetchone()
            if not row:
                print(f"[db] Liga uuid {p['liga_uuid']} no encontrada, skip")
                return
            liga_id = row[0]

            hora_utc = p.get("hora_utc")
            if hora_utc and hasattr(hora_utc, "isoformat"):
                hora_utc = hora_utc.isoformat()

            cur.execute("""
                INSERT INTO futbol_partidos (
                    uuid, liga_id, espn_event_id, fecha, hora_utc,
                    equipo_local, equipo_visitante, estadio, estado,
                    pos_local, pos_visitante, pts_local, pts_visitante,
                    pj_local, pj_visitante, forma_local, forma_visitante,
                    odds_local, odds_empate, odds_visitante, spread, over_under,
                    prob_local, prob_empate, prob_visitante,
                    h2h_ganados_local, h2h_ganados_visitante, h2h_empates, h2h_detalle,
                    score_analisis, score_detalle,
                    goles_local, goles_visitante, lideres,
                    corners_local, corners_visitante,
                    fase, grupo,
                    created_at, updated_at
                ) VALUES (
                    gen_random_uuid(), %s, %s, %s, %s,
                    %s, %s, %s, %s,
                    %s, %s, %s, %s,
                    %s, %s, %s, %s,
                    %s, %s, %s, %s, %s,
                    %s, %s, %s,
                    %s, %s, %s, %s,
                    %s, %s,
                    %s, %s, %s,
                    %s, %s,
                    %s, %s,
                    NOW(), NOW()
                )
                ON CONFLICT (espn_event_id) DO UPDATE SET
                    estado           = EXCLUDED.estado,
                    pos_local        = EXCLUDED.pos_local,
                    pos_visitante    = EXCLUDED.pos_visitante,
                    pts_local        = EXCLUDED.pts_local,
                    pts_visitante    = EXCLUDED.pts_visitante,
                    pj_local         = EXCLUDED.pj_local,
                    pj_visitante     = EXCLUDED.pj_visitante,
                    forma_local      = EXCLUDED.forma_local,
                    forma_visitante  = EXCLUDED.forma_visitante,
                    odds_local       = EXCLUDED.odds_local,
                    odds_empate      = EXCLUDED.odds_empate,
                    odds_visitante   = EXCLUDED.odds_visitante,
                    spread           = EXCLUDED.spread,
                    over_under       = EXCLUDED.over_under,
                    prob_local       = EXCLUDED.prob_local,
                    prob_empate      = EXCLUDED.prob_empate,
                    prob_visitante   = EXCLUDED.prob_visitante,
                    h2h_ganados_local      = EXCLUDED.h2h_ganados_local,
                    h2h_ganados_visitante  = EXCLUDED.h2h_ganados_visitante,
                    h2h_empates            = EXCLUDED.h2h_empates,
                    h2h_detalle            = EXCLUDED.h2h_detalle,
                    score_analisis   = EXCLUDED.score_analisis,
                    score_detalle    = EXCLUDED.score_detalle,
                    goles_local      = EXCLUDED.goles_local,
                    goles_visitante  = EXCLUDED.goles_visitante,
                    lideres              = EXCLUDED.lideres,
                    corners_local        = EXCLUDED.corners_local,
                    corners_visitante    = EXCLUDED.corners_visitante,
                    fase             = EXCLUDED.fase,
                    grupo            = EXCLUDED.grupo,
                    updated_at           = NOW()
            """, (
                liga_id, p["espn_event_id"], p["fecha"], hora_utc,
                p["equipo_local"], p["equipo_visitante"], p.get("estadio"), p.get("estado", "programado"),
                p.get("pos_local"), p.get("pos_visitante"),
                p.get("pts_local"), p.get("pts_visitante"),
                p.get("pj_local"), p.get("pj_visitante"),
                p.get("forma_local"), p.get("forma_visitante"),
                p.get("odds_local"), p.get("odds_empate"), p.get("odds_visitante"),
                p.get("spread"), p.get("over_under"),
                p.get("prob_local"), p.get("prob_empate"), p.get("prob_visitante"),
                p.get("h2h_ganados_local"), p.get("h2h_ganados_visitante"), p.get("h2h_empates"),
                json.dumps(p["h2h_detalle"]) if p.get("h2h_detalle") else None,
                p.get("score_analisis", 0),
                json.dumps(p["score_detalle"]) if p.get("score_detalle") else None,
                p.get("goles_local"), p.get("goles_visitante"),
                json.dumps(p["lideres"]) if p.get("lideres") else None,
                json.dumps(p["corners_local"]) if p.get("corners_local") else None,
                json.dumps(p["corners_visitante"]) if p.get("corners_visitante") else None,
                p.get("fase"), p.get("grupo"),
            ))
            conn.commit()


def save_seleccion_diaria(fecha: date, partidos: list[dict], tipo: str = 'con_temporada', razones_key: str = 'score_detalle') -> None:
    """Borra la selección del día para ese tipo y la reescribe."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "DELETE FROM futbol_seleccion_diaria WHERE fecha = %s AND tipo = %s",
                (fecha, tipo),
            )

            for i, p in enumerate(partidos, 1):
                cur.execute("SELECT id FROM futbol_partidos WHERE espn_event_id = %s", (p["espn_event_id"],))
                row = cur.fetchone()
                if not row:
                    continue
                partido_id = row[0]

                razones = p.get(razones_key)

                cur.execute("""
                    INSERT INTO futbol_seleccion_diaria
                        (uuid, fecha, partido_id, tipo, posicion, razones, created_at)
                    VALUES
                        (gen_random_uuid(), %s, %s, %s, %s, %s, NOW())
                """, (
                    fecha,
                    partido_id,
                    tipo,
                    i,
                    json.dumps(razones),
                ))

            conn.commit()


def save_ligas_gpm(ligas: list[dict]) -> None:
    """Upsert de goles por partido por liga. Reemplaza todo en cada ejecución."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            for liga in ligas:
                cur.execute("""
                    INSERT INTO futbol_ligas_gpm
                        (uuid, codigo_espn, liga, pais, tier, gpm, partidos, equipos, updated_at)
                    VALUES
                        (gen_random_uuid(), %s, %s, %s, %s, %s, %s, %s, NOW())
                    ON CONFLICT (codigo_espn) DO UPDATE SET
                        liga      = EXCLUDED.liga,
                        pais      = EXCLUDED.pais,
                        tier      = EXCLUDED.tier,
                        gpm       = EXCLUDED.gpm,
                        partidos  = EXCLUDED.partidos,
                        equipos   = EXCLUDED.equipos,
                        updated_at = NOW()
                """, (
                    liga['codigo'], liga['liga'], liga['pais'], liga['tier'],
                    liga['gpm'], liga['partidos'], liga['equipos'],
                ))
            conn.commit()
    print(f"[db] futbol_ligas_gpm actualizado ({len(ligas)} ligas)")


def seed_ligas() -> None:
    """Inserta las 9 ligas configuradas si no existen."""
    ligas = [
        ("España — La Liga",        "esp.1", "España",    1),
        ("España — Segunda",        "esp.2", "España",    2),
        ("Alemania — Bundesliga",   "ger.1", "Alemania",  1),
        ("Alemania — 2. Bundesliga","ger.2", "Alemania",  2),
        ("Italia — Serie A",        "ita.1", "Italia",    1),
        ("Italia — Serie B",        "ita.2", "Italia",    2),
        ("Noruega — Eliteserien",   "nor.1", "Noruega",   1),
        ("Bolivia — Liga Profesional","bol.1","Bolivia",  1),
        ("Australia — A-League",    "aus.1", "Australia", 1),
        ("Inglaterra — Premier League", "eng.1", "Inglaterra", 1),
        ("Inglaterra — Championship",   "eng.2", "Inglaterra", 2),
    ]
    with get_connection() as conn:
        with conn.cursor() as cur:
            for nombre, codigo, pais, division in ligas:
                cur.execute("""
                    INSERT INTO futbol_ligas (uuid, nombre, codigo_espn, pais, division, activa, created_at)
                    VALUES (gen_random_uuid(), %s, %s, %s, %s, true, NOW())
                    ON CONFLICT (codigo_espn) DO NOTHING
                """, (nombre, codigo, pais, division))
            conn.commit()
    print(f"[db] Ligas seeded ({len(ligas)} ligas)")


# ─────────────────────────────────────────────
#  ACCIONES (original)
# ─────────────────────────────────────────────

def get_or_create_liga(codigo_espn: str, nombre: str, pais: str) -> str:
    """Retorna el uuid de la liga, creándola si no existe."""
    from uuid import uuid4
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT uuid FROM futbol_ligas WHERE codigo_espn = %s", (codigo_espn,))
            row = cur.fetchone()
            if row:
                return row[0]
            new_uuid = str(uuid4())
            cur.execute("""
                INSERT INTO futbol_ligas (uuid, nombre, codigo_espn, pais, division, activa, created_at)
                VALUES (%s, %s, %s, %s, 1, false, NOW())
            """, (new_uuid, nombre, codigo_espn, pais))
            conn.commit()
            return new_uuid


def migrate_mundial_columns() -> None:
    """Adds fase and grupo columns to futbol_partidos if they don't exist yet."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("ALTER TABLE futbol_partidos ADD COLUMN IF NOT EXISTS fase VARCHAR(30)")
            cur.execute("ALTER TABLE futbol_partidos ADD COLUMN IF NOT EXISTS grupo VARCHAR(5)")
            conn.commit()
    print("[db] Columnas fase/grupo OK")


def get_or_create_mundial_liga(codigo: str = "fifa.world", nombre: str = "FIFA World Cup 2026") -> str:
    """Returns the uuid of the World Cup liga, inserting it if it doesn't exist."""
    from uuid import uuid4
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT uuid FROM futbol_ligas WHERE codigo_espn = %s", (codigo,))
            row = cur.fetchone()
            if row:
                return row[0]
            new_uuid = str(uuid4())
            cur.execute("""
                INSERT INTO futbol_ligas (uuid, nombre, codigo_espn, pais, division, activa, created_at)
                VALUES (%s, %s, %s, 'International', 1, true, NOW())
            """, (new_uuid, nombre, codigo))
            conn.commit()
            print(f"[db] Liga '{nombre}' creada ({codigo})")
            return new_uuid


def get_all_favoritos() -> list[dict]:
    """Devuelve todos los equipos favoritos de fútbol."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT uuid, espn_team_id, espn_liga_code, team_name, liga_nombre, pais
                FROM futbol_favoritos
                ORDER BY team_name
            """)
            rows = cur.fetchall()
            return [
                {
                    "uuid": r[0], "espn_team_id": r[1], "espn_liga_code": r[2],
                    "team_name": r[3], "liga_nombre": r[4], "pais": r[5],
                }
                for r in rows
            ]


def save_earnings(accion_uuid: str, earnings_date: str, eps_estimate) -> None:
    """Upsert earnings date for an accion (one row per accion, always overwrite)."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id FROM acciones WHERE uuid = %s", (accion_uuid,))
            row = cur.fetchone()
            if not row:
                return
            accion_id = row[0]
            cur.execute("""
                INSERT INTO acciones_earnings (accion_id, earnings_date, eps_estimate, fetched_at)
                VALUES (%s, %s, %s, NOW())
                ON CONFLICT (accion_id) DO UPDATE SET
                    earnings_date = EXCLUDED.earnings_date,
                    eps_estimate  = EXCLUDED.eps_estimate,
                    fetched_at    = NOW()
            """, (accion_id, earnings_date, eps_estimate))
            conn.commit()


def get_earnings_tomorrow() -> list[dict]:
    """Returns acciones whose earnings_date is tomorrow."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT a.symbol, a.name, ae.earnings_date, ae.eps_estimate
                FROM acciones_earnings ae
                JOIN acciones a ON a.id = ae.accion_id
                WHERE ae.earnings_date = CURRENT_DATE + INTERVAL '1 day'
                ORDER BY a.symbol
            """)
            rows = cur.fetchall()
            return [
                {"symbol": r[0], "name": r[1], "earnings_date": str(r[2]), "eps_estimate": float(r[3]) if r[3] else None}
                for r in rows
            ]


def save_noticias(accion_uuid: str, change_pct: float, articles: list[dict], agent_analysis: str) -> None:
    if not articles:
        return
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id FROM acciones WHERE uuid = %s", (accion_uuid,))
            row = cur.fetchone()
            if not row:
                return
            accion_id = row[0]
            today = date.today()

            for article in articles:
                pub_date = None
                if article.get("date") and article["date"] != "unknown":
                    try:
                        pub_date = datetime.strptime(article["date"], "%Y-%m-%d").date()
                    except ValueError:
                        pass

                cur.execute("""
                    INSERT INTO acciones_noticias
                        (uuid, accion_id, date, change_pct, title, summary, url, pub_date, agent_analysis, created_at)
                    VALUES
                        (gen_random_uuid(), %s, %s, %s, %s, %s, %s, %s, %s, NOW())
                    ON CONFLICT DO NOTHING
                """, (
                    accion_id,
                    today,
                    change_pct,
                    article["title"][:500],
                    article.get("summary", "")[:1000] or None,
                    article.get("url", "")[:500] or None,
                    pub_date,
                    agent_analysis,
                ))
            conn.commit()
