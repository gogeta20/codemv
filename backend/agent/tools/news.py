import yfinance as yf
from datetime import datetime, timezone

def get_news(symbol: str, max_items: int = 5) -> dict:
    ticker = yf.Ticker(symbol.upper())
    raw = ticker.news or []

    if not raw:
        return {"symbol": symbol.upper(), "found": False, "articles": []}

    articles = []
    for item in raw[:max_items]:
        content = item.get("content", {})
        title   = content.get("title") or item.get("title", "")
        summary = content.get("summary") or ""
        url     = content.get("canonicalUrl", {}).get("url") or item.get("link", "")

        pub_ts = content.get("pubDate") or item.get("providerPublishTime")
        if isinstance(pub_ts, (int, float)):
            pub_date = datetime.fromtimestamp(pub_ts, tz=timezone.utc).strftime("%Y-%m-%d")
        elif isinstance(pub_ts, str):
            pub_date = pub_ts[:10]
        else:
            pub_date = "unknown"

        if title:
            articles.append({"title": title, "summary": summary[:300], "date": pub_date, "url": url})

    return {"symbol": symbol.upper(), "found": bool(articles), "articles": articles}
