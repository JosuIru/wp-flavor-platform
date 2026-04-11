<template>
  <div class="device-toggle">
    <button
      v-for="device in devices"
      :key="device.id"
      class="device-btn"
      :class="{ 'is-active': currentDevice === device.id }"
      @click="setDevice(device.id)"
      :title="`${device.label} (Alt+${device.shortcut})`"
    >
      <span class="dashicons" :class="device.icon"></span>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useUiStore } from '../../stores/uiStore';

const uiStore = useUiStore();

// Definición estática de dispositivos (inmutable)
const devices = Object.freeze([
  Object.freeze({ id: 'desktop', label: 'Escritorio', icon: 'dashicons-desktop', shortcut: '1' }),
  Object.freeze({ id: 'tablet', label: 'Tablet', icon: 'dashicons-tablet', shortcut: '2' }),
  Object.freeze({ id: 'mobile', label: 'Móvil', icon: 'dashicons-smartphone', shortcut: '3' }),
]);

const currentDevice = computed(() => uiStore.previewDevice);

function setDevice(device) {
  uiStore.setPreviewDevice(device);
}
</script>

<style scoped>
.device-toggle {
  display: flex;
  gap: 2px;
  padding: 4px;
  background: var(--pb-bg);
  border-radius: var(--pb-radius);
}

.device-btn {
  padding: 6px 10px;
  background: transparent;
  border: none;
  border-radius: 3px;
  color: var(--pb-text-muted);
  cursor: pointer;
  transition: all 0.2s;
}

.device-btn:hover {
  color: var(--pb-text);
}

.device-btn.is-active {
  background: var(--pb-bg-light);
  color: var(--pb-primary);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.device-btn .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
}
</style>
