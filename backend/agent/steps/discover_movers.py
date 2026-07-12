import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from dotenv import load_dotenv
load_dotenv(os.path.join(os.path.dirname(__file__), '..', '.env'))

import yfinance as yf
from db import get_active_acciones, save_precio
from tools.news import get_news
from tools.telegram import send_discover as send

MIN_PRICE      = 1.0       # ignora penny stocks
MIN_CHANGE_PCT = 5.0       # solo movimientos relevantes
MIN_VOLUME     = 500_000   # ignora stocks ilíquidos
MAX_RESULTS    = 50        # cuántos gainers analizar

INVESTIGAR_UUID = os.getenv('PORTAFOLIO_INVESTIGAR_UUID', '')


def get_known_symbols() -> set:
    return {a["symbol"] for a in get_active_acciones()}


def fetch_gainers() -> list[dict]:
    try:
        result = yf.screen('day_gainers', count=MAX_RESULTS)
        return result.get('quotes', [])
    except Exception as e:
        print(f"  [discover] Error fetching gainers: {e}")
        return []


def fetch_losers() -> list[dict]:
    try:
        result = yf.screen('day_losers', count=MAX_RESULTS)
        return result.get('quotes', [])
    except Exception as e:
        print(f"  [discover] Error fetching losers: {e}")
        return []


def save_discovered(symbol: str, name: str) -> str | None:
    """Guarda la acción descubierta en la DB. Retorna el UUID o None si falla."""
    import psycopg2, os
    from datetime import datetime
    import uuid as uuid_lib

    try:
        import yfinance as yf
        info     = yf.Ticker(symbol).info
        sector   = info.get('sector') or None
        industry = info.get('industry') or None
        exchange = info.get('fullExchangeName') or info.get('exchange') or None
        atype    = 'etf' if info.get('quoteType') == 'ETF' else 'stock'
    except Exception:
        sector = industry = exchange = None
        atype  = 'stock'

    new_uuid = str(uuid_lib.uuid4())
    try:
        conn = psycopg2.connect(
            host=os.getenv("DB_HOST", "localhost"),
            port=int(os.getenv("DB_PORT", 25432)),
            dbname=os.getenv("DB_NAME", "codemv"),
            user=os.getenv("DB_USER", "codemv"),
            password=os.getenv("DB_PASSWORD", "codemv_pass"),
        )
        cur = conn.cursor()
        cur.execute("""
            INSERT INTO acciones (uuid, symbol, name, type, source, is_active, alert_threshold_pct, exchange, sector, industry, created_at, updated_at)
            VALUES (%s, %s, %s, %s, 'discovered', true, 5.0, %s, %s, %s, NOW(), NOW())
            ON CONFLICT (symbol) DO NOTHING
        """, (new_uuid, symbol, name[:100], atype, exchange, sector, industry))
        conn.commit()

        # Verificar si se insertó (podría existir ya con otro uuid)
        cur.execute("SELECT uuid FROM acciones WHERE symbol = %s", (symbol,))
        row = cur.fetchone()
        conn.close()
        return row[0] if row else None
    except Exception as e:
        print(f"  [discover] DB error saving {symbol}: {e}")
        return None


def add_to_investigar(accion_uuid: str) -> None:
    if not INVESTIGAR_UUID:
        return
    try:
        import psycopg2, uuid as uuid_lib
        conn = psycopg2.connect(
            host=os.getenv("DB_HOST", "localhost"),
            port=int(os.getenv("DB_PORT", 25432)),
            dbname=os.getenv("DB_NAME", "codemv"),
            user=os.getenv("DB_USER", "codemv"),
            password=os.getenv("DB_PASSWORD", "codemv_pass"),
        )
        cur = conn.cursor()
        cur.execute("SELECT id FROM portafolios WHERE uuid = %s", (INVESTIGAR_UUID,))
        row = cur.fetchone()
        if not row:
            conn.close()
            return
        portafolio_id = row[0]

        cur.execute("SELECT id FROM acciones WHERE uuid = %s", (accion_uuid,))
        row = cur.fetchone()
        if not row:
            conn.close()
            return
        accion_id = row[0]

        cur.execute("""
            INSERT INTO portafolio_acciones
                (uuid, portafolio_id, accion_id, status, created_at, updated_at)
            VALUES (%s, %s, %s, 'watchlist', NOW(), NOW())
            ON CONFLICT (portafolio_id, accion_id) DO NOTHING
        """, (str(uuid_lib.uuid4()), portafolio_id, accion_id))
        conn.commit()
        conn.close()
    except Exception as e:
        print(f"  [discover] Error añadiendo a portafolio: {e}")


def build_telegram_message(symbol: str, name: str, change_pct: float, price: float, articles: list) -> str:
    direction = '🟢' if change_pct > 0 else '🔴'
    sign      = '+' if change_pct > 0 else ''
    lines = [
        f'{direction} *Nueva: {symbol}* `{sign}{change_pct:.2f}%`',
        f'_{name}_\n',
    ]
    if articles:
        lines.append('📰 Por qué se mueve:')
        for a in articles[:3]:
            title = a.get('title', '')[:90]
            url   = a.get('url', '')
            lines.append(f'• [{title}]({url})' if url else f'• {title}')
    else:
        lines.append('_Sin noticias encontradas — movimiento técnico_')
    return '\n'.join(lines)


def run() -> list[dict]:
    print(f"\n{'='*50}")
    print("[discover_movers] Buscando oportunidades en el mercado...")
    print(f"{'='*50}")

    known = get_known_symbols()
    print(f"  Símbolos en seguimiento: {len(known)}")

    # Busca tanto gainers como losers extremos
    quotes = fetch_gainers() + fetch_losers()
    discovered = []

    for q in quotes:
        symbol = q.get('symbol', '')
        price  = q.get('regularMarketPrice', 0)
        pct    = q.get('regularMarketChangePercent', 0)
        name   = q.get('shortName') or q.get('longName') or symbol
        vol    = q.get('regularMarketVolume', 0)

        # Filtros objetivos
        if not symbol or '.' in symbol:  # ignora símbolos extranjeros (NKE.F, etc.)
            continue
        if price < MIN_PRICE:
            continue
        if abs(pct) < MIN_CHANGE_PCT:
            continue
        if vol < MIN_VOLUME:
            continue
        if symbol in known:
            continue

        print(f"\n  🔍 NUEVA: {symbol} ({name}) {pct:+.2f}% @ ${price:.2f}")

        # Guarda en DB
        uuid = save_discovered(symbol, name)
        if not uuid:
            print(f"     → No se pudo guardar en DB")
            continue

        # Guarda precio usando los datos del screener (ya disponibles, sin segunda llamada a yfinance)
        prev_close = q.get('regularMarketPreviousClose') or price
        price_payload = {
            "price":         round(float(price), 4),
            "open":          q.get('regularMarketOpen'),
            "high":          q.get('regularMarketDayHigh'),
            "low":           q.get('regularMarketDayLow'),
            "volume":        int(vol),
            "prev_close":    round(float(prev_close), 4) if prev_close else None,
            "change_pct":    round(float(pct), 4),
            "change_amount": round(float(price - prev_close), 4) if prev_close else None,
        }
        try:
            save_precio(uuid, price_payload)
            print(f"     → Precio guardado: ${price:.2f} ({pct:+.2f}%)")
        except Exception as e:
            print(f"     → Error guardando precio: {e}")

        # Busca noticias
        news_data = get_news(symbol, max_items=5)
        articles  = news_data.get('articles', [])

        # Añadir al portafolio "investigar"
        add_to_investigar(uuid)
        print(f"     → Añadida a portafolio 'investigar'")

        # Telegram al chat de descubrimientos
        msg = build_telegram_message(symbol, name, pct, price, articles)
        send(msg)
        print(f"     → Alerta enviada a Telegram")

        discovered.append({
            'symbol':     symbol,
            'name':       name,
            'change_pct': pct,
            'price':      price,
            'volume':     vol,
            'uuid':       uuid,
        })

        known.add(symbol)  # evita duplicados si aparece en gainers y losers

    if not discovered:
        print("\n  Sin nuevas oportunidades hoy.")
    else:
        print(f"\n  {len(discovered)} nueva(s) acción(es) descubierta(s).")

    return discovered


if __name__ == "__main__":
    found = run()
    if found:
        print("\nResumen:")
        for f in found:
            print(f"  {f['symbol']:8} {f['change_pct']:+.2f}%  ${f['price']:.2f}  {f['name']}")
