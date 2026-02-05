<template>
  <div class="preview-overlay">
    <div class="preview-message">
      <span class="dashicons dashicons-visibility"></span>
      <span>Modo Vista Previa</span>
      <button class="exit-btn" @click="exitPreview">
        <span class="dashicons dashicons-no-alt"></span>
        Salir (Esc)
      </button>
    </div>
  </div>
</template>

<script setup>
import { useUiStore } from '../../stores/uiStore';

const uiStore = useUiStore();

function exitPreview() {
  uiStore.togglePreviewMode();
}
</script>

<style scoped>
.preview-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.03);
  padding: 10px;
  pointer-events: all;
}

.preview-message {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--pb-info);
  color: white;
  border-radius: var(--pb-radius);
  font-size: 13px;
}

.preview-message .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
}

.exit-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-left: 16px;
  padding: 4px 12px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 3px;
  color: white;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.2s;
}

.exit-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.exit-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}
</style>
