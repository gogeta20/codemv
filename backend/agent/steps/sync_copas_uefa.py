#!/usr/bin/env python3
import argparse
import html
import json
import re
import unicodedata
import uuid
from dataclasses import dataclass
from datetime import date, datetime, time, timedelta
from pathlib import Path
from typing import Optional
from urllib.request import Request, urlopen
from zoneinfo import ZoneInfo

import psycopg2
from psycopg2.extras import Json

try:
    from dotenv import load_dotenv
except ImportError:  # pragma: no cover
    load_dotenv = None


ROOT = Path(__file__).resolve().parents[3]
AGENT_ENV = ROOT / "backend" / "agent" / ".env"
CLUB_CATALOG = ROOT / "backend" / "symfony" / "src" / "Futbol" / "Application" / "Copa" / "Support" / "UefaClubCountryCatalog.php"
STRENGTH_CATALOG = ROOT / "backend" / "symfony" / "src" / "Futbol" / "Application" / "Copa" / "Support" / "UefaAssociationStrengthCatalog.php"

COMPETITIONS = {
    "uefa.champions": {
        "label": "UEFA Champions League",
        "url": "https://www.uefa.com/uefachampionsleague/news/02a6-20e5a8be4e63-ae971c582f8c-1000--champions-league-qualifying-fixtures-dates-how-it-works/",
    },
    "uefa.europa": {
        "label": "UEFA Europa League",
        "url": "https://www.uefa.com/uefaeuropaleague/news/02a6-20e5db0029dd-8241a8d00925-1000--europa-league-qualifying-fixtures-results-dates-how-it-works/",
    },
    "uefa.europa.conf": {
        "label": "UEFA Conference League",
        "url": "https://www.uefa.com/uefaconferenceleague/news/02a6-20e5e911587f-cc10425958b3-1000--conference-league-qualifying-fixtures-dates-how-it-works/",
    },
}

PATH_HEADINGS = {"Champions path", "League path", "Main path"}
MADRID_TZ = ZoneInfo("Europe/Madrid")
DAY_NAMES = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
MONTH_NAMES = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December",
]

DB_CONFIG = {
    "host": "localhost",
    "port": 25432,
    "dbname": "codemv",
    "user": "codemv",
    "password": "codemv_pass",
}


@dataclass
class ParsedMatch:
    local_name: str
    away_name: str
    local_score: Optional[int]
    away_score: Optional[int]
    match_time: Optional[str]
    status: dict


def bootstrap_env() -> None:
    if load_dotenv and AGENT_ENV.exists():
        load_dotenv(AGENT_ENV)

    import os

    DB_CONFIG["host"] = os.getenv("DB_HOST", DB_CONFIG["host"])
    DB_CONFIG["port"] = int(os.getenv("DB_PORT", DB_CONFIG["port"]))
    DB_CONFIG["dbname"] = os.getenv("DB_NAME", DB_CONFIG["dbname"])
    DB_CONFIG["user"] = os.getenv("DB_USER", DB_CONFIG["user"])
    DB_CONFIG["password"] = os.getenv("DB_PASSWORD", DB_CONFIG["password"])


def normalize_key(value: Optional[str]) -> str:
    text = html.unescape((value or "").strip())
    text = text.replace("\ufeff", " ").replace("\u200b", " ")
    text = (
        text.replace("ø", "o").replace("Ø", "O")
        .replace("ł", "l").replace("Ł", "L")
        .replace("đ", "d").replace("Đ", "D")
        .replace("ß", "ss")
        .replace("æ", "ae").replace("Æ", "Ae")
        .replace("œ", "oe").replace("Œ", "Oe")
    )
    text = unicodedata.normalize("NFKD", text)
    text = text.encode("ascii", "ignore").decode("ascii")
    text = text.lower()
    text = re.sub(r"[^a-z0-9]+", " ", text)
    return re.sub(r"\s+", " ", text).strip()


def load_club_catalog() -> dict[str, dict]:
    text = CLUB_CATALOG.read_text()
    pattern = re.compile(
        r"'(?P<club>[^']+)'\s*=>\s*\['country'\s*=>\s*'(?P<country>[^']*)',\s*'league_label'\s*=>\s*'(?P<league>[^']*)'\]"
    )
    catalog = {}
    for match in pattern.finditer(text):
        catalog[match.group("club")] = {
            "country": match.group("country") or None,
            "league_label": match.group("league") or "Liga domestica",
        }
    return catalog


def load_strength_catalog() -> dict[str, dict]:
    text = STRENGTH_CATALOG.read_text()
    pattern = re.compile(
        r"'(?P<country>[^']+)'\s*=>\s*\['rank'\s*=>\s*(?P<rank>\d+),\s*'score'\s*=>\s*(?P<score>\d+),\s*'label'\s*=>\s*'(?P<label>[^']+)'\]"
    )
    catalog = {}
    for match in pattern.finditer(text):
        catalog[match.group("country")] = {
            "rank": int(match.group("rank")),
            "score": int(match.group("score")),
            "label": match.group("label"),
        }
    return catalog


def fetch_html(url: str) -> str:
    request = Request(
        url,
        headers={
            "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language": "en-US,en;q=0.9",
        },
    )
    with urlopen(request, timeout=20) as response:
        return response.read().decode("utf-8", "ignore")


def extract_lines(raw_html: str) -> list[str]:
    without_blocks = re.sub(r"<(script|style)\b[^>]*>.*?</\1>", " ", raw_html, flags=re.I | re.S)
    text = re.sub(r"<[^>]+>", "\n", without_blocks)
    text = html.unescape(text).replace("\xa0", " ").replace("\ufeff", " ").replace("\u200b", " ")
    lines = []
    for line in text.splitlines():
        compact = re.sub(r"\s+", " ", line).strip()
        if compact:
            lines.append(compact)
    return lines


def build_day_heading(target: date) -> str:
    return f"{DAY_NAMES[target.weekday()]} {target.day} {MONTH_NAMES[target.month - 1]}"


def is_stage_heading(line: str) -> bool:
    return bool(re.match(r"^(First|Second|Third) qualifying round$", line)) or line == "Play-off round"


def is_day_heading(line: str) -> bool:
    return bool(re.match(r"^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday) \d{1,2} [A-Z][a-z]+$", line))


def should_skip(line: str) -> bool:
    return line in {"All kick-off times CET", "Kick-off times in CET", "First legs", "Second legs", "How does it work?", "How did it work?"}


def parse_match_line(line: str) -> Optional[ParsedMatch]:
    scheduled = re.match(r"^(?P<local>.+?) vs (?P<away>.+?) \((?P<time>\d{1,2}:\d{2})\)$", line)
    if scheduled:
        hhmm = scheduled.group("time")
        return ParsedMatch(
            local_name=scheduled.group("local").strip(),
            away_name=scheduled.group("away").strip(),
            local_score=None,
            away_score=None,
            match_time=hhmm,
            status={
                "state": "pre",
                "completed": False,
                "description": "Pendiente",
                "detail": f"{hhmm} CET",
                "short_detail": hhmm,
            },
        )

    result = re.match(r"^(?P<local>.+?) (?P<ls>\d+)-(?P<as>\d+)(?:aet)? (?P<away>.+?)(?: \((?P<extra>.+)\))?$", line)
    if result:
        return ParsedMatch(
            local_name=result.group("local").strip(),
            away_name=result.group("away").strip(),
            local_score=int(result.group("ls")),
            away_score=int(result.group("as")),
            match_time=None,
            status={
                "state": "post",
                "completed": True,
                "description": "Finalizado",
                "detail": result.group("extra") or "Final",
                "short_detail": "Final",
            },
        )

    plain = re.match(r"^(?P<local>.+?) vs (?P<away>.+)$", line)
    if plain:
        return ParsedMatch(
            local_name=plain.group("local").strip(),
            away_name=plain.group("away").strip(),
            local_score=None,
            away_score=None,
            match_time=None,
            status={
                "state": "pre",
                "completed": False,
                "description": "Pendiente",
                "detail": "Horario pendiente",
                "short_detail": "Pend.",
            },
        )

    return None


def strength_for_country(country: Optional[str], strength_catalog: dict[str, dict]) -> dict:
    normalized = "Unknown" if not country else {"Türkiye": "Turkey"}.get(country, country)
    base = strength_catalog.get(normalized, {"rank": 99, "score": 15, "label": "desconocida"})
    return {"country": normalized, **base}


def confidence_label(diff: int) -> str:
    if diff >= 20:
        return "muy alta"
    if diff >= 12:
        return "alta"
    if diff >= 7:
        return "media"
    if diff >= 3:
        return "baja"
    return "muy baja"


def compare_strength(local_country: Optional[str], away_country: Optional[str], strength_catalog: dict[str, dict]) -> dict:
    local = strength_for_country(local_country, strength_catalog)
    away = strength_for_country(away_country, strength_catalog)
    diff = local["score"] - away["score"]
    if diff == 0:
        summary = f"Fuerza pareja: {local['country']} y {away['country']} estan en el mismo escalon UEFA."
        favorite = "even"
    elif diff > 0:
        summary = f"{local['country']} parte por encima de {away['country']} por fuerza estructural de liga."
        favorite = "local"
    else:
        summary = f"{away['country']} parte por encima de {local['country']} por fuerza estructural de liga."
        favorite = "visitante"

    return {
        "local": local,
        "visitante": away,
        "diff": diff,
        "favorite_side": favorite,
        "confidence": confidence_label(abs(diff)),
        "summary": summary,
    }


def map_club(name: str, score: Optional[int], club_catalog: dict[str, dict], strength_catalog: dict[str, dict]) -> dict:
    club = club_catalog.get(normalize_key(name), {"country": None, "league_label": "Liga domestica"})
    strength = strength_for_country(club.get("country"), strength_catalog)
    return {
        "team_id": None,
        "name": name,
        "short_name": name,
        "abbreviation": "",
        "logo": None,
        "form": None,
        "home_away": None,
        "winner": None,
        "score": score,
        "shootout_score": None,
        "domestic": {
            "country": club.get("country"),
            "league_code": None,
            "league_label": club.get("league_label", "Liga domestica"),
            "strength_rank": strength["rank"],
            "strength_score": strength["score"],
            "strength_label": strength["label"],
        },
    }


def slugify(text: str) -> str:
    normalized = normalize_key(text)
    return normalized.replace(" ", "-")


def season_from_date(target: date) -> str:
    return f"{target.year}/{target.year + 1}" if target.month >= 7 else f"{target.year - 1}/{target.year}"


def build_event_datetime(target: date, hhmm: Optional[str]) -> str:
    if hhmm:
        hours, minutes = [int(x) for x in hhmm.split(":")]
    else:
        hours, minutes = 0, 0
    return datetime.combine(target, time(hours, minutes), MADRID_TZ).isoformat()


def build_payload(competition_code: str, competition_label: str, article_url: str, target: date, stage: Optional[str], path: Optional[str], parsed: ParsedMatch, club_catalog: dict[str, dict], strength_catalog: dict[str, dict]) -> dict:
    local = map_club(parsed.local_name, parsed.local_score, club_catalog, strength_catalog)
    away = map_club(parsed.away_name, parsed.away_score, club_catalog, strength_catalog)
    strength = compare_strength(local["domestic"]["country"], away["domestic"]["country"], strength_catalog)
    event_name = f"{local['name']} vs {away['name']}"
    return {
        "event_id": f"{target.strftime('%Y%m%d')}--{slugify(event_name)}",
        "competition": {
            "code": competition_code,
            "label": competition_label,
        },
        "name": event_name,
        "short_name": event_name,
        "date": build_event_datetime(target, parsed.match_time),
        "status": parsed.status,
        "venue": {
            "name": None,
            "city": None,
            "country": None,
        },
        "local": local,
        "visitante": away,
        "strength": strength,
        "meta": {
            "stage": stage,
            "path": path,
            "source_url": article_url,
        },
        "details": {
            "headline": event_name,
            "note": "Fuente oficial UEFA qualifying news",
            "season": season_from_date(target),
            "venue": {
                "name": None,
                "city": None,
                "country": None,
            },
            "stage": stage,
            "path": path,
            "source_url": article_url,
        },
    }


def should_keep_payload(payload: dict) -> bool:
    local_name = str(payload["local"]["name"])
    away_name = str(payload["visitante"]["name"])
    local_country = payload["local"]["domestic"].get("country")
    away_country = payload["visitante"]["domestic"].get("country")

    if any(token in local_name or token in away_name for token in ["/", "winner", "loser"]):
        return False

    if local_country is None or away_country is None:
        return False

    return True


def extract_matches_for_date(competition_code: str, target: date, club_catalog: dict[str, dict], strength_catalog: dict[str, dict]) -> list[dict]:
    meta = COMPETITIONS[competition_code]
    lines = extract_lines(fetch_html(meta["url"]))
    target_heading = build_day_heading(target)
    stage = None
    path = None
    current_day = None
    items = []

    index = 0
    while index < len(lines):
        line = lines[index]

        if is_stage_heading(line):
            stage = line
            path = None
            index += 1
            continue

        if line in PATH_HEADINGS:
            path = line
            index += 1
            continue

        if is_day_heading(line):
            if current_day == target_heading and line != target_heading:
                break
            current_day = line
            index += 1
            continue

        if current_day != target_heading or should_skip(line):
            index += 1
            continue

        candidate = line
        if index + 1 < len(lines) and re.match(r"^\(\d{1,2}:\d{2}\)$", lines[index + 1]):
            candidate += f" {lines[index + 1]}"
            index += 1

        parsed = parse_match_line(candidate)
        if parsed:
            payload = build_payload(
                competition_code,
                meta["label"],
                meta["url"],
                target,
                stage,
                path,
                parsed,
                club_catalog,
                strength_catalog,
            )
            if should_keep_payload(payload):
                items.append(payload)

        index += 1

    return items


def save_matches(target: date, matches: list[dict]) -> None:
    with psycopg2.connect(**DB_CONFIG) as conn:
        with conn.cursor() as cur:
            cur.execute("DELETE FROM futbol_copa_partidos WHERE fecha = %s", (target,))
            for match in matches:
                cur.execute(
                    """
                    INSERT INTO futbol_copa_partidos (
                        uuid, competition_code, event_id, fecha, hora_utc,
                        equipo_local, equipo_visitante, payload, created_at, updated_at
                    ) VALUES (
                        %s, %s, %s, %s, %s,
                        %s, %s, %s, NOW(), NOW()
                    )
                    ON CONFLICT (competition_code, event_id) DO UPDATE SET
                        fecha = EXCLUDED.fecha,
                        hora_utc = EXCLUDED.hora_utc,
                        equipo_local = EXCLUDED.equipo_local,
                        equipo_visitante = EXCLUDED.equipo_visitante,
                        payload = EXCLUDED.payload,
                        updated_at = NOW()
                    """,
                    (
                        str(uuid.uuid4()),
                        match["competition"]["code"],
                        match["event_id"],
                        target,
                        match["date"],
                        match["local"]["name"],
                        match["visitante"]["name"],
                        Json(match),
                    ),
                )
        conn.commit()


def sync_day(target: date, club_catalog: dict[str, dict], strength_catalog: dict[str, dict]) -> int:
    matches = []
    for competition_code in COMPETITIONS:
        try:
            matches.extend(extract_matches_for_date(competition_code, target, club_catalog, strength_catalog))
        except Exception as exc:
            print(f"[warn] {competition_code} {target.isoformat()} -> {exc}")
    save_matches(target, matches)
    print(f"{target.isoformat()} -> {len(matches)} partido(s) guardado(s)")
    return len(matches)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Sincroniza copas UEFA hacia futbol_copa_partidos")
    parser.add_argument("--fecha", default=date.today().isoformat(), help="Fecha base YYYY-MM-DD")
    parser.add_argument("--days", type=int, default=1, help="Cantidad de dias consecutivos")
    return parser.parse_args()


def main() -> int:
    bootstrap_env()
    args = parse_args()
    target = date.fromisoformat(args.fecha)
    club_catalog = load_club_catalog()
    strength_catalog = load_strength_catalog()

    total = 0
    for offset in range(max(1, args.days)):
        total += sync_day(target + timedelta(days=offset), club_catalog, strength_catalog)

    print(f"Total guardado: {total}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
