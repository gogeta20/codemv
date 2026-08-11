<template>
  <footer class="futbol-leyenda">
    <div class="leyenda-titulo">Glosario</div>
    <div class="leyenda-grid">
      <div class="leyenda-item">
        <span class="term">Odds</span>
        <span class="def">Cuotas decimales. Ej: 1.32 significa que por cada 1 apostado se cobran 1.32 si gana (cuanto más baja, más favorito). El % junto a cada cuota es la probabilidad implícita (1 / cuota); la suma de las 3 supera 100% por el margen de la casa.</span>
      </div>
      <div class="leyenda-item">
        <span class="term">Prob</span>
        <span class="def">Probabilidad de victoria según el pickcenter de ESPN. Barra azul = local, gris = empate, roja = visitante. <em>Solo disponible en ligas con cobertura ESPN (La Liga, Serie A, Bolivia…). Bundesliga y otras no lo publican.</em></span>
      </div>
      <div class="leyenda-item">
        <span class="term">O/U</span>
        <span class="def">Over/Under. Línea de goles totales del partido. O/U 2.5 significa apostar si habrá más o menos de 2.5 goles en total.</span>
      </div>
      <div class="leyenda-item">
        <span class="term">Spread</span>
        <span class="def">Hándicap de goles. Spread -1.5 significa que el favorito debe ganar por 2 o más. +1.5 significa que el underdog puede perder por 1 y aún cubrir.</span>
      </div>
      <div class="leyenda-item">
        <span class="term">H2H</span>
        <span class="def">Head to Head. Historial de enfrentamientos directos en formato Local–Empates–Visitante (últimos 5 partidos entre ambos equipos).</span>
      </div>
      <div class="leyenda-item">
        <span class="term">Forma</span>
        <span class="def">Últimos 5 resultados: W = victoria, D = empate, L = derrota. <em>ESPN no lo publica para Bundesliga ni 2. Bundesliga. Disponible en La Liga, Serie A, Noruega y Bolivia.</em></span>
      </div>
      <div class="leyenda-item">
        <span class="term">Score</span>
        <span class="def">Puntuación de analizabilidad calculada por el algoritmo. Suma brecha de tabla, claridad de odds, dominio H2H, forma y contexto de temporada.</span>
      </div>
      <div class="leyenda-item">
        <span class="term">Tabla A / B</span>
        <span class="def">Tabla A penaliza ligas con menos de 8 partidos jugados (datos poco fiables). Tabla B confía en odds y H2H aunque la temporada sea joven.</span>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.futbol-leyenda {
  margin-top: var(--space-10);
  padding-top: var(--space-5);
  border-top: 1px solid var(--tokyo-bg-tertiary);
}

.leyenda-titulo {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--tokyo-fg-dim);
  margin-bottom: var(--space-3);
  opacity: 0.6;
}

.leyenda-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--space-2) var(--space-5);
}

.leyenda-item {
  display: flex;
  gap: var(--space-2);
  align-items: baseline;
  font-size: 0.76rem;
  line-height: 1.5;
}

.term {
  font-weight: 700;
  color: var(--tokyo-fg-dim);
  white-space: nowrap;
  min-width: 52px;
}

.def {
  color: var(--tokyo-fg-dim);
  opacity: 0.65;
}

.def em {
  font-style: normal;
  opacity: 0.55;
}
</style>
