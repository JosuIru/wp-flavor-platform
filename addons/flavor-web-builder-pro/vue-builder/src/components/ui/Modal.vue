<template>
  <Teleport to="body">
    <div class="modal-overlay" @click.self="handleOverlayClick">
      <div class="modal-container" :class="sizeClass">
        <!-- Header -->
        <div class="modal-header">
          <h3 class="modal-title">{{ title }}</h3>
          <button class="modal-close" @click="close" title="Cerrar (Esc)">
            <span class="dashicons dashicons-no-alt"></span>
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <slot></slot>
        </div>

        <!-- Footer -->
        <div class="modal-footer" v-if="$slots.footer">
          <slot name="footer"></slot>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Modal',
  },
  size: {
    type: String,
    default: 'medium', // 'small' | 'medium' | 'large' | 'full'
  },
  closeOnOverlay: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['close']);

const sizeClass = computed(() => `modal-${props.size}`);

function close() {
  emit('close');
}

function handleOverlayClick() {
  if (props.closeOnOverlay) {
    close();
  }
}

function handleKeydown(event) {
  if (event.key === 'Escape') {
    close();
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
  document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100002;
  padding: 20px;
}

.modal-container {
  background: var(--pb-bg-light);
  border-radius: var(--pb-radius-lg);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 40px);
  animation: modalFadeIn 0.2s ease;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Sizes */
.modal-small {
  width: 400px;
  max-width: 100%;
}

.modal-medium {
  width: 600px;
  max-width: 100%;
}

.modal-large {
  width: 900px;
  max-width: 100%;
}

.modal-full {
  width: calc(100vw - 40px);
  height: calc(100vh - 40px);
}

/* Header */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--pb-border-light);
}

.modal-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--pb-text);
}

.modal-close {
  padding: 4px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--pb-text-muted);
  border-radius: 3px;
  transition: all 0.2s;
}

.modal-close:hover {
  background: var(--pb-bg);
  color: var(--pb-text);
}

.modal-close .dashicons {
  font-size: 20px;
  width: 20px;
  height: 20px;
}

/* Body */
.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 0;
}

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 20px;
  border-top: 1px solid var(--pb-border-light);
}

.modal-footer .button {
  min-width: 80px;
}
</style>
