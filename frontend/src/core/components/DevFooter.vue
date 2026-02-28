<template>
  <footer class="dev-footer">
    <div class="dev-footer__item">
      <i class="pi pi-file" />
      <span>{{ route.meta.view ?? '—' }}</span>
      <CopyButton v-if="route.meta.view" :text="route.meta.view" icon="pi-file" />
    </div>
    <div class="dev-footer__item">
      <i class="pi pi-arrow-right-arrow-left" />
      <span>{{ route.meta.endpoint ?? '—' }}</span>
      <CopyButton v-if="route.meta.endpoint" :text="route.meta.endpoint" icon="pi-copy" />
    </div>
  </footer>
</template>

<script setup>
import { useRoute } from 'vue-router'
import CopyButton from '@/core/components/CopyButton.vue'

const route = useRoute()
</script>

<style scoped>
.dev-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 32px;
  background: var(--p-surface-900, #0f172a);
  color: var(--p-surface-400, #94a3b8);
  font-size: 0.75rem;
  font-family: monospace;
  display: flex;
  align-items: center;
  gap: 0;
  z-index: 200;
  border-top: 1px solid var(--p-surface-700, #334155);
}

.dev-footer__item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0 1rem;
  height: 100%;
  border-right: 1px solid var(--p-surface-700, #334155);
}

.dev-footer__item .pi {
  font-size: 0.7rem;
  color: var(--p-primary-400, #818cf8);
}
</style>
