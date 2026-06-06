# Arrancar el entorno

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
cd ~/projects/personal/IA/codemv/backend/agent && python3 main.py

# Análisis fútbol (selección del día)
cd ~/projects/personal/IA/codemv/backend/agent && python3 steps/football_daily.py

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
07:00  todos los días        →  análisis fútbol  (football_daily.py)
@reboot (+ 30s)              →  lookup server
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
