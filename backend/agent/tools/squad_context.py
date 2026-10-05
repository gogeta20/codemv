from __future__ import annotations

from datetime import date, datetime

RECENT_SQUAD_IMPACTS = [
    {
        "team": "IK Sirius",
        "start": date(2026, 8, 11),
        "end": date(2026, 9, 5),
        "points": -2,
        "reason": "salida reciente del goleador Robbie Ure; baja la continuidad ofensiva del favorito",
        "kind": "salida_clave",
        "source": "manual",
    },
]


def _normalize_match_date(match_date: date | datetime | None) -> date | None:
    if match_date is None:
        return None
    if isinstance(match_date, datetime):
        return match_date.date()
    return match_date


def get_team_squad_context(team_name: str, match_date: date | datetime | None) -> dict | None:
    target_date = _normalize_match_date(match_date)
    if not team_name or target_date is None:
        return None

    for item in RECENT_SQUAD_IMPACTS:
        if item["team"] != team_name:
            continue
        if item["start"] <= target_date <= item["end"]:
            return {
                "team": item["team"],
                "points": item["points"],
                "reason": item["reason"],
                "kind": item["kind"],
                "source": item["source"],
                "active_from": item["start"].isoformat(),
                "active_until": item["end"].isoformat(),
            }

    return None
