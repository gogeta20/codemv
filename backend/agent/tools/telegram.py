import os
import requests
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '..', '.env'))

TOKEN            = os.getenv('TELEGRAM_TOKEN', '')
CHAT_ID          = os.getenv('TELEGRAM_CHAT_ID', '')           # Chat 1: resumen diario
DISCOVER_CHAT_ID = os.getenv('TELEGRAM_DISCOVER_CHAT_ID', '')  # Chat 2: descubrimientos
NEWS_CHAT_ID     = os.getenv('TELEGRAM_NEWS_CHAT_ID', '')      # Chat 3: noticias con catalizador

API_URL = f'https://api.telegram.org/bot{TOKEN}'


def send(text: str, parse_mode: str = 'Markdown', chat_id: str = None) -> bool:
    target = chat_id or CHAT_ID
    if not TOKEN or not target or target in ('', 'PENDIENTE'):
        print(f'[telegram] Sin configurar — mensaje no enviado a {target}:\n{text[:80]}...')
        return False
    try:
        res = requests.post(
            f'{API_URL}/sendMessage',
            json={'chat_id': target, 'text': text, 'parse_mode': parse_mode},
            timeout=10,
        )
        return res.ok
    except Exception as e:
        print(f'[telegram] Error: {e}')
        return False


def send_discover(text: str, parse_mode: str = 'Markdown') -> bool:
    """Chat 2 — descubrimientos del radar. Fallback al principal si no está configurado."""
    target = DISCOVER_CHAT_ID if DISCOVER_CHAT_ID and DISCOVER_CHAT_ID != 'PENDIENTE' else CHAT_ID
    return send(text, parse_mode, chat_id=target)


def send_news(text: str, parse_mode: str = 'Markdown') -> bool:
    """Chat 3 — solo noticias con catalizador real. Fallback al principal."""
    target = NEWS_CHAT_ID if NEWS_CHAT_ID and NEWS_CHAT_ID != 'PENDIENTE' else CHAT_ID
    return send(text, parse_mode, chat_id=target)


def send_daily_summary(results: list[dict]) -> bool:
    """Chat 1 — solo movers del día (acciones que superaron el umbral), en chunks."""
    from datetime import date
    today = date.today().strftime('%d/%m/%Y')

    movers = [r for r in results if abs(r['change_pct']) >= r.get('threshold', 5)]
    movers.sort(key=lambda x: abs(x['change_pct']), reverse=True)

    if not movers:
        return send(f'📊 *Resumen {today}*\n✅ Sin movimientos relevantes hoy.')

    header = f'📊 *Movers del día — {today}* ({len(movers)} alertas)\n'
    lines  = []
    for r in movers:
        sign = '+' if r['change_pct'] >= 0 else ''
        icon = '🟢' if r['change_pct'] >= 0 else '🔴'
        lines.append(f'{icon} *{r["symbol"]}*  `${r["price"]:.2f}`  `{sign}{r["change_pct"]:.2f}%`')

    # Partir en chunks de 4000 chars para no superar el límite de Telegram
    LIMIT  = 3800
    chunks = []
    current = header
    for line in lines:
        if len(current) + len(line) + 1 > LIMIT:
            chunks.append(current)
            current = line + '\n'
        else:
            current += line + '\n'
    if current:
        chunks.append(current)

    ok = True
    for chunk in chunks:
        ok = send(chunk) and ok
    return ok


def send_earnings_alert(upcoming: list[dict]) -> bool:
    """Chat 1 — alerta de earnings mañana. Solo se llama si hay acciones."""
    if not upcoming:
        return True
    from datetime import date, timedelta
    tomorrow = (date.today() + timedelta(days=1)).strftime('%d/%m/%Y')

    lines = [f'📢 *Reportes de ganancias mañana — {tomorrow}*\n']
    for a in upcoming:
        eps = f'  EPS est: `${a["eps_estimate"]:.2f}`' if a.get('eps_estimate') is not None else ''
        lines.append(f'• *{a["symbol"]}* — {a["name"]}{eps}')

    return send('\n'.join(lines))


def send_news_alert(symbol: str, change_pct: float, articles: list[dict], analysis: str) -> bool:
    """Chat 3 — alerta de noticia real con catalizador. Solo se llama si hay artículos."""
    direction = '🔴 bajó' if change_pct < 0 else '🟢 subió'
    lines = [f'📰 *{symbol}* {direction} `{change_pct:+.2f}%`\n']

    for a in articles[:3]:
        title = a.get('title', '')[:100]
        url   = a.get('url', '')
        lines.append(f'• [{title}]({url})' if url else f'• {title}')

    if analysis:
        lines.append(f'\n🤖 _{analysis[:300]}_')

    return send_news('\n'.join(lines))
