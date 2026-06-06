"""
Lookup server — corre en localhost:5001
El front Vue lo llama directamente para evitar restricciones de Docker.
Arrancar: python3 lookup_server.py
"""
from flask import Flask, request, jsonify
from flask_cors import CORS
import yfinance as yf
from datetime import date, timedelta

app = Flask(__name__)
CORS(app)

TYPE_MAP = {
    'ETF':            'etf',
    'CRYPTOCURRENCY': 'crypto',
    'EQUITY':         'stock',
}

@app.route('/lookup')
def lookup():
    q = request.args.get('q', '').strip()
    if not q:
        return jsonify({'results': []})

    try:
        import requests as req
        res = req.get(
            'https://query1.finance.yahoo.com/v1/finance/search',
            params={'q': q, 'quotesCount': 8, 'newsCount': 0, 'listsCount': 0},
            headers={'User-Agent': 'Mozilla/5.0'},
            timeout=5,
        )
        quotes = res.json().get('quotes', [])
    except Exception as e:
        return jsonify({'error': str(e)}), 502

    results = []
    for qt in quotes:
        qtype = qt.get('quoteType', '')
        if qtype not in TYPE_MAP:
            continue
        results.append({
            'symbol':   qt['symbol'],
            'name':     qt.get('longname') or qt.get('shortname') or qt['symbol'],
            'exchange': qt.get('exchDisp') or qt.get('exchange'),
            'type':     TYPE_MAP[qtype],
            'sector':   None,
            'industry': None,
        })

    for r in results[:4]:
        try:
            info = yf.Ticker(r['symbol']).info
            r['sector']   = info.get('sector') or None
            r['industry'] = info.get('industry') or None
        except Exception:
            pass

    return jsonify({'results': results})


@app.route('/price')
def price():
    symbol = request.args.get('symbol', '').strip().upper()
    uuid   = request.args.get('uuid', '').strip()
    if not symbol:
        return jsonify({'error': 'symbol required'}), 400

    try:
        from tools.prices import get_price
        data = get_price(symbol)
        if 'error' in data:
            return jsonify({'error': data['error']}), 404

        if uuid:
            from db import save_precio
            save_precio(uuid, data)

        return jsonify(data)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/description')
def description():
    symbol = request.args.get('symbol', '').strip().upper()
    if not symbol:
        return jsonify({'error': 'symbol required'}), 400

    try:
        info = yf.Ticker(symbol).info
        return jsonify({
            'symbol':      symbol,
            'name':        info.get('longName') or info.get('shortName'),
            'summary':     info.get('longBusinessSummary'),
            'website':     info.get('website'),
            'employees':   info.get('fullTimeEmployees'),
            'country':     info.get('country'),
            'city':        info.get('city'),
            'sector':      info.get('sector'),
            'industry':    info.get('industry'),
            'market_cap':  info.get('marketCap'),
            'currency':    info.get('currency'),
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/history')
def history():
    symbol = request.args.get('symbol', '').strip().upper()
    if not symbol:
        return jsonify({'error': 'symbol required'}), 400

    try:
        ticker = yf.Ticker(symbol)
        hist   = ticker.history(period='1y')

        if hist.empty:
            return jsonify({'error': f'No history for {symbol}'}), 404

        today = date.today()

        def closest_price(target_date):
            # Busca el cierre más cercano a la fecha objetivo (hacia atrás)
            for offset in range(0, 10):
                d = target_date - timedelta(days=offset)
                key = str(d)
                matches = [
                    float(hist['Close'].iloc[i])
                    for i in range(len(hist))
                    if hist.index[i].date() == d
                ]
                if matches:
                    return round(matches[-1], 4), str(d)
            return None, None

        price_today, date_today   = closest_price(today)
        price_1w,    date_1w      = closest_price(today - timedelta(days=7))
        price_1m,    date_1m      = closest_price(today - timedelta(days=30))
        price_1y,    date_1y      = closest_price(today - timedelta(days=365))

        def pct_change(base, ref):
            if base is None or ref is None or ref == 0:
                return None
            return round(((base - ref) / ref) * 100, 2)

        return jsonify({
            'symbol': symbol,
            'periods': [
                {
                    'label':      'Hoy',
                    'date':       date_today,
                    'price':      price_today,
                    'change_pct': None,
                },
                {
                    'label':      '1 semana',
                    'date':       date_1w,
                    'price':      price_1w,
                    'change_pct': pct_change(price_today, price_1w),
                },
                {
                    'label':      '1 mes',
                    'date':       date_1m,
                    'price':      price_1m,
                    'change_pct': pct_change(price_today, price_1m),
                },
                {
                    'label':      '1 año',
                    'date':       date_1y,
                    'price':      price_1y,
                    'change_pct': pct_change(price_today, price_1y),
                },
            ]
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/news')
def news():
    symbol = request.args.get('symbol', '').strip().upper()
    if not symbol:
        return jsonify({'error': 'symbol required'}), 400

    try:
        from tools.news import get_news
        data = get_news(symbol, max_items=10)
        return jsonify(data)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


if __name__ == '__main__':
    print('Lookup server en http://localhost:5001')
    app.run(port=5001, debug=False)
