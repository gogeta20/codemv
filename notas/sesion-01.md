# Agente Local - Sesión 01
Fecha: 24/04/2026

---

## Objetivo del proyecto

Agente de trading que vive en la PC local con:
- Análisis matutino automático de precios (si mueve >5% busca por qué)
- Herramientas reales: curl, consulta de DB, envío de alertas
- Capacidad de consultar a Claude (API Anthropic) cuando tenga dudas
- Memoria persistente via base de datos
- Gestión autónoma: al cerrar guarda estado, al abrir retoma contexto

---

## Hardware de la PC

| Componente | Detalle |
|---|---|
| CPU | Intel Core i7-1165G7 (11th Gen, 4 cores / 8 threads, hasta 4.7GHz) |
| RAM | 16GB total, ~12GB disponibles |
| GPU | Intel Iris Xe (integrada, sin VRAM dedicada) |
| OS | Ubuntu 22.04 |
| Shell | zsh |

**Modo CPU-only** — sin GPU dedicada. Modelos pequeños son la clave.

---

## Modelo elegido

**Llama 3.2 3B**
- Tamaño: 2GB en disco
- Tool calling nativo
- Rápido en CPU
- Para razonamiento complejo delega a Claude via API

---

## Instalación

### Instalar Ollama
```bash
curl -fsSL https://ollama.com/install.sh | sh
```
Requiere sudo. Se instala como servicio systemd y arranca automático.

### Bajar el modelo
```bash
ollama pull llama3.2:3b
```

### Verificar
```bash
ollama list
```

---

## Comandos útiles de Ollama

```bash
# Chat interactivo
ollama run llama3.2:3b

# Pregunta rápida
ollama run llama3.2:3b "tu pregunta"

# Ver modelos instalados
ollama list

# Estado del servicio
systemctl status ollama
systemctl restart ollama

# API REST (para Python)
curl http://localhost:11434/api/chat -d '{
  "model": "llama3.2:3b",
  "messages": [{"role": "user", "content": "hola"}],
  "stream": false
}'
```

---

## Prueba de tool calling

Archivo de prueba: `/home/mau/test_agent.py`

Resultado de la prueba:
- Tool calling funciona correctamente
- El modelo ejecuta las tools y recibe los resultados
- Sin system prompt adecuado el modelo ignora sus propios resultados (miedo a dar info incorrecta)
- Con system prompt correcto responde usando los datos reales

### System prompt que funcionó

```python
{
    "role": "system",
    "content": (
        "Eres un asistente de trading. "
        "Tienes tools disponibles y DEBES usarlas cuando necesites datos. "
        "Cuando una tool te devuelve un resultado, ese dato es REAL y ACTUAL — fue ejecutado ahora mismo en el sistema. "
        "NUNCA digas que no puedes dar información en tiempo real si ya ejecutaste una tool y tienes el resultado. "
        "Usa siempre el resultado de la tool en tu respuesta."
    )
}
```

---

## Arquitectura del agente (plan)

```
[Cron - mañana] → agente despierta
        ↓
Lee DB → concepto + instrucciones + resumen de ayer
        ↓
Fetchea precios actuales (tool)
        ↓
¿Movió >5%? → busca noticias (tool)
        ↓
¿Duda compleja? → consulta Claude API (tool)
        ↓
Analiza y genera resumen
        ↓
Envía alerta (Telegram / email)
        ↓
[Noche] guarda estado en DB
```

### Estructura de la base de datos (3 capas)

```
┌─────────────────────────────────────┐
│  CONCEPTO E INSTRUCCIONES           │  ← se define una vez
│  estilo, umbrales, activos a seguir │
├─────────────────────────────────────┤
│  MEMORIA DEL DÍA ANTERIOR           │  ← el agente escribe al cerrar
│  precios, tendencia, noticias clave │
├─────────────────────────────────────┤
│  HISTÓRICO                          │  ← acumula con el tiempo
│  fecha | activo | precio | análisis │
└─────────────────────────────────────┘
```

---

## Stack técnico

- **Runtime**: Ollama (servicio local)
- **Modelo**: llama3.2:3b
- **Lenguaje**: Python 3.10
- **Agente**: llamadas directas a Ollama API (localhost:11434)
- **DB**: por definir (viene de la otra PC — Postgres / MySQL / SQLite)
- **Alertas**: por definir (Telegram es la opción más simple)
- **Fallback IA**: Claude API (Anthropic) via curl desde el agente

---

## Pendiente para próxima sesión

- [ ] Traer el proyecto de la otra PC
- [ ] Definir qué DB se usa
- [ ] Definir canal de alertas (Telegram?)
- [ ] Armar estructura del proyecto Python
- [ ] Conectar tool de precios real (yfinance / CoinGecko / broker API)
- [ ] Implementar tool de noticias
- [ ] Implementar memoria persistente en DB
- [ ] Configurar cron para ejecución matutina
- [ ] Agregar tool para consultar Claude API
