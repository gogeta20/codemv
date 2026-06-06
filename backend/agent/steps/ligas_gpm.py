"""
Calcula el promedio de goles por partido (gpm) para una lista amplia de ligas mundiales.
Fuente: ESPN standings (goles a favor de cada equipo).
Fórmula: sum(gf_todos_equipos) / (sum(pj_todos_equipos) / 2)
Guarda en futbol_ligas_gpm. Ejecutar semanalmente.
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from tools.espn import get_standings
from db import save_ligas_gpm

LIGAS = [
    # Europa — Tier 1
    ('eng.1', 'Premier League',       'Inglaterra',    1),
    ('esp.1', 'La Liga',              'España',        1),
    ('ger.1', 'Bundesliga',           'Alemania',      1),
    ('ita.1', 'Serie A',              'Italia',        1),
    ('fra.1', 'Ligue 1',              'Francia',       1),
    ('por.1', 'Primeira Liga',        'Portugal',      1),
    ('ned.1', 'Eredivisie',           'Países Bajos',  1),
    ('tur.1', 'Süper Lig',            'Turquía',       1),
    ('bel.1', 'Pro League',           'Bélgica',       1),
    ('sco.1', 'Premiership',          'Escocia',       1),
    ('gre.1', 'Super League',         'Grecia',        1),
    # Europa — Tier 2
    ('nor.1', 'Eliteserien',          'Noruega',       1),
    ('swe.1', 'Allsvenskan',          'Suecia',        1),
    ('den.1', 'Superliga',            'Dinamarca',     1),
    ('aut.1', 'Bundesliga',           'Austria',       1),
    ('eng.2', 'Championship',         'Inglaterra',    2),
    ('esp.2', 'Segunda División',     'España',        2),
    ('ger.2', '2. Bundesliga',        'Alemania',      2),
    ('ita.2', 'Serie B',              'Italia',        2),
    # América
    ('usa.1', 'MLS',                  'USA',           1),
    ('mex.1', 'Liga MX',              'México',        1),
    ('bra.1', 'Brasileirão',          'Brasil',        1),
    ('arg.1', 'Primera División',     'Argentina',     1),
    ('col.1', 'Primera A',            'Colombia',      1),
    ('bol.1', 'Liga Profesional',     'Bolivia',       1),
    ('uru.1', 'Primera División',     'Uruguay',       1),
    ('ecu.1', 'Liga Pro',             'Ecuador',       1),
    # Asia / Oceanía
    ('jpn.1', 'J1 League',            'Japón',         1),
    ('aus.1', 'A-League',             'Australia',     1),
    ('chn.1', 'Super League',         'China',         1),
    ('kor.1', 'K League 1',           'Corea del Sur', 1),
]

MIN_PARTIDOS = 15


def run():
    print(f"\n{'='*50}")
    print("[ligas_gpm] Calculando goles por partido por liga...")
    print(f"{'='*50}")

    resultados = []

    for codigo, nombre, pais, tier in LIGAS:
        print(f"  → {nombre} ({pais})...", end=" ", flush=True)
        tabla = get_standings(codigo)

        if not tabla or len(tabla) < 4:
            print("sin datos")
            continue

        gf_total = sum(t.get('gf') or 0 for t in tabla.values())
        pj_total = sum(t.get('pj') or 0 for t in tabla.values())

        if pj_total < MIN_PARTIDOS * 2:
            print(f"temporada muy joven ({pj_total} pj total)")
            continue

        partidos = pj_total / 2
        gpm = round(gf_total / partidos, 2)

        resultados.append({
            'codigo': codigo,
            'liga':   nombre,
            'pais':   pais,
            'tier':   tier,
            'gpm':    gpm,
            'partidos': int(partidos),
            'equipos':  len(tabla),
        })
        print(f"{gpm} gpm ({int(partidos)} partidos)")

    if not resultados:
        print("[ligas_gpm] Sin resultados. Fin.")
        return

    resultados.sort(key=lambda x: x['gpm'], reverse=True)

    print(f"\n[ligas_gpm] {len(resultados)} ligas calculadas")
    print("  Más goles:  ", resultados[0]['liga'], resultados[0]['gpm'])
    print("  Menos goles:", resultados[-1]['liga'], resultados[-1]['gpm'])

    save_ligas_gpm(resultados)
    print("[ligas_gpm] Guardado en DB. Completado.\n")


if __name__ == '__main__':
    run()
