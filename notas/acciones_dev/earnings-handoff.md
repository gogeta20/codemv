# Handoff — Earnings reports

Fecha: 2026-08-11

## Objetivo de este bloque

Dejar automatizado el flujo para:

1. detectar acciones con fecha de earnings cercana o vencida
2. buscar el último reporte oficial en SEC
3. descargarlo y guardar el contenido bruto en base de datos
4. exponer un endpoint que devuelva un análisis inicial para que el frontend lo pinte

---

## Estado actual

### Backend

- Existe fetch oficial desde SEC para el último earnings report de una acción.
- Existe persistencia de reportes brutos en base de datos.
- Existe comando Symfony reutilizable para cron.
- Existe endpoint de análisis listo para frontend.
- El parsing actual está orientado a press releases tipo `8-K / EX-99.1`.

### Frontend

- Existe vista de análisis de earnings.
- La lista de acciones ya puede mostrar acceso rápido al reporte si existe.
- La vista ya consume el endpoint real del backend.
- Se quitaron los fallbacks silenciosos a mock para este caso de uso cuando no está en modo mock.

---

## Endpoints y comandos

### 1. Consultar último reporte detectado en SEC

`GET /api/acciones/earnings-report?symbol=PLTR`

Devuelve metadata del último filing relevante encontrado en SEC.

### 2. Descargar y persistir reporte bruto

`POST /api/acciones/earnings-report/fetch`

Body:

```json
{
  "symbol": "PLTR"
}
```

Esto descarga el exhibit o filing, normaliza el contenido y lo guarda en `acciones_earnings_reports`.

### 3. Analizar reporte persistido para frontend

`GET /api/acciones/:uuid/earnings-analysis`

Contrato mínimo actual:

- `report`
- `verdict`
- `highlights.good`
- `highlights.bad`
- `kpis`
- `cross_checks`
- `risks`

### 4. Comando Symfony para cron o pruebas manuales

```bash
docker exec codemv-api php bin/console app:acciones:fetch-earnings-reports --symbol=PLTR
docker exec codemv-api php bin/console app:acciones:fetch-earnings-reports --days-back=7 --days-forward=1
```

---

## Base de datos

### Tabla nueva

`acciones_earnings_reports`

Guarda el reporte bruto desacoplado del análisis posterior.

Campos importantes:

- `uuid`
- `accion_id`
- `source`
- `source_url`
- `filing_url`
- `exhibit_url`
- `accession_number`
- `form_type`
- `filing_date`
- `report_date`
- `raw_content`
- `metadata`

### Nota

En la entidad `AccionEarningsReport` sigue existiendo el setter con typo:

`setAccesssionNumber(...)`

No renombrarlo sin actualizar los callers.

---

## Validaciones hechas

### Caso real probado

- Símbolo: `PLTR`
- Filing detectado: `8-K`
- Filing date: `2026-08-03`
- Period label corregido: `Q2 2026`

### Resultado actual del análisis para PLTR

- `signal`: `good`
- `score`: `94`
- revenue detectado
- EPS detectado
- margen operativo detectado
- free cash flow detectado
- guidance detectado
- caja detectada

### Frontend

- `pnpm build` pasa correctamente dentro de `codemv-frontend`
- la vista de earnings compila bien
- la UI ya usa subtítulos servidos por backend en `kpis`, `cross_checks` y `risks`

---

## Archivos clave tocados

### Backend

- `backend/symfony/src/Acciones/Application/Earnings/GetLatestReport/*`
- `backend/symfony/src/Acciones/Application/Earnings/FetchLatestReport/*`
- `backend/symfony/src/Acciones/Application/Earnings/GetAnalysis/*`
- `backend/symfony/src/Acciones/Infrastructure/Controller/Accion/GetLatestEarningsReportController.php`
- `backend/symfony/src/Acciones/Infrastructure/Controller/Accion/FetchLatestEarningsReportController.php`
- `backend/symfony/src/Acciones/Infrastructure/Controller/Accion/GetEarningsAnalysisController.php`
- `backend/symfony/src/Acciones/Infrastructure/Command/FetchEarningsReportsCommand.php`
- `backend/symfony/src/Acciones/Infrastructure/Doctrine/Entity/AccionEarningsReport.php`
- `backend/symfony/src/Acciones/Infrastructure/Doctrine/Repository/DoctrineAccionEarningsReportRepository.php`
- `backend/symfony/src/Acciones/Infrastructure/Doctrine/Repository/DoctrineAccionEarningsRepository.php`
- `backend/symfony/migrations/Version20260810000000.php`
- `backend/symfony/config/services.yaml`

### Frontend

- `frontend/src/Acciones/Infrastructure/View/AccionEarningsAnalysisView.vue`
- `frontend/src/Acciones/Infrastructure/View/AccionesListView.vue`
- `frontend/src/Acciones/Application/UseCase/GetEarningsAnalysis/GetEarningsAnalysisUseCase.js`

---

## Cron

Cron recomendado ya documentado también en `notas/arrancar.md`:

```cron
0 20 * * 1-5 cd /home/mauricio-vargas/projects/personal/codemv && docker exec codemv-api php bin/console app:acciones:fetch-earnings-reports --days-back=7 --days-forward=1 >> /tmp/earnings_reports.log 2>&1
```

Objetivo:

- revisar acciones con earnings cercano o vencido
- intentar encontrar reporte oficial
- persistirlo en `acciones_earnings_reports`

---

## Qué falta

### Prioridad alta

- cruzar el reporte contra consenso externo
- poblar `estimate` y `surprise_pct`
- decidir de qué proveedor sacar estimates

### Prioridad media

- mejorar parsing para más formatos de filing y más empresas
- soportar mejor casos donde no haya `EX-99.1` clara
- añadir tests del parser con fixtures de varios símbolos

### Prioridad frontend

- revisar en navegador la vista real con varios reportes
- decidir si mostrar badges extra para `beat/miss`
- decidir si conviene una pestaña con contenido bruto o extractos

---

## Riesgos / observaciones

- SEC exige headers realistas; si falla la búsqueda, revisar `User-Agent` y headers usados.
- El análisis actual es útil para UI y demo, pero todavía no debe venderse como análisis fundamental definitivo.
- Sin estimates externos no hay todavía `beat vs consensus` real, solo placeholder razonado.
- Parte de los edits de esta sesión no se pudieron hacer con `apply_patch` por restricciones del sandbox; se usó edición directa en algunos archivos existentes.

---

## Siguiente paso recomendado para el próximo chat

Conectar estimates externos al flujo de earnings:

1. elegir fuente para consenso
2. guardar revenue estimate y EPS estimate
3. calcular `surprise_pct`
4. enriquecer `verdict` y `cross_checks.beat_vs_expectations`
5. pintar esos datos en la vista
