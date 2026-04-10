<template>
  <FieldWrapper :field="field" field-class="icon-field">
    <div class="icon-input-wrapper">
      <div class="icon-preview">
        <span v-if="value" class="dashicons" :class="value"></span>
        <span v-else class="no-icon">—</span>
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

    <!-- Icon picker (lazy loaded) -->
    <div v-if="showPicker" class="icon-picker">
      <div class="picker-header">
        <input
          ref="searchInput"
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
          :title="icon.replace('dashicons-', '')"
        >
          <span class="dashicons" :class="icon"></span>
        </button>
      </div>
      <div v-if="filteredIcons.length === 0" class="no-results">
        Sin resultados para "{{ searchQuery }}"
      </div>
    </div>
  </FieldWrapper>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import FieldWrapper from './FieldWrapper.vue';

const props = defineProps({
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
const searchInput = ref(null);

// Lista de iconos dashicons organizados por categoría
const DASHICONS = Object.freeze([
  // Admin
  'dashicons-menu', 'dashicons-admin-site', 'dashicons-dashboard', 'dashicons-admin-post',
  'dashicons-admin-media', 'dashicons-admin-page', 'dashicons-admin-comments',
  'dashicons-admin-appearance', 'dashicons-admin-plugins', 'dashicons-admin-users',
  'dashicons-admin-tools', 'dashicons-admin-settings', 'dashicons-admin-network',
  'dashicons-admin-generic',
  // Format
  'dashicons-welcome-write-blog', 'dashicons-format-image', 'dashicons-format-gallery',
  'dashicons-format-audio', 'dashicons-format-video', 'dashicons-format-chat',
  'dashicons-format-status', 'dashicons-format-aside', 'dashicons-format-quote',
  // Rating
  'dashicons-star-empty', 'dashicons-star-filled', 'dashicons-star-half',
  // Time
  'dashicons-calendar', 'dashicons-calendar-alt',
  // Visibility
  'dashicons-visibility', 'dashicons-hidden', 'dashicons-lock', 'dashicons-unlock',
  'dashicons-shield', 'dashicons-shield-alt', 'dashicons-flag',
  // Social
  'dashicons-heart', 'dashicons-thumbs-up', 'dashicons-thumbs-down',
  'dashicons-share', 'dashicons-share-alt', 'dashicons-share-alt2',
  // Communication
  'dashicons-email', 'dashicons-email-alt', 'dashicons-email-alt2',
  'dashicons-phone', 'dashicons-smartphone', 'dashicons-tablet', 'dashicons-desktop', 'dashicons-laptop',
  // Commerce
  'dashicons-cart', 'dashicons-tag', 'dashicons-category',
  // Actions
  'dashicons-yes', 'dashicons-yes-alt', 'dashicons-no', 'dashicons-no-alt',
  'dashicons-plus', 'dashicons-plus-alt', 'dashicons-plus-alt2', 'dashicons-minus', 'dashicons-dismiss',
  // Location
  'dashicons-marker', 'dashicons-location', 'dashicons-location-alt',
  // People
  'dashicons-awards', 'dashicons-lightbulb', 'dashicons-businessperson', 'dashicons-businesswoman',
  'dashicons-businessman', 'dashicons-groups', 'dashicons-universal-access',
  'dashicons-universal-access-alt', 'dashicons-id', 'dashicons-id-alt', 'dashicons-nametag',
  'dashicons-superhero', 'dashicons-superhero-alt',
  // Misc
  'dashicons-games', 'dashicons-coffee', 'dashicons-food', 'dashicons-beer', 'dashicons-palmtree',
  'dashicons-money', 'dashicons-money-alt',
  // Charts
  'dashicons-chart-bar', 'dashicons-chart-pie', 'dashicons-chart-line', 'dashicons-chart-area',
]);

const filteredIcons = computed(() => {
  if (!searchQuery.value) return DASHICONS;
  const query = searchQuery.value.toLowerCase();
  return DASHICONS.filter(icon => icon.includes(query));
});

// Focus search input cuando se abre el picker
watch(showPicker, async (isOpen) => {
  if (isOpen) {
    await nextTick();
    searchInput.value?.focus();
  } else {
    searchQuery.value = '';
  }
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
.icon-input-wrapper {
  display: flex;
  align-items: center;
  gap: 6px;
}

.icon-preview {
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--pb-bg);
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
}

.icon-preview .dashicons {
  font-size: 18px;
  width: 18px;
  height: 18px;
  color: var(--pb-primary);
}

.no-icon {
  font-size: 10px;
  color: var(--pb-text-muted);
}

.icon-text {
  flex: 1;
  min-width: 0;
  padding: 6px 8px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 11px;
  font-family: monospace;
  background: var(--pb-bg-light);
}

.browse-btn {
  padding: 6px;
  background: var(--pb-bg);
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
  flex-shrink: 0;
}

.browse-btn:hover {
  background: var(--pb-border-light);
  border-color: var(--pb-primary);
}

.browse-btn .dashicons {
  font-size: 14px;
  width: 14px;
  height: 14px;
}

/* Icon picker */
.icon-picker {
  margin-top: 8px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  background: var(--pb-bg-light);
  max-height: 220px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.picker-header {
  padding: 8px;
  border-bottom: 1px solid var(--pb-border-light);
  flex-shrink: 0;
}

.picker-search {
  width: 100%;
  padding: 6px 8px;
  border: 1px solid var(--pb-border);
  border-radius: 3px;
  font-size: 12px;
}

.picker-search:focus {
  outline: none;
  border-color: var(--pb-primary);
}

.icons-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 2px;
  padding: 8px;
  overflow-y: auto;
  flex: 1;
}

.icon-btn {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: 1px solid transparent;
  border-radius: 3px;
  cursor: pointer;
  transition: all 0.15s;
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

.no-results {
  padding: 20px;
  text-align: center;
  font-size: 12px;
  color: var(--pb-text-muted);
}
</style>
