# Arrancar el entorno

Antes de usar esta chuleta, leer primero `notas/normas.md`.

## Un solo comando

```bash
cd ~/projects/personal/IA/codemv && make dev
```

Levanta Docker (API + DB + frontend) y el lookup server. Espera ~15s a que la API esté lista.

---

## Logs

```bash
tail -f /tmp/lookup_server.log       # lookup server (yfinance / precios)
tail -f /tmp/agente_mvm.log          # agente trading (cron L-V)
tail -f /tmp/futbol_seleccion.log    # análisis fútbol (cron diario)
```

---

## Acciones manuales (fuera del cron)

```bash
# Agente trading
cd ~/projects/personal/IA/codemv && make agent-main

# Análisis fútbol (selección del día)
cd ~/projects/personal/IA/codemv && make agent-football

# Análisis fútbol para mañana (útil para probar el cron de la 01:00)
cd ~/projects/personal/IA/codemv && make agent-football-manana

# Fetch de reportes de earnings desde SEC para una acción concreta
docker exec codemv-api php bin/console app:acciones:fetch-earnings-reports --symbol=PLTR

# Ranking de ligas mundiales por goles/partido ← ejecutar 1 vez por semana o al mes
# Solo hace falta cuando quieres actualizar los datos de la vista /futbol/ligas/gpm
cd ~/projects/personal/IA/codemv/backend/agent && python3 steps/ligas_gpm.py
```

---

## Primera vez (DB vacía) — seed de ligas

```bash
cd ~/projects/personal/IA/codemv/backend/agent && python3 seed_ligas.py
```

---

## Cron (automático — no tocar)

```
07:00  15:00  18:00  22:00  →  Lunes a Viernes  →  agente trading (main.py)
01:00  todos los días        →  análisis fútbol  (football_daily.py)
20:15  Lunes a Viernes       →  fetch reportes earnings Symfony (app:acciones:fetch-earnings-reports)
@reboot (+ 30s)              →  lookup server

Línea recomendada para el nuevo cron de earnings:

0 20 * * 1-5  cd /home/mauricio-vargas/projects/personal/codemv && docker exec codemv-api php bin/console app:acciones:fetch-earnings-reports --days-back=7 --days-forward=1 >> /tmp/earnings_reports.log 2>&1
```

---

## Otros comandos útiles

```bash
make stop          # parar Docker
make down          # parar y eliminar contenedores
make logs          # logs Docker en vivo
make psql          # psql en el contenedor DB
make bash          # bash en el contenedor API
```
