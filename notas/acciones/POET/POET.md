# ~~POET Technologies (POET)~~ — DESCARTADA
> **DESCARTADA el 7 junio 2026** — Escándalos legales activos (class action, violación NDA del CFO), información no transparente (partnerships muertos presentados como activos), y 20 años sin revenue comercial real. No cumple criterios mínimos de transparencia e integridad de management.

---

# POET Technologies (POET) — Análisis Fundamental
> Actualizado: 7 junio 2026 | Datos en miles USD (K) | Precio aprox. stock: $14-15

---

## 1. La Empresa — Qué hace y a qué se dedica

### Origen y cotización
POET Technologies es una empresa canadiense de semiconductores fotónicos fundada en 2012, con operaciones en Toronto (HQ, en reubicación a EEUU), Singapur y Malasia. En agosto 2025 se **deslistó voluntariamente del TSX Venture Exchange (TSXV)** y cotiza exclusivamente en **NASDAQ: POET**. Está migrando la sede a EEUU para eliminar el riesgo de clasificación PFIC (ver sección 11).

Es una empresa **fabless** — no tiene fábricas propias, trabaja con socios de manufactura.

### Tecnología central: el POET Optical Interposer

El **POET Optical Interposer** es una plataforma de integración fotónica a nivel de oblea (wafer-level). Técnicamente:

- Un **sustrato de silicio pasivo** con guías de onda ópticas de baja pérdida integradas y trazas de cobre eléctrico en el mismo chip.
- Chips electrónicos (DSPs, TIAs, drivers) y dispositivos fotónicos (láseres, fotodetectores, moduladores) se montan mediante **flip-chip bonding** sobre el interposer en un proceso de ensamblaje a escala de oblea.
- Esto elimina el costoso alineamiento óptico manual que requieren los enfoques convencionales de silicon photonics o packaging discreto.
- Compatible con procesos CMOS estándar — fabricable en equipos de semiconductores convencionales.

**En palabras simples:** Los centros de datos de IA mueven petabytes entre GPUs usando fibra óptica. Cada punto de conversión eléctrico-óptico ("transceiver") requiere hoy ensamblar manualmente chips de 3-5 proveedores distintos. POET promete integrar todo en un solo chip → menor costo, mayor velocidad, menor consumo.

### Productos actuales (2025-2026)
- **POET Infinity** — motor óptico 800G (8x100G) y 1.6T (8x200G) para redes de AI data center
- **Light Source / EOI (Electro-Optical Integration)** — para conectividad chip-a-chip dentro de servidores
- **Near-Package Optics (NPO) y Co-Packaged Optics (CPO)** — en desarrollo para el siguiente nivel de integración con ASICs

### Mercados objetivo
- **AI / Data Centers** (principal) — transceivers 800G, 1.6T, CPO para infraestructura de GPU clusters
- **Telecomunicaciones** — sistemas de acceso y backhaul (secundario)
- **5G / Edge** — asociación con NTT Innovative Devices

### Management
- **CEO/Executive Chairman:** Dr. Suresh Venkatesan — ex-SVP de GLOBALFOUNDRIES, lideró desarrollo de nodo 28nm. Sólida credencial técnica.
- **CFO:** Thomas Mika — protagonista del escándalo de abril 2026 (violación NDA, ver sección 11)
- **COO:** Dr. Sandeep Kumar — incorporado el 11 mayo 2026, veterano de semiconductores, señal de transición de R&D a manufactura

### Estado real de la tecnología
- Muestras de 800G enviadas a clientes desde fab en Malasia (Globetronics) — Q2 2025
- Muestras de 1.6T enviadas — Q3 2025
- Una orden de producción confirmada: **>$5M para motores 800G POET Infinity** — Q4 2025
- CPO y NPO: solo muestras de ingeniería — producción en 2027
- Modulator TFLN 3.2T (con QCi): en desarrollo, entrega H2 2026
- **Chip revenue acumulado desde 2018: $1.2M** — la "comercialización real" no ha llegado
- Un ex-ingeniero de Rockley Photonics describió el interposer como "a solution looking for a problem"

---

## 2. Posición Financiera (actualizada a junio 2026)

| Métrica                           | Valor           |
|-----------------------------------|-----------------|
| Cash + ST investments (Q4 2025)   | ~$430M          |
| Raise adicional (mayo 2026)       | +$400M          |
| **Cash estimado actual**          | **~$800M+**     |
| Deuda total                       | ~$7M            |
| Operating Cash Burn (anualizado)  | ~$35M/año       |
| **Runway estimado**               | **~20+ años**   |
| Acciones en circulación           | ~100M post-raise|
| **Cash por acción (estimado)**    | **~$8/acción**  |
| Precio actual aprox.              | $14-15          |
| Market Cap aprox.                 | ~$1.4-1.5B      |

**El raise de $400M en mayo 2026** (cerrado el 18 de mayo, a $21/acción + warrants, un único inversor institucional) es el evento más reciente y transforma completamente el balance. Ocurrió **después** del escándalo de Marvell.

Esto significa que **alguien con $400M en efectivo evaluó la empresa post-escándalo y decidió invertir de todas formas**. Eso es el dato más bullish disponible hoy.

---

## 3. Income Statement — Puntos Críticos

### Ingresos (en $K)
```
2021:  $209K
2022:  $552K  +164%
2023:  $465K  -16%
2024:   $41K  -91%  ← casi cero, cliente principal canceló
2025: $1,074K +2494%  (Q1:$167K  Q3:$298K  Q4:$341K)
Q1 2026: $503K  +194% YoY ← mejor trimestre histórico
```
Todo es **NRE (Non-Recurring Engineering)** y órdenes de muestras pequeñas. No son ventas de chips en volumen. El "194% growth" es real pero sobre una base casi cero.

Desde 2020, el revenue total acumulado es solo **$2.3M**.

### Pérdidas Netas
```
2022: -$21M
2023: -$20M
2024: -$56M  ← warrants y derivados no-cash
2025: -$62M  (Q4 solo: -$42.7M, incluye -$30.6M ajuste warrants)
Q1 2026: -$12.3M  (-$0.08/share, miss vs estimado -$0.05)
```

### EPS Diluido
```
2025: -$0.68/share
Q1 2026: -$0.08/share
```

### R&D — acelerando con el capital
```
2021:  $8.1M
2023: $10.0M
2025: $18.1M
Q1 2026: $4.5M (trimestral)
```

---

## 4. Balance Sheet — Puntos Críticos

### Evento mayor: raises masivos 2025-2026
```
FY2025: $293M en equity nuevo (3 inversores institucionales, $250M principal + ATM)
Ene 2026: +$150M adicionales
May 2026: +$400M (un único inversor a $21/acción + warrants)
Total levantado 2025-2026: ~$843M
```

### Pasivos corrientes ($136M en 2025) — RESPONDIDO
Son **warrant/derivative liabilities** — parte del acuerdo con los inversores institucionales incluía warrants clasificados como pasivos mark-to-market. Q1 2026 reportó ganancia de $1.6M en "derivative liability adjustment", confirmando la hipótesis.

### Dilución histórica de acciones
```
2016:  22M shares
2021:  34M shares
2024:  60M shares
2025:  93M shares
May 2026: ~100M shares (post raise de $400M con 19M nuevas acciones)
```
Las acciones se quintuplicaron en 10 años. El modelo de financiación de la empresa ES la dilución continua.

---

## 5. Cash Flow — El Contador del Runway

### Operating Cash Flow (quema real de caja)
```
2022: -$12.3M/año
2023: -$15.4M/año
2024: -$23.3M/año
2025: -$31.1M/año
Q1 2026: -$8.8M (trimestral → ~-$35M anualizado)
```
El burn se acelera cada año. Con ~$800M en cash y -$35M/año: **runway de >20 años**.

---

## 6. Último Earnings — Q1 2026 (reportado 14 mayo 2026)

| Métrica             | Q1 2026   | Q1 2025   | vs Estimado |
|---------------------|-----------|-----------|-------------|
| Revenue             | $503K     | $167K     | Beat (+$250K est) |
| Net Loss            | -$12.3M   | positivo* | Miss (-$0.08 vs -$0.05 est) |
| EPS                 | -$0.08    | +$0.08*   | Miss        |
| Operating CF        | -$8.8M    | —         | —           |
| R&D                 | $4.5M     | —         | —           |
| Interest income     | $4.0M     | —         | —           |
| Derivative gain     | +$1.6M    | —         | —           |

*Q1 2025 net income positivo fue principalmente no-cash/no-operacional.

### Anuncios clave en Q1 2026
- **Deal Lumilens:** $50M initial PO, potencial de escalar a $500M en 5 años — el deal más grande anunciado
- **Partnership LITEON:** Co-desarrollo de módulos de comunicación óptica
- **Partnership Lessengers:** Colaboración adicional de transceptores
- **Reubicación a EEUU** anunciada para eliminar la clasificación PFIC
- **Nombramiento COO Sandeep Kumar** (11 mayo 2026)

### Reacción del mercado
```
Pre-earnings (11 mayo): +14.3% pre-market → ~$10.95
Día de earnings (14 mayo): cierre en $20.57 (run-up previo)
Post-earnings (15 mayo+): -12% por EPS miss + dilución del $400M raise
Rango reciente: $14-15
```

---

## 7. El Escándalo de Abril 2026 — El Crash del 47%

### Cronología
```
25 abr 2026 → POET publica PR anunciando PO de Celestial AI (subsidiaria de Marvell)
               CFO Thomas Mika había mencionado info confidencial en un video de YouTube
23 abr 2026 → Marvell envía carta cancelando TODOS los purchase orders
               (carta anterior al PR — timing bizarro)
27 abr 2026 → POET revela públicamente la cancelación
               Stock cae -47.3%: $15.10 → $7.95
               Se borran ~$1,070M de market cap
29 abr 2026 → Primeras demandas de class action
11 may 2026 → Stock inicia recuperación fuerte (+14.3% pre-market)
18 may 2026 → Cierra raise de $400M — alguien compró a $21/acción
29 jun 2026 → Deadline lead plaintiff en demandas
```

### ¿Qué pasó exactamente?
1. El CFO Thomas Mika reveló en un video de YouTube información sobre la relación con Celestial AI antes de tener autorización.
2. El PR del 25 de abril confirmó el PO públicamente sin aprobación de Marvell.
3. **Marvell canceló TODO** citando violación de confidentiality obligations.
4. Night Market Research y Wolfpack Research publicaron reportes cortos explosivos casi simultáneamente.

### Las demandas class action (activas)
- **Período de clase:** 1 abril 2026 – 27 abril 2026
- **Firmas:** Rosen Law Firm, Block & Leviton, Gibbs Mura
- **Alegaciones principales:**
  1. **PFIC misrepresentation:** El Annual Report 2025 decía "may be treated as PFIC" cuando ya era PFIC — implicaciones fiscales materiales para inversores americanos
  2. **Violación de NDA:** CFO divulgó info confidencial de Marvell/Celestial AI
  3. **SOX certifications cuestionables:** CEO y CFO atestiguaron accuracy que no existía
- **Deadline lead plaintiff:** 29 junio 2026

---

## 8. Short Seller Reports

### Night Market Research — El más devastador
**URL:** nightmarketresearch.com/poet  
**Título:** *"Marvell Isn't The Only Dead Partnership. Foxconn, LITEON and More Are Too. Lumilens Next?"*

#### Partnerships que POET vendía como activos vs realidad:

| Partner             | Claim de POET             | Realidad según Night Market                          |
|---------------------|---------------------------|------------------------------------------------------|
| Marvell/Celestial AI| Cliente activo             | "No se discute internamente desde 2023"              |
| Foxconn             | Major customer            | "Dejaron de usar POET hace 2 años"                   |
| Luxshare            | Integra tecnología POET   | 2 ejecutivos: "nunca hemos oído de POET"             |
| LITEON              | Partner estratégico       | Presidente: "no hay negocio real entre nosotros"     |
| Sanan (fab)         | Manufacturing partner     | Aportó $7M de $25M prometidos y se retiró           |
| Lumilens            | $50M PO, $500M potencial  | Shell company, order posiblemente no vinculante       |

#### Sobre Lumilens (el deal que celebraron en Q1 2026):
- Lumilens adquirió Rain Tree Photonics **7 semanas antes** de hacer el PO de $50M
- Se describe como shell company sin tecnología propia
- El orden estaría estructurado para entregar **valor de warrants (~$30M)** a Lumilens
- El potencial de "$500M en 5 años" sería marketing, no un contrato vinculante

#### Diagnóstico general:
> "20 años perdiendo dinero, prometiendo comercialización inminente, anunciando partnerships que no generan negocio real. Chip revenue (no NRE) desde 2018: $1.2M total."

### Wolfpack Research
- Describió POET como **"an obvious stock promotion"**
- Señaló el riesgo PFIC (Passive Foreign Investment Company) para accionistas americanos
- POET posteriormente confirmó que proveerá QEF election data a accionistas y reubicará HQ a EEUU

---

## 9. Fotónica — Por Qué Importa en la Era de IA

### El problema que resuelve
Los clusters de IA (H100/B200 de Nvidia) necesitan mover petabytes entre GPUs cada segundo. El cobre falla a distancias >5m y consume demasiada energía. Los transceivers ópticos (fibra) son la solución, pero hoy requieren ensamblar manualmente chips de 3-5 proveedores. La fotónica integrada promete hacer todo en un chip → menor costo, mayor velocidad, menor consumo.

### Mercado (TAM)
- Mercado de transceivers ópticos 2025: ~$12B
- Mercado interconnects ópticos para AI 2025: ~$3.75B
- Proyección 2030-2033: $35B+ (CAGR ~23%)
- Driver principal: hiperescaladores Meta, Google, Microsoft, Amazon

### El bottleneck real en AI
En clusters de GPU de siguiente generación (Blackwell, Rubin), el ancho de banda **entre chips** (no la capacidad de cómputo de cada chip) es el cuello de botella. El CPO (Co-Packaged Optics) se considera la solución definitiva — óptica integrada directamente en el ASIC del switch. **Este es el mercado de largo plazo que todos (POET, Ayar Labs, Intel, Nvidia) persiguen.**

---

## 10. Inversiones del Gobierno EEUU en Fotónica

### Escala general
- Grants federales para fotónica 2023: **$1.7B** (solo I+D, sin contar privados)
- DARPA: ~$168.7M/año dedicados a fotónica
- AIM Photonics: ~$130M/año promedio, $934M acumulado 2015-2022

### AIM Photonics — el instituto clave
- Consorcio público-privado creado en 2015 (DoD + MIT + SUNY Albany)
- **Extensión 2021:** $321M por 7 años ($165M federal + $156M cost-share privado)
- Objetivo: crear cadena de suministro doméstica de fotónica integrada, independiente de Asia
- **POET Technologies es miembro de AIM Photonics** — acceso a sus procesos de fabricación
- Shared PIC foundry en Albany (fab de GlobalFoundries) disponible para startups

### CHIPS and Science Act (2022) — beneficiarios de fotónica

| Empresa          | Monto CHIPS      | Para qué                                          | Fecha    |
|------------------|------------------|---------------------------------------------------|----------|
| Infinera         | Hasta $93M       | Fab de wafers InP (PIC) en San José — 10x capacidad | Oct 2024|
| GlobalFoundries  | Hasta $75M       | Centro NY de Packaging Avanzado y Fotónica         | Ene 2025|

GlobalFoundries + NY State + Green CHIPS = ~$575M total en inversión para el centro de fotónica de Albany.

### DARPA — programas activos

**PIPES (Photonics in the Package for Extreme Scalability)**
- Meta: 100 Tbps por paquete a <1 picojoule/bit
- Abril 2025: $45M a Cerebras + Ranovus para interconexión fotónica wafer-scale (primer CPO a escala de oblea)

**LUMOS (Lasers for Universal Microscale Optical Systems)**
- Parte del Electronics Resurgence Initiative (ERI) de ~$1.5B
- Miles de láseres on-chip, láseres de clase watt en plataformas fotónicas

**ERI (Electronics Resurgence Initiative)**
- $1.5B+ durante 5 años para microelectrónica más allá del transistor
- Incluye fotónica, nuevas arquitecturas de cómputo, IA en el edge

### NVIDIA — $4B en fotónica (marzo 2026)
```
Lumentum:  $2B en inversión + compromiso de compra
Coherent:  $2B en inversión + compromiso de compra
```
NVIDIA aseguró el suministro de óptica americana para su infraestructura de AI data center. El movimiento más grande de capital privado en fotónica de la historia reciente.

### Ayar Labs — el competidor más financiado
```
Series D: $155M (dic 2024) — AMD, Intel Capital, NVIDIA entre inversores
Series E: $500M (mar 2026) — Neuberger Berman + otros
Total: $655M+ solo en 2024-2026
```
Ayar Labs tiene la tecnología TeraPHY (chiplet de I/O óptico) basada en trabajo original del MIT/DARPA. Es el competidor más directo de POET en el segmento CPO.

### Relevancia estratégica: China
- Un reporte CSIS (ene 2024) identificó la fotónica de silicio como área donde China podría "cambiar de carril y rebasar" a EEUU, ya que las restricciones de exportación de oct 2022 no cubrían fotónica.
- El 14° Plan Quinquenal de China prioriza explícitamente la fotónica.
- El House Select Committee on CCP presionó para incluir equipos de silicon photonics en la Commerce Control List.

---

## 11. Competidores — La Competencia es Seria

| Empresa             | Fortaleza                                   | Amenaza para POET                     |
|---------------------|---------------------------------------------|---------------------------------------|
| **Intel** (Silicon Photonics) | $80B+ revenue, fab propia, 15+ años de desarrollo | Puede aplastar en precio y escala |
| **Broadcom**        | Domina market share en transceivers de volumen | Customer AND competidor potential    |
| **Coherent Corp.**  | Fabricante vertical, $2B de NVIDIA          | Producción masiva real ya hoy         |
| **Lumentum**        | Componentes ópticos en volumen, $2B NVIDIA  | Misma base de clientes                |
| **Ayar Labs**       | TeraPHY chiplet, $655M levantado, DARPA + NVIDIA | Tecnología similar, mucho más capital |
| **Ranovus**         | CPO + wafer-scale, $45M DARPA (con Cerebras)| Compite en CPO con validación DoD     |
| **Lightmatter**     | Silicon photonics interposers, $400M levantado | Aplicación IA directa                |
| **II-VI / Coherent**| Manufactura vertical completa               | Integración que POET no tiene         |

**La realidad competitiva de POET:** 80 empleados vs equipos de cientos. Sin fab propia. Sin contrato con un hiperescalador. Compiten en CPO con Ayar Labs (que tiene $655M+ y validación DARPA/NVIDIA).

---

## 12. Preguntas Abiertas

| # | Pregunta | Estado |
|---|----------|--------|
| ✅ | ¿Qué son los $136M en Current Liabilities? | Warrant/derivative liabilities del raise institucional |
| ✅ | ¿Quiénes invirtieron los $293M en 2025? | 3 institucionales anónimos + ATM |
| ✅ | ¿Hay contratos reales con hiperescaladores? | NO — Celestial AI fue el único y fue cancelado |
| ✅ | ¿Por qué los $136M de liabilities? | Mark-to-market de warrants del raise |
| ⚠️ | ¿Quién invirtió $400M post-escándalo a $21/acción? | No revelado públicamente — crítico saberlo |
| ⚠️ | ¿Es Lumilens un deal real? | Night Market dice que no — alto riesgo |
| ⚠️ | ¿Sobrevive el CEO/CFO post-demandas? | Sin confirmación — CFO en posición muy débil |
| ⚠️ | ¿Logrará la reubicación a EEUU resolver el PFIC? | En proceso según management |
| ⚠️ | ¿Cuándo llega revenue real de chips en volumen? | Q2-Q3 2026 será el siguiente test |

---

## 13. Rating Actualizado (junio 2026)

| Dimensión               | Nota    | Comentario                                           |
|-------------------------|---------|------------------------------------------------------|
| Salud financiera (cash) | 9/10    | ~$800M cash, >20 años runway — extraordinario        |
| Revenue real            | 1/10    | $2.3M acumulado desde 2020 es devastador             |
| Integridad management   | 3/10    | CFO violó NDA, demandas activas, pero CEO sólido     |
| Credibilidad de partnerships | 2/10 | Foxconn, Luxshare, LITEON, Marvell — todos muertos según short sellers |
| Tesis tecnológica       | 6/10   | Concepto válido, muestras enviadas, pero ejecución aún no probada |
| Competencia             | 2/10   | Ayar Labs, Intel, Coherent, Lumentum tienen recursos masivamente superiores |
| Riesgo legal            | Alto    | Class action activa, CFO vulnerable, SOX en cuestión |
| Valoración vs cash      | 7/10   | $14-15 con $8/cash ≈ pagar $6-7 por la opción tecnológica |
| **Overall**             | **Especulativa/Alta Riesgo** | La posición de cash es extraordinaria; el resto es red flags |

---

## 14. Tesis de Inversión — El Debate Real

### Bull Case (revisado con nueva información)
- **Cash fortress:** ~$800M con burn de $35M/año = >20 años sin necesidad de levantar más capital
- **Alguien con $400M invirtió POST-escándalo** a $21/acción — implica due diligence profunda
- La fotónica para AI es un **mercado real con tailwinds masivos** (NVIDIA, gobierno EEUU)
- R&D acelerando con capital — 800G y 1.6T samples enviados a clientes reales
- Si Lumilens es real y escala, el revenue de 2027-2028 podría justificar la valoración

### Bear Case (los riesgos no desaparecen con el cash)
- **20 años sin revenue significativo** — el patrón es alarmante
- Night Market destruyó las dos narrativas más grandes: Marvell/Celestial y la credibilidad de partnerships
- **Class action con PFIC + NDA violations** — puede resultar en settlement que destroce confianza
- Ayar Labs levantó $655M solo en 2024-2026, tiene DARPA + NVIDIA detrás — competencia brutal
- La dilución continuará — los warrants de los $400M y los raises anteriores crearán más presión
- **Lumilens puede ser el próximo catalizador negativo** si Night Market tiene razón

### La pregunta de $1,400M (market cap actual):
¿Pagas $6-7 por acción por "la opción" de que POET logre lo que ha prometido durante 20 años, en un mercado donde compiten Intel, Broadcom, Ayar Labs con recursos masivamente superiores?

---

## 15. Log de análisis

| Fecha       | Nota |
|-------------|------|
| 2026-06-07  | Análisis inicial con IS, BS y CF (2016–TTM) |
| 2026-06-07  | Incorporado: escándalo Marvell (abr 2026), short sellers Night Market + Wolfpack, Q1 2026 earnings, raise $400M (mayo 2026), tecnología detallada, competidores, fotónica y EEUU |
