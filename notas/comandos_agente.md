# Comandos del agente

Comandos manuales recomendados desde la raíz del proyecto:

```bash
cd ~/projects/personal/IA/codemv
```

## Arranque de infraestructura

```bash
make up-all
```

Levanta API, base de datos y frontend.

## Lookup server

```bash
make agent-lookup
```

Arranca el servidor auxiliar del agente en `http://localhost:5001`.
Sirve para lookups y utilidades de datos externas.

## Agente de acciones

```bash
make agent-main
```

Ejecuta manualmente el flujo principal del agente de acciones (`main.py`).
Útil para probar precios, noticias, descubrimientos y alertas sin esperar al cron.

## Fútbol — partidos de hoy

```bash
make agent-football
```

Ejecuta `football_daily.py` usando la fecha de hoy.
Sirve para cargar y analizar los partidos del día actual.

## Fútbol — partidos de mañana

```bash
make agent-football-manana
```

Ejecuta `football_daily.py --manana`.
Es el comando más útil para probar el caso del cron de la `01:00`, donde queremos precargar el siguiente día.

## Cuándo usar cada uno

- `make agent-main`
  Para acciones y radar de mercado.

- `make agent-football`
  Para revisar el día actual.

- `make agent-football-manana`
  Para comprobar el flujo que se parece al cron nocturno.

- `make agent-lookup`
  Cuando necesitas el servidor auxiliar Python disponible.
