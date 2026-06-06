import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

import requests
from db import get_active_acciones, save_precio
from tools.prices import get_price

OLLAMA_URL = "http://localhost:11434/api/chat"
MODEL = "llama3.2:3b"

SYSTEM_PROMPT = """Eres un agente de trading especializado en descubrir empresas antes de que sean famosas.
Recibirás datos reales de precios ya obtenidos. Analiza y sé conciso:
- Para cada acción: precio actual, cambio % y si merece atención
- Marca con ALERTA si el cambio supera el umbral indicado
- Si no hay alertas, di que el mercado está tranquilo"""


def run():
    acciones = get_active_acciones()
    if not acciones:
        print("[morning] No hay acciones activas en la DB.")
        return

    print(f"\n{'='*50}")
    print(f"[morning_analysis] Obteniendo precios: {', '.join(a['symbol'] for a in acciones)}")
    print(f"{'='*50}\n")

    # Fetch directo en Python — más fiable que dejar al modelo 3B decidir los args
    fetched = {}
    for accion in acciones:
        symbol = accion["symbol"]
        print(f"  → {symbol}...", end=" ", flush=True)
        result = get_price(symbol)
        if "error" in result:
            print(f"ERROR: {result['error']}")
        else:
            fetched[symbol] = {"data": result, "uuid": accion["uuid"], "threshold": accion["threshold"]}
            print(f"${result['price']} ({result['change_pct']:+.2f}%)")

    if not fetched:
        print("\n[morning] No se pudieron obtener precios.")
        return

    # Armar contexto para el modelo
    lines = []
    for symbol, info in fetched.items():
        d = info["data"]
        lines.append(
            f"{symbol}: precio=${d['price']} | cambio={d['change_pct']:+.2f}% (${d['change_amount']:+.4f}) "
            f"| open={d['open']} high={d['high']} low={d['low']} vol={d['volume']:,} "
            f"| umbral de alerta: {info['threshold']}%"
        )

    context = "\n".join(lines)
    user_message = f"Datos de hoy:\n{context}\n\nDame el análisis matutino."

    print(f"\n[morning_analysis] Enviando datos al agente...\n")

    response = requests.post(
        OLLAMA_URL,
        json={
            "model": MODEL,
            "messages": [
                {"role": "system", "content": SYSTEM_PROMPT},
                {"role": "user", "content": user_message},
            ],
            "stream": False,
        },
    )
    response.raise_for_status()
    analysis = response.json()["message"]["content"]
    print(f"[Agente]\n{analysis}\n")

    # Guardar precios en DB
    print("[morning_analysis] Guardando precios en DB...")
    for symbol, info in fetched.items():
        save_precio(info["uuid"], info["data"])
        print(f"  ✓ {symbol} guardado")

    print("\n[morning_analysis] Completado.\n")


if __name__ == "__main__":
    run()
