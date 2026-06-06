import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from datetime import date, timedelta
from db import get_active_acciones, save_earnings, get_earnings_tomorrow
from tools.prices import get_earnings_date


def run() -> list[dict]:
    """
    Fetches next earnings date for all active acciones, saves to DB.
    Returns list of acciones with earnings tomorrow (for Telegram alert).
    """
    acciones = get_active_acciones()
    print(f"\n[earnings] Verificando fechas de reporte para {len(acciones)} acciones...")

    tomorrow = (date.today() + timedelta(days=1)).isoformat()

    for accion in acciones:
        symbol = accion['symbol']
        result = get_earnings_date(symbol)
        if result is None:
            print(f"  · {symbol}: sin fecha disponible")
            continue
        save_earnings(accion['uuid'], result['earnings_date'], result['eps_estimate'])
        marker = ' ⚠️  MAÑANA' if result['earnings_date'] == tomorrow else ''
        print(f"  · {symbol}: {result['earnings_date']}{marker}")

    upcoming = get_earnings_tomorrow()
    print(f"[earnings] {len(upcoming)} acción(es) reportan mañana.")
    return upcoming
