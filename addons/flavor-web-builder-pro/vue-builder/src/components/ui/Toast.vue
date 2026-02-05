<template>
  <div class="toast" :class="typeClass">
    <span class="toast-icon dashicons" :class="iconClass"></span>
    <span class="toast-message">{{ message }}</span>
    <button class="toast-close" @click="close">
      <span class="dashicons dashicons-no-alt"></span>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  message: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: 'info', // 'success' | 'error' | 'warning' | 'info'
  },
});

const emit = defineEmits(['close']);

const typeClass = computed(() => `toast-${props.type}`);

const iconClass = computed(() => {
  const icons = {
    success: 'dashicons-yes-alt',
    error: 'dashicons-warning',
    warning: 'dashicons-info',
    info: 'dashicons-info-outline',
  };
  return icons[props.type] || icons.info;
});

function close() {
  emit('close');
}
</script>

<style scoped>
.toast {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: var(--pb-bg-light);
  border-radius: var(--pb-radius);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  animation: toastSlideIn 0.3s ease;
  min-width: 250px;
  max-width: 400px;
}

@keyframes toastSlideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.toast-icon {
  font-size: 18px;
  width: 18px;
  height: 18px;
}

.toast-message {
  flex: 1;
  font-size: 13px;
  color: var(--pb-text);
}

.toast-close {
  padding: 2px;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--pb-text-muted);
  border-radius: 3px;
  transition: all 0.2s;
}

.toast-close:hover {
  background: var(--pb-bg);
  color: var(--pb-text);
}

.toast-close .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

/* Types */
.toast-success {
  border-left: 4px solid var(--pb-success);
}
.toast-success .toast-icon {
  color: var(--pb-success);
}

.toast-error {
  border-left: 4px solid var(--pb-error);
}
.toast-error .toast-icon {
  color: var(--pb-error);
}

.toast-warning {
  border-left: 4px solid var(--pb-warning);
}
.toast-warning .toast-icon {
  color: var(--pb-warning);
}

.toast-info {
  border-left: 4px solid var(--pb-info);
}
.toast-info .toast-icon {
  color: var(--pb-info);
}
</style>
