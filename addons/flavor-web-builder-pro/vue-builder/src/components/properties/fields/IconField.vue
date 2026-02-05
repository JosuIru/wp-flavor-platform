<template>
  <div class="field icon-field">
    <label class="field-label">
      {{ field.label || field.key }}
    </label>
    <div class="icon-input-wrapper">
      <div class="icon-preview">
        <span v-if="value" class="dashicons" :class="value"></span>
        <span v-else class="no-icon">Sin icono</span>
      </div>
      <input
        type="text"
        class="icon-text"
        :value="value"
        placeholder="dashicons-star-filled"
        @input="handleInput"
      />
      <button class="browse-btn" @click="toggleIconPicker" title="Explorar iconos">
        <span class="dashicons dashicons-search"></span>
      </button>
    </div>

    <!-- Icon picker -->
    <div v-if="showPicker" class="icon-picker">
      <div class="picker-header">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Buscar icono..."
          class="picker-search"
        />
      </div>
      <div class="icons-grid">
        <button
          v-for="icon in filteredIcons"
          :key="icon"
          class="icon-btn"
          :class="{ 'is-selected': value === icon }"
          @click="selectIcon(icon)"
          :title="icon"
        >
          <span class="dashicons" :class="icon"></span>
        </button>
      </div>
    </div>

    <p v-if="field.description" class="field-description">
      {{ field.description }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

defineProps({
  field: {
    type: Object,
    required: true,
  },
  value: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update']);

const showPicker = ref(false);
const searchQuery = ref('');

// Lista de iconos dashicons comunes
const dashicons = [
  'dashicons-menu', 'dashicons-admin-site', 'dashicons-dashboard', 'dashicons-admin-post',
  'dashicons-admin-media', 'dashicons-admin-page', 'dashicons-admin-comments',
  'dashicons-admin-appearance', 'dashicons-admin-plugins', 'dashicons-admin-users',
  'dashicons-admin-tools', 'dashicons-admin-settings', 'dashicons-admin-network',
  'dashicons-admin-generic', 'dashicons-welcome-write-blog', 'dashicons-format-image',
  'dashicons-format-gallery', 'dashicons-format-audio', 'dashicons-format-video',
  'dashicons-format-chat', 'dashicons-format-status', 'dashicons-format-aside',
  'dashicons-format-quote', 'dashicons-star-empty', 'dashicons-star-filled',
  'dashicons-star-half', 'dashicons-calendar', 'dashicons-calendar-alt',
  'dashicons-visibility', 'dashicons-hidden', 'dashicons-lock', 'dashicons-unlock',
  'dashicons-shield', 'dashicons-shield-alt', 'dashicons-flag', 'dashicons-heart',
  'dashicons-thumbs-up', 'dashicons-thumbs-down', 'dashicons-share', 'dashicons-share-alt',
  'dashicons-share-alt2', 'dashicons-email', 'dashicons-email-alt', 'dashicons-email-alt2',
  'dashicons-phone', 'dashicons-smartphone', 'dashicons-tablet', 'dashicons-desktop',
  'dashicons-laptop', 'dashicons-cart', 'dashicons-tag', 'dashicons-category',
  'dashicons-yes', 'dashicons-yes-alt', 'dashicons-no', 'dashicons-no-alt',
  'dashicons-plus', 'dashicons-plus-alt', 'dashicons-plus-alt2', 'dashicons-minus',
  'dashicons-dismiss', 'dashicons-marker', 'dashicons-location', 'dashicons-location-alt',
  'dashicons-awards', 'dashicons-lightbulb', 'dashicons-businessperson', 'dashicons-businesswoman',
  'dashicons-businessman', 'dashicons-groups', 'dashicons-universal-access',
  'dashicons-universal-access-alt', 'dashicons-id', 'dashicons-id-alt', 'dashicons-nametag',
  'dashicons-superhero', 'dashicons-superhero-alt', 'dashicons-games', 'dashicons-coffee',
  'dashicons-food', 'dashicons-beer', 'dashicons-palmtree', 'dashicons-money', 'dashicons-money-alt',
  'dashicons-chart-bar', 'dashicons-chart-pie', 'dashicons-chart-line', 'dashicons-chart-area',
];

const filteredIcons = computed(() => {
  if (!searchQuery.value) return dashicons;
  const query = searchQuery.value.toLowerCase();
  return dashicons.filter(icon => icon.toLowerCase().includes(query));
});

function toggleIconPicker() {
  showPicker.value = !showPicker.value;
}

function selectIcon(icon) {
  emit('update', icon);
  showPicker.value = false;
}

function handleInput(event) {
  emit('update', event.target.value.trim());
}
</script>

<style scoped>
.field-label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  font-weight: 500;
  color: var(--pb-text);
}

.icon-input-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.icon-preview {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--pb-bg);
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
}

.icon-preview .dashicons {
  font-size: 20px;
  width: 20px;
  height: 20px;
  color: var(--pb-primary);
}

.no-icon {
  font-size: 9px;
  color: var(--pb-text-muted);
}

.icon-text {
  flex: 1;
  padding: 8px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 12px;
  font-family: monospace;
  background: var(--pb-bg-light);
}

.browse-btn {
  padding: 8px;
  background: var(--pb-bg);
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
}

.browse-btn:hover {
  background: var(--pb-border-light);
}

/* Icon picker */
.icon-picker {
  margin-top: 8px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  background: var(--pb-bg-light);
  max-height: 200px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.picker-header {
  padding: 8px;
  border-bottom: 1px solid var(--pb-border-light);
}

.picker-search {
  width: 100%;
  padding: 6px 8px;
  border: 1px solid var(--pb-border);
  border-radius: 3px;
  font-size: 12px;
}

.icons-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 2px;
  padding: 8px;
  overflow-y: auto;
}

.icon-btn {
  padding: 6px;
  background: none;
  border: 1px solid transparent;
  border-radius: 3px;
  cursor: pointer;
  transition: all 0.2s;
}

.icon-btn:hover {
  background: var(--pb-bg);
  border-color: var(--pb-border);
}

.icon-btn.is-selected {
  background: var(--pb-primary);
  border-color: var(--pb-primary);
}

.icon-btn.is-selected .dashicons {
  color: white;
}

.icon-btn .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
}

.field-description {
  margin: 6px 0 0;
  font-size: 11px;
  color: var(--pb-text-muted);
}
</style>
