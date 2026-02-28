<template>
  <button class="copy-btn" @click="copy" :title="copied ? 'Copiado' : 'Copiar'">
    <i class="pi" :class="copied ? 'pi-check' : icon" />
  </button>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  text:  { type: String, required: true },
  icon:  { type: String, default: 'pi-copy' },
})

const copied = ref(false)

async function copy() {
  await navigator.clipboard.writeText(props.text)
  copied.value = true
  setTimeout(() => { copied.value = false }, 1500)
}
</script>

<style scoped>
.copy-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 2px 4px;
  border-radius: 4px;
  color: inherit;
  transition: color 0.15s;
}

.copy-btn:hover { color: var(--p-primary-400, #818cf8); }

.copy-btn .pi-check { color: #22c55e; }
</style>
