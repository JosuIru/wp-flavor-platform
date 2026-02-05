<template>
  <div class="flavor-pb-block-toolbar" @mousedown.stop>
    <span class="toolbar-label">{{ componentLabel }}</span>

    <div class="toolbar-actions">
      <button
        class="action-btn"
        @click="$emit('move-up')"
        title="Mover arriba (Ctrl+↑)"
      >
        <span class="dashicons dashicons-arrow-up-alt2"></span>
      </button>

      <button
        class="action-btn"
        @click="$emit('move-down')"
        title="Mover abajo (Ctrl+↓)"
      >
        <span class="dashicons dashicons-arrow-down-alt2"></span>
      </button>

      <div class="action-separator"></div>

      <button
        class="action-btn"
        @click="$emit('duplicate')"
        title="Duplicar (Ctrl+D)"
      >
        <span class="dashicons dashicons-admin-page"></span>
      </button>

      <button
        class="action-btn danger"
        @click="$emit('remove')"
        title="Eliminar (Delete)"
      >
        <span class="dashicons dashicons-trash"></span>
      </button>
    </div>

    <div
      class="drag-handle"
      title="Arrastrar para mover"
    >
      <span class="dashicons dashicons-move"></span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  blockId: {
    type: String,
    required: true,
  },
  blockIndex: {
    type: Number,
    required: true,
  },
  componentLabel: {
    type: String,
    default: 'Bloque',
  },
});

defineEmits(['move-up', 'move-down', 'duplicate', 'remove']);
</script>

<style scoped>
.flavor-pb-block-toolbar {
  position: absolute;
  top: -1px;
  left: -1px;
  right: -1px;
  display: flex;
  align-items: center;
  padding: 4px 8px;
  background: var(--pb-primary);
  color: white;
  font-size: 11px;
  border-radius: var(--pb-radius) var(--pb-radius) 0 0;
  z-index: 10;
}

.toolbar-label {
  flex: 1;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 2px;
}

.action-btn {
  padding: 4px;
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  border-radius: 3px;
  transition: background 0.2s;
}

.action-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.action-btn.danger:hover {
  background: var(--pb-error);
}

.action-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

.action-separator {
  width: 1px;
  height: 16px;
  background: rgba(255, 255, 255, 0.3);
  margin: 0 4px;
}

.drag-handle {
  margin-left: 8px;
  padding: 4px 8px;
  cursor: grab;
  border-left: 1px solid rgba(255, 255, 255, 0.3);
}

.drag-handle:active {
  cursor: grabbing;
}

.drag-handle .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}
</style>
