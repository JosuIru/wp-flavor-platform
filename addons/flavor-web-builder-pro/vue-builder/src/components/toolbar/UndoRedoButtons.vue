<template>
  <div class="undo-redo-buttons">
    <button
      class="undo-btn"
      @click="handleUndo"
      :disabled="!canUndo"
      title="Deshacer (Ctrl+Z)"
    >
      <span class="dashicons dashicons-undo"></span>
    </button>

    <button
      class="redo-btn"
      @click="handleRedo"
      :disabled="!canRedo"
      title="Rehacer (Ctrl+Shift+Z)"
    >
      <span class="dashicons dashicons-redo"></span>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useHistory } from '../../composables/useHistory';

const builderStore = useBuilderStore();
const { undo, redo } = useHistory();

const canUndo = computed(() => builderStore.canUndo);
const canRedo = computed(() => builderStore.canRedo);

function handleUndo() {
  undo();
}

function handleRedo() {
  redo();
}
</script>

<style scoped>
.undo-redo-buttons {
  display: flex;
  gap: 4px;
}

.undo-btn,
.redo-btn {
  padding: 6px 8px;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--pb-radius);
  color: var(--pb-text);
  cursor: pointer;
  transition: all 0.2s;
}

.undo-btn:hover:not(:disabled),
.redo-btn:hover:not(:disabled) {
  background: var(--pb-bg);
  border-color: var(--pb-border);
}

.undo-btn:disabled,
.redo-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.undo-btn .dashicons,
.redo-btn .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
}
</style>
