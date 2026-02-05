<template>
  <div
    class="inline-text-editor"
    :contenteditable="true"
    @input="handleInput"
    @blur="handleBlur"
    @keydown="handleKeydown"
    ref="editorRef"
  ></div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';

const props = defineProps({
  value: {
    type: String,
    default: '',
  },
  multiline: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update', 'confirm', 'cancel']);

const editorRef = ref(null);

// Sincronizar contenido cuando cambia el valor
watch(() => props.value, (newValue) => {
  if (editorRef.value && editorRef.value.innerText !== newValue) {
    editorRef.value.innerText = newValue;
  }
});

onMounted(() => {
  if (editorRef.value) {
    editorRef.value.innerText = props.value;
    editorRef.value.focus();

    // Seleccionar todo el texto
    const range = document.createRange();
    range.selectNodeContents(editorRef.value);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
  }
});

function handleInput(event) {
  emit('update', event.target.innerText);
}

function handleBlur() {
  emit('confirm');
}

function handleKeydown(event) {
  // Enter confirma (si no es multiline)
  if (event.key === 'Enter' && !props.multiline) {
    event.preventDefault();
    editorRef.value?.blur();
    emit('confirm');
  }

  // Escape cancela
  if (event.key === 'Escape') {
    event.preventDefault();
    emit('cancel');
  }
}
</script>

<style scoped>
.inline-text-editor {
  outline: 2px solid var(--pb-primary);
  outline-offset: 2px;
  background: rgba(255, 255, 255, 0.95);
  min-width: 50px;
  padding: 2px 4px;
  cursor: text;
}

.inline-text-editor:focus {
  outline: 2px solid var(--pb-primary);
}
</style>
