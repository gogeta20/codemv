import yfinance as yf
from datetime import date

def get_earnings_date(symbol: str) -> dict | None:
    """Returns {'earnings_date': 'YYYY-MM-DD', 'eps_estimate': float|None} or None if unavailable."""
    try:
        ticker = yf.Ticker(symbol.upper())
        cal = ticker.calendar
        if not cal or 'Earnings Date' not in cal:
            return None
        raw = cal['Earnings Date']
        if isinstance(raw, list):
            raw = raw[0] if raw else None
        if raw is None:
            return None
        # calendar returns datetime.date objects directly
        if hasattr(raw, 'isoformat'):
            earnings_date = raw.isoformat()
        else:
            return None
        eps_raw = cal.get('Earnings Average') or cal.get('EPS Estimate')
        try:
            eps_value = float(eps_raw) if eps_raw is not None else None
        except (TypeError, ValueError):
            eps_value = None
        return {'earnings_date': earnings_date, 'eps_estimate': eps_value}
    except Exception:
        return None

def get_price(symbol: str) -> dict:
    data = yf.Ticker(symbol.upper())
    hist = data.history(period="5d")
    if hist.empty or len(hist) < 1:
        return {"error": f"No data for {symbol}"}
    current    = float(hist["Close"].iloc[-1])
    prev_close = float(hist["Close"].iloc[-2]) if len(hist) >= 2 else current
    open_      = float(hist["Open"].iloc[-1])
    high       = float(hist["High"].iloc[-1])
    low        = float(hist["Low"].iloc[-1])
    volume     = int(hist["Volume"].iloc[-1])
    change_pct    = round(((current - prev_close) / prev_close) * 100, 4)
    change_amount = round(current - prev_close, 4)
    return {
        "symbol":        symbol.upper(),
        "price":         round(current, 4),
        "open":          round(open_, 4),
        "high":          round(high, 4),
        "low":           round(low, 4),
        "volume":        volume,
        "prev_close":    round(prev_close, 4),
        "change_pct":    change_pct,
        "change_amount": change_amount,
    }
