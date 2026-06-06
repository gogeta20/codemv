<template>
  <div class="forma-chips">
    <span v-for="(r, i) in resultados" :key="i" class="chip" :class="chipClass(r)">{{ r }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  forma: { type: String, default: '' },
})

const resultados = computed(() =>
  props.forma ? props.forma.toUpperCase().split(/[-,\s]+/).filter(Boolean) : []
)

function chipClass(r) {
  if (r === 'W') return 'win'
  if (r === 'L') return 'loss'
  return 'draw'
}
</script>

<style scoped>
.forma-chips { display: flex; gap: 3px; }
.chip { font-size: 0.65rem; font-weight: 700; width: 16px; height: 16px; border-radius: 3px; display: flex; align-items: center; justify-content: center; }
.win  { background: rgba(158,206,106,0.2); color: #9ece6a; }
.loss { background: rgba(247,118,142,0.2); color: #f7768e; }
.draw { background: rgba(148,148,148,0.15); color: var(--tokyo-fg-dim); }
</style>
