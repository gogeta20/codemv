import sys
import os
sys.path.insert(0, os.path.dirname(__file__))

from db import get_active_acciones, save_precio
from tools.prices import get_price
from tools.telegram import send_daily_summary, send_earnings_alert
import steps.news_check as news_check
import steps.discover_movers as discover_movers
import steps.earnings_alert as earnings_alert
import steps.favorites_daily as favorites_daily


def run():
    print("\n" + "="*50)
    print("AGENTE — inicio")
    print("="*50)

    acciones = get_active_acciones()
    if not acciones:
        print("[main] No hay acciones activas.")
        return

    print(f"\n[main] {len(acciones)} acciones: {', '.join(a['symbol'] for a in acciones)}\n")

    # 1. Fetchear precios
    fetched = {}
    for accion in acciones:
        symbol = accion["symbol"]
        print(f"  → {symbol}...", end=" ", flush=True)
        result = get_price(symbol)
        if "error" in result:
            print(f"ERROR: {result['error']}")
            continue
        fetched[symbol] = {"data": result, "uuid": accion["uuid"], "threshold": accion["threshold"]}
        print(f"${result['price']} ({result['change_pct']:+.2f}%)")

    # 2. Guardar precios en DB
    print("\n[main] Guardando precios...")
    for symbol, info in fetched.items():
        save_precio(info["uuid"], info["data"])
        print(f"  ✓ {symbol}")

    # 3. Detectar movers
    movers = [
        {
            "symbol":     symbol,
            "change_pct": info["data"]["change_pct"],
            "price":      info["data"]["price"],
            "threshold":  info["threshold"],
            "uuid":       info["uuid"],
        }
        for symbol, info in fetched.items()
        if abs(info["data"]["change_pct"]) >= info["threshold"]
    ]

    # 4. Chat 1: resumen diario de precios (siempre, con o sin movers)
    summary_data = [
        {
            "symbol":     symbol,
            "change_pct": info["data"]["change_pct"],
            "price":      info["data"]["price"],
            "threshold":  info["threshold"],
        }
        for symbol, info in fetched.items()
    ]
    send_daily_summary(summary_data)

    if not movers:
        print("\n[main] Sin movimientos relevantes. Mercado tranquilo.")
        discover_movers.run()
        favorites_daily.run()
        upcoming = earnings_alert.run()
        if upcoming:
            send_earnings_alert(upcoming)
        print("="*50 + "\n")
        return

    print(f"\n[main] {len(movers)} acción(es) con alerta:")
    for m in movers:
        print(f"  ! {m['symbol']} {m['change_pct']:+.2f}%")

    # 5. Chat 3: buscar noticias — top 15 movers por magnitud, solo alertar si hay catalizador real
    top_movers = sorted(movers, key=lambda x: abs(x['change_pct']), reverse=True)[:15]
    print(f"\n[main] Analizando noticias para top {len(top_movers)} movers...")
    news_results = news_check.run(top_movers)

    if not news_results:
        print("\n[main] Movers sin noticias — no se envían alertas al chat de noticias.")
    else:
        print(f"\n[main] {len(news_results)} alerta(s) enviadas al chat de noticias.")

    # 6. Chat 2: radar de descubrimientos
    discover_movers.run()

    # 7. Favoritos: alertar si algún equipo favorito juega hoy o mañana
    favorites_daily.run()

    # 8. Earnings: actualizar fechas y alerta si hay reportes mañana (último mensaje)
    upcoming = earnings_alert.run()
    if upcoming:
        send_earnings_alert(upcoming)

    print("\n" + "="*50)
    print("AGENTE — completado")
    print("="*50 + "\n")


if __name__ == "__main__":
    run()
