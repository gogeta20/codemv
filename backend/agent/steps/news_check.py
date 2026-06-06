import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

import requests
from tools.news import get_news
from tools.telegram import send_news_alert
from db import save_noticias

OLLAMA_URL = "http://localhost:11434/api/chat"
MODEL      = "llama3.2:3b"
OLLAMA_TIMEOUT = 25  # segundos — si tarda más, manda solo titulares

SYSTEM_PROMPT = """Eres un agente de trading. Recibirás noticias recientes de una acción que movió más del 5%.
REGLAS ESTRICTAS:
1. Lee TODOS los titulares antes de responder
2. Si algún titular menciona: socio nuevo, contrato, earnings, upgrade/downgrade, regulación, fusión, adquisición → ese ES el catalizador, explícalo en 2-3 líneas
3. Si hay un rally fuerte los días previos y hoy baja sin noticia negativa → es corrección post-rally, dilo
4. Máximo 3 líneas. Sin saludos, sin rodeos."""


def _ollama_analyze(symbol: str, change_pct: float, articles: list) -> str:
    """Intenta análisis con Ollama. Retorna '' si falla o tarda demasiado."""
    from datetime import date
    today_str  = date.today().strftime("%Y-%m-%d")
    direction  = "subió" if change_pct > 0 else "bajó"
    today_news = [a for a in articles if a["date"] == today_str]
    prev_news  = [a for a in articles if a["date"] != today_str]

    lines = [f"SITUACIÓN: {symbol} {direction} {abs(change_pct):.2f}% HOY ({today_str})."]
    if today_news:
        lines.append("\nNOTICIAS DE HOY:")
        for a in today_news:
            lines.append(f"- {a['title']}")
    else:
        lines.append("\nNOTICIAS DE HOY: Ninguna.")
    if prev_news:
        lines.append("\nNOTICIAS RECIENTES:")
        for a in prev_news[:3]:
            lines.append(f"- [{a['date']}] {a['title']}")
    lines.append(f"\nPREGUNTA: ¿Por qué {direction} {symbol} hoy? Máximo 3 líneas.")

    try:
        res = requests.post(
            OLLAMA_URL,
            json={
                "model": MODEL,
                "messages": [
                    {"role": "system", "content": SYSTEM_PROMPT},
                    {"role": "user",   "content": "\n".join(lines)},
                ],
                "stream": False,
            },
            timeout=OLLAMA_TIMEOUT,
        )
        res.raise_for_status()
        return res.json()["message"]["content"]
    except Exception:
        return ""


def run(movers: list[dict]) -> dict[str, dict]:
    """
    Para cada mover:
      1. Busca noticias (rápido)
      2. Si hay artículos → envía alerta a Chat 3 inmediatamente con los titulares
      3. Intenta análisis Ollama (25s timeout) → si responde, envía seguimiento
      4. Guarda en DB
    Retorna dict symbol → {"analysis": str, "articles": list}
    """
    results = {}

    for mover in movers:
        symbol     = mover["symbol"]
        change_pct = mover["change_pct"]
        uuid       = mover.get("uuid", "")

        print(f"\n  [news] {symbol} ({change_pct:+.2f}%) — buscando noticias...", end=" ", flush=True)
        news_data = get_news(symbol)
        articles  = news_data.get("articles", []) if news_data.get("found") else []

        if not articles:
            print("sin noticias, omitida")
            continue

        print(f"{len(articles)} artículos")

        # Enviar alerta inmediata con titulares (sin esperar Ollama)
        send_news_alert(symbol, change_pct, articles, analysis="")
        print(f"  → Alerta enviada a Chat 3")

        # Intentar análisis Ollama (no bloqueante si tarda)
        print(f"  → Analizando con Ollama ({OLLAMA_TIMEOUT}s timeout)...", end=" ", flush=True)
        analysis = _ollama_analyze(symbol, change_pct, articles)
        if analysis:
            print("OK")
            # Enviar seguimiento con el análisis
            from tools.telegram import send_news
            direction = '🟢 subió' if change_pct > 0 else '🔴 bajó'
            send_news(f'🤖 *{symbol}* {direction} — análisis:\n_{analysis[:400]}_')
        else:
            print("sin respuesta — solo titulares enviados")

        # Guardar en DB
        if uuid:
            save_noticias(uuid, change_pct, articles, analysis)
            print(f"  → {len(articles)} noticias guardadas en DB")

        results[symbol] = {"analysis": analysis, "articles": articles}

    return results


if __name__ == "__main__":
    from db import get_active_acciones
    acciones = {a["symbol"]: a["uuid"] for a in get_active_acciones()}
    movers = [
        {"symbol": "IREN", "change_pct": 10.63, "uuid": acciones.get("IREN", "")},
        {"symbol": "KEEL", "change_pct":  8.62, "uuid": acciones.get("KEEL", "")},
    ]
    run(movers)
