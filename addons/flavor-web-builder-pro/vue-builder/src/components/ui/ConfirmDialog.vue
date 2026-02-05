<template>
  <Modal title="Confirmar" size="small" @close="cancel">
    <div class="confirm-dialog-content">
      <span class="confirm-icon dashicons dashicons-warning"></span>
      <p class="confirm-message">{{ message }}</p>
    </div>

    <template #footer>
      <button class="button" @click="cancel">Cancelar</button>
      <button class="button button-primary" @click="confirm">Confirmar</button>
    </template>
  </Modal>
</template>

<script setup>
import Modal from './Modal.vue';

defineProps({
  message: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['confirm', 'cancel']);

function confirm() {
  emit('confirm');
}

function cancel() {
  emit('cancel');
}
</script>

<style scoped>
.confirm-dialog-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 30px 20px;
}

.confirm-icon {
  font-size: 48px;
  width: 48px;
  height: 48px;
  color: var(--pb-warning);
  margin-bottom: 16px;
}

.confirm-message {
  margin: 0;
  font-size: 14px;
  color: var(--pb-text);
  line-height: 1.5;
}
</style>
