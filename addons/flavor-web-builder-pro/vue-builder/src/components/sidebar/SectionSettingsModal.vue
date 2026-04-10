<template>
  <Modal
    title="Configuración de Sección"
    size="medium"
    @close="$emit('close')"
  >
    <div class="section-settings-content" v-if="section">
      <!-- Ancho completo -->
      <div class="settings-field">
        <label class="field-checkbox">
          <input type="checkbox" v-model="localSettings.fullWidth" />
          <span>Ancho completo</span>
        </label>
        <p class="field-description">
          Extender la sección al ancho completo de la pantalla
        </p>
      </div>

      <!-- Color de fondo -->
      <div class="settings-field">
        <label class="field-label">Color de fondo</label>
        <div class="color-field">
          <input
            type="color"
            v-model="localSettings.backgroundColor"
            class="color-picker"
          />
          <input
            type="text"
            v-model="localSettings.backgroundColor"
            placeholder="#ffffff o transparent"
            class="color-input"
          />
          <button
            v-if="localSettings.backgroundColor"
            class="color-clear"
            @click="localSettings.backgroundColor = ''"
            title="Quitar color"
          >
            <span class="dashicons dashicons-no-alt"></span>
          </button>
        </div>
      </div>

      <!-- Padding superior -->
      <div class="settings-field">
        <label class="field-label">Espaciado superior</label>
        <div class="spacing-field">
          <input
            type="text"
            v-model="localSettings.paddingTop"
            placeholder="ej: 40px, 2rem"
            class="spacing-input"
          />
          <div class="spacing-presets">
            <button
              v-for="preset in SPACING_PRESETS"
              :key="preset.value"
              class="preset-btn"
              :class="{ active: localSettings.paddingTop === preset.value }"
              @click="localSettings.paddingTop = preset.value"
            >
              {{ preset.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Padding inferior -->
      <div class="settings-field">
        <label class="field-label">Espaciado inferior</label>
        <div class="spacing-field">
          <input
            type="text"
            v-model="localSettings.paddingBottom"
            placeholder="ej: 40px, 2rem"
            class="spacing-input"
          />
          <div class="spacing-presets">
            <button
              v-for="preset in SPACING_PRESETS"
              :key="preset.value"
              class="preset-btn"
              :class="{ active: localSettings.paddingBottom === preset.value }"
              @click="localSettings.paddingBottom = preset.value"
            >
              {{ preset.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Clase CSS personalizada -->
      <div class="settings-field">
        <label class="field-label">Clase CSS personalizada</label>
        <input
          type="text"
          v-model="localSettings.customClass"
          placeholder="mi-seccion-especial"
          class="text-input"
        />
        <p class="field-description">
          Agregar clases CSS personalizadas a la sección
        </p>
      </div>

      <!-- ID de sección -->
      <div class="settings-field">
        <label class="field-label">ID de sección (ancla)</label>
        <input
          type="text"
          v-model="localSettings.sectionId"
          placeholder="mi-seccion"
          class="text-input"
        />
        <p class="field-description">
          ID para enlaces ancla (ej: #mi-seccion)
        </p>
      </div>
    </div>

    <template #footer>
      <button class="button" @click="$emit('close')">Cancelar</button>
      <button class="button button-primary" @click="saveSettings">
        Guardar cambios
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import Modal from '../ui/Modal.vue';
import { useBuilderStore } from '../../stores/builderStore';
import { useUiStore } from '../../stores/uiStore';

const props = defineProps({
  sectionId: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['close']);

const builderStore = useBuilderStore();
const uiStore = useUiStore();

// Presets de espaciado
const SPACING_PRESETS = Object.freeze([
  { label: '0', value: '' },
  { label: 'S', value: '20px' },
  { label: 'M', value: '40px' },
  { label: 'L', value: '60px' },
  { label: 'XL', value: '80px' },
]);

// Obtener sección
const section = computed(() => builderStore.getSectionById(props.sectionId));

// Settings locales para edición
const localSettings = ref({
  fullWidth: false,
  backgroundColor: '',
  paddingTop: '',
  paddingBottom: '',
  customClass: '',
  sectionId: '',
});

// Cargar settings de la sección al montar
onMounted(() => {
  if (section.value?.settings) {
    const settings = section.value.settings;
    localSettings.value = {
      fullWidth: settings.fullWidth || false,
      backgroundColor: settings.backgroundColor || '',
      paddingTop: settings.paddingTop || '',
      paddingBottom: settings.paddingBottom || '',
      customClass: settings.customClass || '',
      sectionId: settings.sectionId || '',
    };
  }
});

function saveSettings() {
  if (!section.value) return;

  builderStore.updateSectionSettings(props.sectionId, { ...localSettings.value });
  uiStore.showSuccess('Configuración de sección guardada');
  emit('close');
}
</script>

<style scoped>
.section-settings-content {
  padding: 20px;
}

/* Settings fields */
.settings-field {
  margin-bottom: 20px;
}

.settings-field:last-child {
  margin-bottom: 0;
}

.field-label {
  display: block;
  font-weight: 500;
  margin-bottom: 8px;
  font-size: 13px;
  color: var(--pb-text);
}

.field-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  font-size: 13px;
  cursor: pointer;
}

.field-checkbox input {
  width: 16px;
  height: 16px;
}

.field-description {
  margin: 6px 0 0;
  font-size: 12px;
  color: var(--pb-text-muted);
}

/* Color field */
.color-field {
  display: flex;
  gap: 8px;
  align-items: center;
}

.color-picker {
  width: 40px;
  height: 32px;
  padding: 2px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
}

.color-input {
  flex: 1;
  padding: 6px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
}

.color-clear {
  padding: 4px 8px;
  background: none;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
  color: var(--pb-text-muted);
}

.color-clear:hover {
  background: var(--pb-bg);
  color: var(--pb-error);
}

/* Spacing field */
.spacing-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.spacing-input {
  padding: 8px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
}

.spacing-presets {
  display: flex;
  gap: 4px;
}

.preset-btn {
  padding: 4px 12px;
  font-size: 12px;
  background: var(--pb-bg);
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  cursor: pointer;
  transition: all 0.15s;
}

.preset-btn:hover {
  background: var(--pb-bg-light);
  border-color: var(--pb-primary);
}

.preset-btn.active {
  background: var(--pb-primary);
  color: white;
  border-color: var(--pb-primary);
}

/* Text input */
.text-input {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--pb-border);
  border-radius: var(--pb-radius);
  font-size: 13px;
}

.text-input:focus,
.spacing-input:focus,
.color-input:focus {
  outline: none;
  border-color: var(--pb-primary);
  box-shadow: 0 0 0 1px var(--pb-primary);
}
</style>
