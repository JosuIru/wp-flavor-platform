<template>
  <div class="field toggle-field">
    <label class="toggle-wrapper">
      <span class="toggle-switch" :class="{ 'is-active': value }">
        <span class="toggle-handle"></span>
      </span>
      <span class="toggle-label">
        {{ field.label || field.key }}
      </span>
    </label>
    <input
      type="checkbox"
      class="toggle-input"
      :checked="value"
      @change="handleChange"
    />
    <p v-if="field.description" class="field-description">
      {{ field.description }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  field: {
    type: Object,
    required: true,
  },
  value: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update']);

function handleChange(event) {
  emit('update', event.target.checked);
}
</script>

<style scoped>
.toggle-field {
  position: relative;
}

.toggle-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.toggle-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-switch {
  position: relative;
  width: 40px;
  height: 22px;
  background: var(--pb-border);
  border-radius: 11px;
  transition: background 0.2s;
}

.toggle-switch.is-active {
  background: var(--pb-primary);
}

.toggle-handle {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 18px;
  height: 18px;
  background: white;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s;
}

.toggle-switch.is-active .toggle-handle {
  transform: translateX(18px);
}

.toggle-label {
  font-size: 13px;
  color: var(--pb-text);
}

.field-description {
  margin: 6px 0 0 50px;
  font-size: 11px;
  color: var(--pb-text-muted);
}
</style>
